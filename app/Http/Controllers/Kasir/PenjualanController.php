<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Pelanggan; 
use App\Models\Produk;  
use App\Models\StokBarang; // Import StokBarang untuk cek ketersediaan nanti

class PenjualanController extends Controller
{
    // ... (method create yang sudah ada) ...
    public function create()
    {
        $namaKasir = Auth::user()->nama;
        $tanggalSekarang = Carbon::now();

        $metodePembayaran = [
            'TUNAI' => 'Tunai', 'QRIS' => 'QRIS', 'TRANSFER_BCA' => 'Transfer BCA',
            'TRANSFER_MANDIRI' => 'Transfer Mandiri', 'DEBIT_BCA' => 'Debit BCA',
            'DEBIT_MANDIRI' => 'Debit Mandiri', 'KARTU_KREDIT' => 'Kartu Kredit',
        ];
        $kanalTransaksi = [
            'TOKO' => 'Toko Fisik', 'TOKOPEDIA' => 'Tokopedia (Manual)', 'SHOPEE' => 'Shopee (Manual)',
        ];
        $tipeTransaksi = [
            'BIASA' => 'Biasa', 'PRE_ORDER' => 'Pre-Order',
        ];

        return view('kasir.penjualan.create', compact(
            'namaKasir',
            'tanggalSekarang',
            'metodePembayaran',
            'kanalTransaksi',
            'tipeTransaksi'
        ));
    }


    /**
     * Handle AJAX request to search for customers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchPelangganAjax(Request $request)
    {
        $searchTerm = $request->input('q');
        $page = $request->input('page', 1);
        $limit = 15; // Jumlah item per halaman

        $query = Pelanggan::where('status', true) // Hanya pelanggan aktif
                          ->where(function ($q) use ($searchTerm) {
                              $q->where('nama', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('telepon', 'LIKE', "%{$searchTerm}%");
                          });

        $pelanggan = $query->orderBy('nama')
                           ->paginate($limit, ['id', 'nama', 'telepon'], 'page', $page);

        $results = $pelanggan->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->nama . ($item->telepon ? " ({$item->telepon})" : '')
            ];
        });

        return response()->json([
            'items' => $results,
            'total_count' => $pelanggan->total()
        ]);
    }

    /**
     * Handle AJAX request to search for products for sale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProdukAjax(Request $request)
    {
        $searchTerm = $request->input('q');
        $page = $request->input('page', 1);
        $limit = 15;

        $query = Produk::where('status', true) // Hanya produk aktif
                       ->where(function ($q) use ($searchTerm) {
                           $q->where('nama', 'LIKE', "%{$searchTerm}%")
                             ->orWhere('kode_produk', 'LIKE', "%{$searchTerm}%");
                       });
        
        // Jika ada parameter 'for_sale', kita bisa tambahkan filter
        // untuk produk yang memiliki stok (ini bisa jadi lebih kompleks,
        // untuk sekarang kita biarkan dulu, fokus di pencarian nama/kode)
        // if ($request->has('for_sale')) {
        //     $query->whereHas('stokBarang', function($qStok){
        //         $qStok->where('jumlah', '>', 0)->where('kondisi', 'BAIK'); // Contoh filter stok
        //     });
        // }


        $produk = $query->orderBy('nama')
                        ->paginate($limit, ['id', 'nama', 'kode_produk', 'harga_jual_standart', 'memiliki_serial'], 'page', $page);

        $results = $produk->map(function ($item) {
            // Cek ketersediaan stok sederhana (bisa dioptimalkan nanti)
            // $stokTersedia = StokBarang::where('id_produk', $item->id)
            //                             ->where('jumlah', '>', 0)
            //                             ->where('kondisi', 'BAIK') // Hanya stok kondisi baik
            //                             ->sum('jumlah');
            return [
                'id' => $item->id,
                'text' => $item->nama . ($item->kode_produk ? " ({$item->kode_produk})" : ''),
                'harga_jual_standar' => $item->harga_jual_standart,
                'memiliki_serial' => (bool) $item->memiliki_serial,
                // 'stok_tersedia' => $stokTersedia // Info stok bisa diambil saat pemilihan batch
            ];
        });

        return response()->json([
            'items' => $results,
            'total_count' => $produk->total()
        ]);
    }

    // ... (method store akan dibuat nanti) ...
}