<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk; // Import model Produk
use App\Models\StokBarang; // Mungkin diperlukan jika ingin query lebih kompleks
use Illuminate\Support\Facades\DB; // Untuk DB::raw jika diperlukan

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Mengambil produk yang statusnya aktif dan memiliki stok_minimum > 0
        $produks = Produk::where('status', true)
            ->where('stok_minimum', '>', 0) // Hanya produk yang punya setting stok minimum
            ->withCount(['stokBarang as total_stok_fisik' => function ($query) {
                $query->select(DB::raw('COALESCE(SUM(jumlah), 0)')) // Total stok fisik di semua batch
                      ->where('jumlah', '>', 0); // Hanya yang masih ada stoknya
            }])
            ->get();

        // Filter produk yang stok fisiknya <= stok minimumnya
        $produkStokRendah = $produks->filter(function ($produk) {
            $stokFisik = $produk->total_stok_fisik ?: 0; // Ambil hasil withCount
            // Stok dianggap rendah jika stok fisik lebih kecil atau sama dengan stok minimum
            // dan stok minimumnya memang diset (sudah difilter di query awal)
            return $stokFisik <= $produk->stok_minimum;
        });

        // Anda juga bisa mengambil data lain untuk dashboard di sini jika perlu
        // Misalnya: total penjualan hari ini, total pembelian bulan ini, dll.

        return view('admin.dashboard', compact('produkStokRendah'));
    }
}