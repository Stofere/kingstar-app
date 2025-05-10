<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Pelanggan; 
use App\Models\Produk;  
use App\Models\StokBarang; // Import StokBarang untuk cek ketersediaan nanti
use App\Models\LogNomorSeri;

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

    /**
     * Handle AJAX request to get available batches/serials for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableBatchesAjax(Request $request)
    {
        $idProduk = $request->input('id_produk');
        $qtyDibutuhkan = (int) $request->input('qty_dibutuhkan', 1);

        if (!$idProduk || $qtyDibutuhkan <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak valid'
            ], 400);
        }

        // Ambil stok yang tersedia untuk produk ini, urutkan berdasarkan FIFO
        $stokTersedia = StokBarang::where('id_produk', $idProduk)
            ->where('jumlah', '>', 0)
            ->where('kondisi', 'BAIK')
            ->where('tipe_stok', 'REGULER') // Hanya stok reguler, bukan konsinyasi
            ->orderBy('diterima_at', 'ASC') // FIFO: barang masuk pertama, keluar pertama
            ->get();

        $results = [];
        foreach ($stokTersedia as $stok) {
            $batch = [
                'id' => $stok->id,
                'diterima_at' => $stok->diterima_at->format('d M Y'),
                'jumlah_tersedia' => $stok->jumlah,
                'harga_beli' => $stok->harga_beli,
                'tipe_garansi' => $stok->tipe_garansi,
                'nomor_seri' => []
            ];

            // Jika produk memiliki serial, ambil nomor seri yang tersedia
            if ($stok->produk->memiliki_serial) {
                $nomorSeri = LogNomorSeri::where('id_stok_barang_asal', $stok->id)
                    ->where('status_log', 'DITERIMA')
                    ->pluck('nomor_seri')
                    ->toArray();
                $batch['nomor_seri'] = $nomorSeri;
            }

            $results[] = $batch;
        }

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
    // ... (method store akan dibuat nanti) ...
}