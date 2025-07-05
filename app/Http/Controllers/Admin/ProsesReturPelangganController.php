<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturPenjualan;
use App\Models\RiwayatPergerakanStok;
use App\Models\StokBarang;
use App\Models\DetailReturPenjualan;
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
            // SUMBER DATA: DetailReturPenjualan yang statusnya masih awal
            $query = DetailReturPenjualan::with([
                    'returPenjualan.pengguna', // Kasir awal
                    'alokasiAsal.stokBarang.supplier', // Asal supplier
                    'detailPenjualanAsal.produk' // Info produk
                ])
                ->whereIn('tindakan_lanjut', [
                    'DITERIMA_KEMBALI_PERLU_CEK',
                    'DITERIMA_LANGSUNG_RUSAK',
                ])
                ->select('detail_retur_penjualan.*');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('no_retur', fn($row) => $row->returPenjualan->nomor_retur ?? '-')
                ->addColumn('tgl_retur', fn($row) => Carbon::parse($row->returPenjualan->tanggal_retur)->isoFormat('D MMM YYYY, HH:mm'))
                
                // ### KOLOM BARU YANG INFORMATIF ###
                ->addColumn('produk_info', function($row){
                    $produk = $row->detailPenjualanAsal->produk;
                    $html = '<strong>' . ($produk->nama ?? 'N/A') . '</strong>';
                    if ($row->nomor_seri_diretur) {
                        $html .= '<br><small class="text-muted">SN: ' . e($row->nomor_seri_diretur) . '</small>';
                    }
                    return $html;
                })
                ->addColumn('supplier_asal', function($row){
                    return $row->alokasiAsal->stokBarang->supplier->nama ?? '<span class="text-muted">(Manual/N/A)</span>';
                })
                
                ->addColumn('alasan_awal', fn($row) => ucwords(strtolower(str_replace('_', ' ', $row->alasan_retur))))
                ->addColumn('kasir_awal', fn($row) => $row->returPenjualan->pengguna->nama ?? '-')
                ->addColumn('action', function($row){
                    // Tombol proses sekarang menunjuk ke ID detail_retur_penjualan
                    $url = route('admin.proses_retur_pelanggan.proses.form', $row->id);
                    return '<a href="' . $url . '" class="btn btn-primary btn-sm"><i class="bi bi-arrow-right-circle me-1"></i> Proses</a>';
                })
                ->rawColumns(['produk_info', 'supplier_asal', 'action'])
                ->make(true);
        }
        return view('admin.proses_retur_pelanggan.index');
    }


    /**
     * Menampilkan form untuk Admin memutuskan tindak lanjut akhir barang retur.
     */
    public function showProsesForm(DetailReturPenjualan $detailReturPenjualan)
    {
        // Validasi apakah item ini memang boleh diproses
        if (!in_array($detailReturPenjualan->tindakan_lanjut, ['DITERIMA_KEMBALI_PERLU_CEK', 'DITERIMA_LANGSUNG_RUSAK'])) {
            return redirect()->route('admin.proses_retur_pelanggan.index')->with('info', 'Item retur ini sudah diproses sebelumnya.');
        }

        // Eager load semua relasi yang dibutuhkan untuk SATU item ini
        $detailReturPenjualan->load([
            'returPenjualan.pengguna',
            'returPenjualan.penjualanAsal.pelanggan',
            'alokasiAsal.stokBarang.supplier',
            'detailPenjualanAsal.produk'
        ]);

        // Opsi tindakan Admin
        $tindakanAdminOptions = [
            'KEMBALI_KE_STOK_BAIK_ADMIN' => 'Tukar Baru (Kembalikan ke Stok Aktif)',
            'BARANG_SELESAI_SERVIS' => 'Servis Selesai (Siap Diambil)',
            'CATAT_SEBAGAI_STOK_RUSAK_FINAL' => 'Gagal Servis (Catat Rusak Final)',
            'AKAN_DIRETUR_KE_SUPPLIER' => 'Retur ke Supplier (Cacat Produksi)',
        ];
        
        // Kirim HANYA objek detail retur yang spesifik ini ke view
        return view('admin.proses_retur_pelanggan.proses_form', compact('detailReturPenjualan', 'tindakanAdminOptions'));
    }

    public function storeTindakanAdmin(Request $request, DetailReturPenjualan $detailReturPenjualan)
    {
        // 1. Validasi sekarang untuk input tunggal, bukan array
        $validated = $request->validate([
            'tindakan_admin' => ['required', 'string', Rule::in(['KEMBALI_KE_STOK_BAIK_ADMIN', 'BARANG_SELESAI_SERVIS', 'CATAT_SEBAGAI_STOK_RUSAK_FINAL', 'AKAN_DIRETUR_KE_SUPPLIER'])],
            'lokasi_tujuan_retur' => ['required_if:tindakan_admin,KEMBALI_KE_STOK_BAIK_ADMIN,AKAN_DIRETUR_KE_SUPPLIER,BARANG_SELESAI_SERVIS', 'string', 'nullable'],
            'harga_beli_baru' => ['required_if:tindakan_admin,KEMBALI_KE_STOK_BAIK_ADMIN', 'numeric', 'min:0', 'nullable'],
            'catatan_admin_proses' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Langsung bekerja dengan objek $detailReturPenjualan yang sudah di-inject
            
            // A. Update status & catatan item retur
            $detailReturPenjualan->tindakan_lanjut = $validated['tindakan_admin'];
            if (!empty($validated['catatan_admin_proses'])) {
                $catatanLama = $detailReturPenjualan->catatan_pelanggan ? $detailReturPenjualan->catatan_pelanggan . "\n" : "";
                $detailReturPenjualan->catatan_pelanggan = $catatanLama . "[ADMIN] " . $validated['catatan_admin_proses'];
            }
            $detailReturPenjualan->save();

            // B. Siapkan variabel
            $detailReturPenjualan->load('alokasiAsal.stokBarang.supplier', 'detailPenjualanAsal.produk', 'returPenjualan');
            $produk = $detailReturPenjualan->detailPenjualanAsal->produk;
            $stokBaru = null;
            $isStokAdded = !in_array($validated['tindakan_admin'], ['CATAT_SEBAGAI_STOK_RUSAK_FINAL', 'BARANG_SELESAI_SERVIS']);

            // C. Buat StokBarang baru jika perlu
            if ($isStokAdded) {
                $stokBarangAsal = $detailReturPenjualan->alokasiAsal->stokBarang ?? null;
                $idSupplierAsal = $stokBarangAsal->id_supplier ?? null;
                $hargaBeliAsal = $stokBarangAsal->harga_beli ?? 0;
                
                $kondisiStokBaru = ($validated['tindakan_admin'] === 'AKAN_DIRETUR_KE_SUPPLIER') ? 'AKAN_DIRETUR_SUPPLIER' : 'BAIK';

                $stokBaru = StokBarang::create([
                    'id_produk' => $produk->id,
                    'id_supplier' => $idSupplierAsal,
                    'harga_beli' => $validated['harga_beli_baru'] ?? $hargaBeliAsal,
                    'jumlah' => $detailReturPenjualan->jumlah_retur,
                    'diterima_at' => now(),
                    'kondisi' => $kondisiStokBaru,
                    'lokasi' => $validated['lokasi_tujuan_retur'],
                    'tipe_stok' => 'RETUR_DARI_PELANGGAN',
                ]);
            }
            
            // D. Catat di Riwayat Pergerakan Stok
            $saldoSebelumnya = RiwayatPergerakanStok::where('id_produk', $produk->id)->latest('id')->value('saldo_setelah_transaksi') ?? 0;
            $jumlahMasuk = $isStokAdded ? $detailReturPenjualan->jumlah_retur : 0;
            
            $keteranganRiwayat = 'Tindak Lanjut Admin untuk retur. Ref: ' . $detailReturPenjualan->returPenjualan->nomor_retur;
            if ($validated['tindakan_admin'] === 'BARANG_SELESAI_SERVIS') {
                $keteranganRiwayat = 'Barang selesai diservis, siap diambil. Ref: ' . $detailReturPenjualan->returPenjualan->nomor_retur;
            }

                RiwayatPergerakanStok::create([
                    'id_produk' => $produk->id,
                    'id_stok_barang_terkait' => $stokBaru ? $stokBaru->id : null,
                    'nomor_seri' => $detailReturPenjualan->nomor_seri_diretur,
                    'tipe_transaksi' => 'PROSES_RETUR_PELANGGAN',
                    'jumlah_masuk' => $jumlahMasuk,
                    'jumlah_keluar' => 0,
                    'saldo_setelah_transaksi' => $saldoSebelumnya + $jumlahMasuk,
                    'id_referensi' => $detailReturPenjualan->id,
                    'tipe_referensi' => get_class($detailReturPenjualan),
                    'tanggal_transaksi' => now(),
                    'keterangan' => $keteranganRiwayat,
                    'id_pengguna' => Auth::id(),
                ]);
            // E. Update status header Retur Pembelian jika semua item sudah diproses
        $returHeader = $detailReturPenjualan->returPenjualan;
        $sisaItemMenunggu = $returHeader->detailReturPenjualan()->whereIn('tindakan_lanjut', ['DITERIMA_KEMBALI_PERLU_CEK', 'DITERIMA_LANGSUNG_RUSAK'])->count();
        if ($sisaItemMenunggu === 0) {
            $returHeader->status_retur = 'SELESAI_DIPROSES';
            $returHeader->save();
        }

        DB::commit();
        return redirect()->route('admin.proses_retur_pelanggan.index')->with('success', 'Tindakan untuk item retur berhasil disimpan.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal memproses tindakan: ' . $e->getMessage());
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
            'pengguna', // Kasir yang memproses nota retur
            'penjualanAsal.pelanggan', // Nota penjualan asal & pelanggannya
            'detailReturPenjualan' => function ($query) {
                // Untuk setiap item di nota retur ini...
                $query->with([
                    'detailPenjualanAsal.produk' // ...muat item dari nota jual asal & info produknya
                ]);
            }
        ]);

        return view('admin.proses_retur_pelanggan.show', compact('returPenjualan'));
    }

    public function showNotaRetur(ReturPenjualan $returPenjualan)
    {
        // Eager load semua relasi yang dibutuhkan untuk nota
        $returPenjualan->load([
            'pengguna',
            'penjualanAsal.pelanggan',
            'detailReturPenjualan.detailPenjualanAsal.produk'
        ]);

        // Data statis toko
        $namaToko = "KINGSTAR ELEKTRONIK";
        $alamatToko = "Pasar Genteng Baru Lt. 2 Blok N no. 20, Surabaya";
        $teleponToko = "081290808046";

        // Kirim data ke view baru yang akan kita buat
        return view('admin.proses_retur_pelanggan.nota_retur', compact(
            'returPenjualan',
            'namaToko',
            'alamatToko',
            'teleponToko'
        ));
    }

}