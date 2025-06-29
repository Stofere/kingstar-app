<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StokBarang;
use App\Models\DetailPenjualan;
use App\Models\Pembelian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KonsinyasiController extends Controller
{
    /**
     * Menampilkan form untuk admin menginput harga beli barang konsinyasi.
     */
    public function showInputHargaForm()
    {
        // Ambil semua batch konsinyasi yang harganya masih 0 dan stoknya ada.
        $batchesMenungguHarga = StokBarang::with(['produk', 'supplier'])
                                ->where('tipe_stok', 'KONSINYASI')
                                ->where('harga_beli', '<=', 0) // Gunakan <= 0 untuk keamanan
                                ->where('jumlah', '>', 0)
                                ->orderBy('diterima_at', 'desc')
                                ->get();

        return view('admin.konsinyasi.input_harga_form', compact('batchesMenungguHarga'));
    }

    /**
     * Menyimpan harga beli yang diinput oleh admin.
     */
    public function storeInputHarga(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'batches' => 'required|array',
            'batches.*.id' => 'required|integer|exists:stok_barang,id',
            'batches.*.harga_beli' => 'required|numeric|min:1', // Harga harus lebih dari 0
        ], [
            'batches.*.harga_beli.required' => 'Harga beli wajib diisi untuk setiap batch.',
            'batches.*.harga_beli.min' => 'Harga beli harus lebih dari 0.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            foreach ($request->batches as $batchData) {
                $batch = StokBarang::find($batchData['id']);

                // Double check untuk keamanan
                if ($batch && $batch->tipe_stok === 'KONSINYASI' && $batch->harga_beli <= 0) {
                    $batch->harga_beli = $batchData['harga_beli'];
                    $batch->save();
                }
            }

            DB::commit();
            return redirect()->route('admin.konsinyasi.input_harga.form')
                             ->with('success', 'Harga beli untuk barang konsinyasi berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan harga: ' . $e->getMessage());
        }
    }

/**
     * Menampilkan halaman laporan terpusat untuk barang konsinyasi.
     */
    public function showLaporanKonsinyasi(Request $request)
    {
        // 1. Data untuk Tab "Barang Terjual & Belum Lunas"
        // Kita cari semua PO yang dibuat secara otomatis dari penjualan konsinyasi
        // dan status pembayarannya BELUM LUNAS.
        $poKonsinyasiBelumLunas = Pembelian::with(['supplier', 'detailPembelian.produk'])
            ->where('nomor_pembelian', 'LIKE', 'POC-%') // Filter berdasarkan prefix unik
            ->where('status_pembayaran', 'BELUM_LUNAS')
            ->orderBy('tanggal_pembelian', 'desc')
            ->get();

        // 2. Data untuk Tab "Stok Tersedia"
        // Kita cari semua batch stok konsinyasi yang masih ada jumlahnya.
        $stokKonsinyasiTersedia = StokBarang::with(['produk', 'supplier'])
            ->where('tipe_stok', 'KONSINYASI')
            ->where('jumlah', '>', 0)
            ->orderBy('diterima_at', 'asc')
            ->get();

        return view('admin.konsinyasi.laporan_index', compact(
            'poKonsinyasiBelumLunas',
            'stokKonsinyasiTersedia'
        ));
    }
}
