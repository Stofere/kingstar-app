<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturPenjualan;
use App\Models\RiwayatPergerakanStok;
use App\Models\StokBarang;
use App\Models\LogNomorSeri;
use App\Models\Produk; // Untuk mengambil info produk
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class ProsesReturPelangganController extends Controller
{
    /**
     * Menampilkan daftar Retur Penjualan yang menunggu tindakan dari Admin.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ReturPenjualan::with([
                    'detailPenjualan.penjualan.pelanggan',
                    'detailPenjualan.produk',
                    'pengguna'
                ])
                ->whereIn('tindakan_lanjut', [
                    'DITERIMA_KEMBALI_PERLU_CEK',
                    'DITERIMA_LANGSUNG_RUSAK'
                   
                ])
                ->orderBy('tanggal_retur', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('nomor_penjualan_asal', fn($row) => $row->detailPenjualan->penjualan->nomor_penjualan ?? '-')
                ->addColumn('nama_produk', fn($row) => $row->detailPenjualan->produk->nama ?? '-')
                ->addColumn('pelanggan', fn($row) => $row->detailPenjualan->penjualan->pelanggan->nama ?? 'Umum')

                ->addColumn('tanggal_retur_formatted', fn($row) => Carbon::parse($row->tanggal_retur)->isoFormat('D MMM YY, HH:mm'))
                
                ->addColumn('jumlah_retur_formatted', fn($row) => $row->jumlah_retur . ' unit')

                ->editColumn('alasan_retur', fn($row) => \App\Helpers\ReturHelper::formatAlasanRetur($row->alasan_retur))
                
                ->addColumn('tindakan_lanjut_awal', fn($row) => \App\Helpers\ReturHelper::formatTindakanLanjut($row->tindakan_lanjut))
                ->addColumn('kasir_proses_awal', fn($row) => $row->pengguna->nama ?? '-')
                ->addColumn('action', fn($row) => '<a href="' . route('admin.proses_retur_pelanggan.proses.form', $row->id) . '" class="btn btn-primary btn-sm"><i class="bi bi-arrow-right-circle me-1"></i> Proses</a>')
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('admin.proses_retur_pelanggan.index');
    }

    /**
     * Menampilkan form untuk Admin memutuskan tindak lanjut akhir barang retur.
     */
    public function showProsesForm(ReturPenjualan $returPenjualan)
    {
        // Validasi apakah retur ini memang boleh diproses HANYA berdasarkan tindakan_lanjut
        $tindakanYangMembutuhkanProsesAdmin = [
            'DITERIMA_KEMBALI_PERLU_CEK',
            'DITERIMA_LANGSUNG_RUSAK',
            'KOMPLAIN_KE_SUPPLIER',
            // Tambahkan nilai 'tindakan_lanjut' lain dari Kasir yang perlu diproses Admin
        ];

        if (!in_array($returPenjualan->tindakan_lanjut, $tindakanYangMembutuhkanProsesAdmin) ) {
            Log::channel('returlog')->warning("Admin coba proses Retur ID {$returPenjualan->id} dengan tindakan_lanjut '{$returPenjualan->tindakan_lanjut}' yang tidak memerlukan proses lagi.");
            return redirect()->route('admin.proses_retur_pelanggan.index')->with('error', 'Retur ini tidak memerlukan tindakan atau sudah pernah diproses sebelumnya oleh Admin.');
        }

        $returPenjualan->load([
            'detailPenjualan.penjualan.pelanggan',
            'detailPenjualan.produk',
            'pengguna'
        ]);

        $tindakanAdminOptions = [
            'KEMBALI_KE_STOK_BAIK_ADMIN' => 'Kembali ke Stok Aktif (Kondisi Baik)',
            'CATAT_SEBAGAI_STOK_RUSAK_FINAL' => 'Catat Sebagai Stok Rusak Final (Tidak Dijual)',
            'AKAN_DIRETUR_KE_SUPPLIER' => 'Akan Diretur ke Supplier (Cacat Produksi)',
        ];
        $lokasiPenyimpanan = ['GUDANG_UTAMA' => 'GUDANG UTAMA', 'GUDANG_RETUR_BAIK' => 'GUDANG RETUR BAIK', 'GUDANG_RUSAK' => 'GUDANG RUSAK'];
        $tipeGaransiOptions = ['NONE' => 'NONE', 'RESMI' => 'RESMI', 'SELF_SERVICE' => 'SELF_SERVICE'];

        return view('admin.proses_retur_pelanggan.proses_form', compact('returPenjualan', 'tindakanAdminOptions', 'lokasiPenyimpanan', 'tipeGaransiOptions'));
    }

    /**
     * Menyimpan keputusan tindakan Admin terhadap barang retur.
     */
    public function storeTindakanAdmin(Request $request, ReturPenjualan $returPenjualan)
    {
        // Validasi Proses Ganda
        $statusAwalKasir = ['DITERIMA_KEMBALI_PERLU_CEK'];
        if (!in_array($returPenjualan->tindakan_lanjut, $statusAwalKasir)) {
            return back()->with('error', 'Retur ini sudah pernah diproses oleh Admin sebelumnya.');
        }

        $validated = $request->validate([
            'tindakan_admin' => ['required', 'string', Rule::in([
                'KEMBALI_KE_STOK_BAIK',
                'KEMBALI_KE_STOK_RUSAK',
                'AKAN_DIRETUR_KE_SUPPLIER'
            ])],
            'catatan_admin_proses' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        try {
            // 1. Update Nota Retur Penjualan
            $returPenjualan->tindakan_lanjut = $validated['tindakan_admin'];
            $returPenjualan->catatan_internal_retur = $validated['catatan_admin_proses'];
            $returPenjualan->save();

            $detailPenjualanAsal = $returPenjualan->detailPenjualan()->with('produk', 'penjualan.pelanggan')->firstOrFail();
            $produk = $detailPenjualanAsal->produk;
            
            // =====================================================================
            // ## FIX: Logika diubah agar SELALU mencatat stok masuk dari pelanggan ##
            // =====================================================================
            
            // Tentukan kondisi dan lokasi berdasarkan keputusan Admin
            $kondisiStokBaru = 'RUSAK'; // Default untuk 'AKAN_DIRETUR_KE_SUPPLIER'
            $lokasiStokBaru = 'GUDANG_RETUR';
            if ($validated['tindakan_admin'] === 'KEMBALI_KE_STOK_BAIK') {
                $kondisiStokBaru = 'BAIK';
                $lokasiStokBaru = 'TOKO'; // atau GUDANG, tergantung kebijakan
            }

            // 2. Buat batch stok baru untuk menampung barang retur
            $stokBaru = StokBarang::create([
                'id_produk' => $produk->id,
                'jumlah' => $returPenjualan->jumlah_retur,
                'id_supplier' => $detailPenjualanAsal->stokAlokasi->first()->stokBarang->id_supplier ?? null, // Ambil supplier dari batch asal penjualan
                'diterima_at' => now(),
                'kondisi' => $kondisiStokBaru,
                'lokasi' => $lokasiStokBaru,
                'harga_beli' => 0,
            ]);
            
            // 3. Catat ke Riwayat Pergerakan Stok
            $keteranganRiwayat = 'Retur dari Pelanggan (' . ($detailPenjualanAsal->penjualan->pelanggan->nama ?? 'Umum') . '). Tindakan: ' . str_replace('_', ' ', $validated['tindakan_admin']);
            
            if ($produk->memiliki_serial && !empty($returPenjualan->nomor_seri_diretur)) {
                $serials = explode(',', str_replace(' ', '', $returPenjualan->nomor_seri_diretur));
                foreach ($serials as $sn) {
                    $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->first();
                    $saldoSebelumnya = $saldoTerakhir->saldo_setelah_transaksi ?? 0;
                    RiwayatPergerakanStok::create([
                        'id_produk' => $produk->id, 'id_stok_barang_terkait' => $stokBaru->id,
                        'nomor_seri' => trim($sn), 'tipe_transaksi' => 'RETUR_PELANGGAN',
                        'jumlah_masuk' => 1, 'jumlah_keluar' => 0,
                        'saldo_setelah_transaksi' => $saldoSebelumnya + 1,
                        'id_referensi' => $returPenjualan->id, 'tipe_referensi' => ReturPenjualan::class,
                        'tanggal_transaksi' => now(), 'keterangan' => $keteranganRiwayat,
                        'id_pengguna' => Auth::id(),
                    ]);
                }
            } else { // Untuk produk non-serial
                $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->first();
                $saldoSebelumnya = $saldoTerakhir->saldo_setelah_transaksi ?? 0;
                RiwayatPergerakanStok::create([
                    'id_produk' => $produk->id, 'id_stok_barang_terkait' => $stokBaru->id,
                    'tipe_transaksi' => 'RETUR_PELANGGAN', 'jumlah_masuk' => $returPenjualan->jumlah_retur,
                    'jumlah_keluar' => 0,
                    'saldo_setelah_transaksi' => $saldoSebelumnya + $returPenjualan->jumlah_retur,
                    'id_referensi' => $returPenjualan->id, 'tipe_referensi' => ReturPenjualan::class,
                    'tanggal_transaksi' => now(), 'keterangan' => $keteranganRiwayat,
                    'id_pengguna' => Auth::id(),
                ]);
            }
            
            DB::commit();
            return redirect()->route('admin.proses_retur_pelanggan.index')->with('success', "Tindakan untuk retur No. {$returPenjualan->nomor_retur} berhasil disimpan.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan fatal: ' . $e->getMessage());
        }
    }
}