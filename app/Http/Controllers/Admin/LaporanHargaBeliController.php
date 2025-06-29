<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\DetailPembelian;
use Illuminate\Http\Request;

class LaporanHargaBeliController extends Controller
{
    /**
     * Menampilkan halaman ringkasan analisis harga beli.
     */
    public function index()
    {
        // Query yang efisien untuk mendapatkan semua data ringkasan
        $produks = Produk::where('status', true)
            ->with([
                // Memuat relasi 'detailPembelianTerbaru' yang sudah kita buat
                // dan juga relasi turunan 'pembelian.supplier'
                'detailPembelianTerbaru.pembelian.supplier'
            ])
            // Menambahkan kolom agregat virtual ke dalam hasil query
            ->withAvg('detailPembelian as harga_rata_rata', 'harga_beli')
            ->withMin('detailPembelian as harga_terendah', 'harga_beli')
            ->withMax('detailPembelian as harga_tertinggi', 'harga_beli')
            ->paginate(25); // Gunakan paginate agar tidak berat jika produk banyak

        return view('admin.laporan.harga_beli.index', compact('produks'));
    }

    /**
     * Menampilkan halaman detail riwayat harga beli untuk satu produk.
     */
    public function show(Produk $produk)
    {
        // ## FIX: Ubah orderBy dari 'desc' menjadi 'asc' ##
        // Ambil semua riwayat pembelian, diurutkan dari yang PALING LAMA ke PALING BARU.
        $riwayatHarga = DetailPembelian::where('id_produk', $produk->id)
            ->with('pembelian.supplier') // Eager load PO dan Supplier
            ->orderBy('created_at', 'asc') // Urutkan dari terlama
            ->get(); // Ambil semua, jangan paginate di sini agar perbandingan akurat

        return view('admin.laporan.harga_beli.show', compact('produk', 'riwayatHarga'));
    }
}