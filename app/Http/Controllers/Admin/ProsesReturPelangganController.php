<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturPenjualan;
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
        Log::channel('returlog')->info("ADMIN storeTindakanAdmin untuk ReturPenjualan ID: {$returPenjualan->id}", $request->all());

        $validated = $request->validate([
            'tindakan_admin' => 'required|string',
            'catatan_admin_proses' => 'nullable|string',
            'harga_beli_batch_retur' => ['nullable', Rule::requiredIf($request->tindakan_admin === 'KEMBALI_KE_STOK_BAIK_ADMIN'), 'numeric', 'min:0'],
            'lokasi_stok_retur' => ['nullable', Rule::requiredIf($request->tindakan_admin === 'KEMBALI_KE_STOK_BAIK_ADMIN'), 'string'],
            'tipe_garansi_retur' => ['nullable', Rule::requiredIf($request->tindakan_admin === 'KEMBALI_KE_STOK_BAIK_ADMIN'), 'string'],
        ]);
        
        DB::beginTransaction();
        try {
            $catatanAdmin = "[Admin Proses: " . Carbon::now()->isoFormat('D MMM YY HH:mm') . "] ";
            $catatanAdmin .= $validated['catatan_admin_proses'] ?? '';

            $returPenjualan->tindakan_lanjut = $validated['tindakan_admin'];
            $returPenjualan->catatan_internal_retur = trim($catatanAdmin);
            $returPenjualan->save();

            $tindakanUntukBuatStokKarantina = ['CATAT_SEBAGAI_STOK_RUSAK_FINAL', 'AKAN_DIRETUR_KE_SUPPLIER', 'DITERIMA_LANGSUNG_RUSAK'];
            
            $returPenjualan->load(['detailPenjualan.produk', 'detailPenjualan.stokAlokasi.stokBarang']);
            $detailPenjualanAsal = $returPenjualan->detailPenjualan;
            $stokAsal = $detailPenjualanAsal->stokAlokasi->first()->stokBarang ?? null;

            $stokBaru = null; // Variabel untuk menampung stok baru yang dibuat

            if (in_array($validated['tindakan_admin'], $tindakanUntukBuatStokKarantina)) {
                Log::channel('returlog')->info("   Tindakan Admin: {$validated['tindakan_admin']}. Memulai pembuatan stok KARANTINA/RUSAK.");
                $stokBaru = StokBarang::create([
                    'id_produk' => $detailPenjualanAsal->id_produk, 'harga_beli' => $stokAsal->harga_beli ?? 0, 'jumlah' => $returPenjualan->jumlah_retur, 'diterima_at' => Carbon::now(), 'kondisi' => 'RUSAK', 'lokasi' => 'GUDANG_RETUR', 'id_supplier' => $stokAsal->id_supplier ?? null, 'tipe_garansi' => $stokAsal->tipe_garansi ?? 'NONE', 'tipe_stok' => $stokAsal->tipe_stok ?? 'REGULER',
                ]);
                Log::channel('returlog')->info("   StokBarang KONDISI RUSAK berhasil dibuat. ID Stok Baru: {$stokBaru->id}");
            
            } elseif ($validated['tindakan_admin'] === 'KEMBALI_KE_STOK_BAIK_ADMIN') {
                Log::channel('returlog')->info("   Tindakan Admin: KEMBALI_KE_STOK_BAIK_ADMIN. Memulai pembuatan stok BAIK.");
                $stokBaru = StokBarang::create([
                    'id_produk' => $detailPenjualanAsal->id_produk, 'harga_beli' => $validated['harga_beli_batch_retur'], 'jumlah' => $returPenjualan->jumlah_retur, 'diterima_at' => Carbon::now(), 'kondisi' => 'BAIK', 'lokasi' => $validated['lokasi_stok_retur'], 'id_supplier' => $stokAsal->id_supplier ?? null, 'tipe_garansi' => $validated['tipe_garansi_retur'], 'tipe_stok' => $stokAsal->tipe_stok ?? 'REGULER',
                ]);
                Log::channel('returlog')->info("   StokBarang KONDISI BAIK berhasil dibuat. ID Stok Baru: {$stokBaru->id}");
            }

            // LOGIKA BARU: UPDATE LOG SERIAL SETELAH STOK BARU DIBUAT (JIKA DIBUAT)
            if ($stokBaru && $detailPenjualanAsal->produk->memiliki_serial && !empty($returPenjualan->nomor_seri_diretur)) {
                $nomorSeriArray = explode(',', str_replace(' ', '', $returPenjualan->nomor_seri_diretur));
                foreach ($nomorSeriArray as $sn) {
                    // CARI log yang sudah diubah oleh kasir
                    $logSerial = LogNomorSeri::where('nomor_seri', $sn)
                        ->where('status_log', 'DIRETUR_PELANGGAN')
                        ->where('tipe_referensi', ReturPenjualan::class)
                        ->where('id_referensi', $returPenjualan->id)
                        ->first();

                    if ($logSerial) {
                        $updateData = [
                            'id_stok_barang_asal' => $stokBaru->id, // Tautkan ke batch stok baru
                            'catatan' => $logSerial->catatan . "\nDiproses Admin ke Stok ID: {$stokBaru->id}."
                        ];
                        // Jika kembali ke stok baik, ubah statusnya jadi DITERIMA lagi
                        if ($stokBaru->kondisi === 'BAIK') {
                            $updateData['status_log'] = 'DITERIMA';
                        }
                        $logSerial->update($updateData);
                        Log::channel('returlog')->info("   LogNomorSeri untuk SN: {$sn} berhasil di-UPDATE, ditautkan ke Stok ID {$stokBaru->id}.");
                    }
                }
            }

            DB::commit();
            Log::channel('returlog')->info("ADMIN storeTindakanAdmin - DB Transaction Committed.");
            return redirect()->route('admin.proses_retur_pelanggan.index')->with('success', "Tindakan untuk retur No. {$returPenjualan->nomor_retur} berhasil disimpan.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("GAGAL storeTindakanAdmin: " . $e->getMessage() . " di baris " . $e->getLine() . " - " . $e->getFile());
            return redirect()->back()->with('error', 'Terjadi kesalahan fatal: ' . $e->getMessage());
        }
    }
}