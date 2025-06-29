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

        // Eager loading
        $returPenjualan->load([
            'detailPenjualan.penjualan.pelanggan',
            'detailPenjualan.produk',
            'pengguna'
        ]);

        // Opsi tindakan untuk dropdown di view
        $tindakanAdminOptions = [
            'KEMBALI_KE_STOK_BAIK_ADMIN' => 'Kembali ke Stok Aktif (Kondisi Baik)',
            'CATAT_SEBAGAI_STOK_RUSAK' => 'Catat Sebagai Stok Rusak Final (Tidak Dijual)',
            'AKAN_DIRETUR_KE_SUPPLIER' => 'Akan Diretur ke Supplier (Cacat Produksi)',
        ];
        

        return view('admin.proses_retur_pelanggan.proses_form', compact('returPenjualan', 'tindakanAdminOptions'));
    }

    /**
     * Menyimpan keputusan tindakan Admin terhadap barang retur.
     */
    public function storeTindakanAdmin(Request $request, ReturPenjualan $returPenjualan)
    {
        // 1. Validasi Input dari Form
        $validated = $request->validate([
            'tindakan_admin' => ['required', 'string', Rule::in([
                'KEMBALI_KE_STOK_BAIK_ADMIN',
                'CATAT_SEBAGAI_STOK_RUSAK_FINAL',
                'AKAN_DIRETUR_KE_SUPPLIER'
            ])],
            // Lokasi sekarang wajib untuk SEMUA tindakan
            'lokasi_tujuan_retur' => ['required', 'string'],
            'catatan_admin_proses' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        try {
            // 2. Update Nota Retur Penjualan terlebih dahulu
            $returPenjualan->tindakan_lanjut = $validated['tindakan_admin'];
            $catatanInternal = $returPenjualan->catatan_internal_retur ? $returPenjualan->catatan_internal_retur . "\n" : "";
            $catatanInternal .= "[ADMIN] " . ($validated['catatan_admin_proses'] ?? 'Tindakan diproses.');
            $returPenjualan->catatan_internal_retur = $catatanInternal;
            $returPenjualan->save();
            
            // 3. Ambil semua data yang dibutuhkan dari relasi
            $detailPenjualanAsal = $returPenjualan->detailPenjualan()
                                                  ->with(['produk', 'penjualan.pelanggan', 'stokAlokasi.stokBarang'])
                                                  ->firstOrFail();
            $produk = $detailPenjualanAsal->produk;
            
            $alokasiAsal = $detailPenjualanAsal->stokAlokasi->first();
            if (!$alokasiAsal || !$alokasiAsal->stokBarang) {
                throw new \Exception("Tidak dapat melacak batch stok asal untuk item retur.");
            }
            $stokBarangAsal = $alokasiAsal->stokBarang;
            $hargaBeliAsli = $stokBarangAsal->harga_beli;
            $idSupplierAsli = $stokBarangAsal->id_supplier;
            $idDetailPembelianAsli = $stokBarangAsal->id_detail_pembelian;
            
            // 4. Tentukan Kondisi & Lokasi berdasarkan Pilihan Admin
            $kondisiStokBaru = 'BAIK';
            $lokasiStokBaru = $validated['lokasi_tujuan_retur'];

            if ($validated['tindakan_admin'] === 'KEMBALI_KE_STOK_BAIK_ADMIN') {
                $kondisiStokBaru = 'BAIK';
            } elseif ($validated['tindakan_admin'] === 'CATAT_SEBAGAI_STOK_RUSAK_FINAL') {
                $kondisiStokBaru = 'RUSAK';
            } elseif ($validated['tindakan_admin'] === 'AKAN_DIRETUR_KE_SUPPLIER') {
                $kondisiStokBaru = 'RUSAK';
                // Bangun nama lokasi dinamis
                $lokasiStokBaru = rtrim($validated['lokasi_tujuan_retur'], '_') . '_RETUR_SUPPLIER';
            }

            // 5. Buat Batch Stok Baru
            $stokBaru = StokBarang::create([
                'id_produk'             => $produk->id,
                'id_detail_pembelian'   => $idDetailPembelianAsli,
                'id_supplier'           => $idSupplierAsli,
                'harga_beli'            => $hargaBeliAsli,
                'jumlah'                => $returPenjualan->jumlah_retur,
                'diterima_at'           => now(),
                'kondisi'               => $kondisiStokBaru,
                'lokasi'                => $lokasiStokBaru,
                'tipe_stok'             => 'RETUR_DARI_PELANGGAN'
            ]);
            
            // 6. Catat di Riwayat Pergerakan Stok
            $keteranganRiwayat = 'Retur dari Pelanggan (' . ($detailPenjualanAsal->penjualan->pelanggan->nama ?? 'Umum') . '). Tindakan: ' . str_replace('_', ' ', $validated['tindakan_admin']);
            $saldoSebelumnya = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->value('saldo_setelah_transaksi') ?? 0;
            
            if ($produk->memiliki_serial && !empty($returPenjualan->nomor_seri_diretur)) {
                $serials = explode(',', str_replace(' ', '', $returPenjualan->nomor_seri_diretur));
                $saldoBerjalan = $saldoSebelumnya;
                foreach ($serials as $sn) {
                    $saldoBerjalan += 1;
                    RiwayatPergerakanStok::create([
                        'id_produk' => $produk->id,
                        'id_stok_barang_terkait' => $stokBaru->id,
                        'nomor_seri' => trim($sn),
                        'tipe_transaksi' => 'RETUR_PELANGGAN',
                        'jumlah_masuk' => 1,
                        'jumlah_keluar' => 0,
                        'saldo_setelah_transaksi' => $saldoBerjalan,
                        'id_referensi' => $returPenjualan->id,
                        'tipe_referensi' => get_class($returPenjualan),
                        'tanggal_transaksi' => now(),
                        'keterangan' => $keteranganRiwayat,
                        'id_pengguna' => Auth::id(),
                    ]);
                }
            } else { // Untuk produk non-serial
                RiwayatPergerakanStok::create([
                    'id_produk' => $produk->id,
                    'id_stok_barang_terkait' => $stokBaru->id,
                    'nomor_seri' => null,
                    'tipe_transaksi' => 'RETUR_PELANGGAN',
                    'jumlah_masuk' => $returPenjualan->jumlah_retur,
                    'jumlah_keluar' => 0,
                    'saldo_setelah_transaksi' => $saldoSebelumnya + $returPenjualan->jumlah_retur,
                    'id_referensi' => $returPenjualan->id,
                    'tipe_referensi' => get_class($returPenjualan),
                    'tanggal_transaksi' => now(),
                    'keterangan' => $keteranganRiwayat,
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
    
    /**
     * Menampilkan halaman detail dari sebuah transaksi retur penjualan.
     *
     * @param  \App\Models\ReturPenjualan  $returPenjualan
     * @return \Illuminate\View\View
     */
    public function showDetail(ReturPenjualan $returPenjualan)
    {
        // Eager load semua relasi yang dibutuhkan untuk ditampilkan
        $returPenjualan->load([
            'detailPenjualan.penjualan.pelanggan', // Ambil nota penjualan asal & pelanggannya
            'detailPenjualan.produk', // Ambil produk yang diretur
            'pengguna' // Ambil kasir yang memproses retur
        ]);

        return view('admin.proses_retur_pelanggan.show', compact('returPenjualan'));
    }
}