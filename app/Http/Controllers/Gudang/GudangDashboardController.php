<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Models\StokBarang;
use App\Models\Produk; // Untuk cek stok habis
use App\Models\StokOpname; // Untuk info stok opname
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GudangDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Daftar PO menunggu diterima (sama seperti sebelumnya)
        $poMenungguDiterima = Pembelian::whereIn('status_pembelian', ['DIPESAN', 'PENGIRIMAN', 'TIBA_SEBAGIAN', 'BARANG_PENGGANTI_RETUR'])
            ->whereHas('detailPembelian', function ($q) {
                $q->whereRaw('jumlah > jumlah_diterima');
            })
            ->with('supplier')
            ->orderBy('tanggal_pembelian', 'asc')
            ->limit(5)
            ->get();
        $jumlahPoMenunggu = Pembelian::whereIn('status_pembelian', ['DIPESAN', 'PENGIRIMAN', 'TIBA_SEBAGIAN', 'BARANG_PENGGANTI_RETUR'])
            ->whereHas('detailPembelian', function ($q) {
                $q->whereRaw('jumlah > jumlah_diterima');
            })
            ->count();

        // 2. Jumlah barang yang diterima hari ini (sama seperti sebelumnya)
        // Asumsi 'jumlah' di StokBarang adalah jumlah yang diterima saat itu untuk batch tersebut,
        // atau Anda punya kolom 'kuantitas_awal_diterima'
        $jumlahBarangDiterimaHariIni = StokBarang::whereDate('diterima_at', $today)
                                        ->sum('jumlah');

        // 3. Notifikasi produk habis stok di GUDANG dan TOKO
        // Definisikan lokasi yang ingin dicek
        $lokasiUntukCekHabis = ['GUDANG', 'GUDANG_UTAMA', 'TOKO']; 

        // Ambil produk yang aktif, lalu hitung total stoknya di lokasi yang ditentukan
        // dan filter yang total stoknya <= 0
        $produkDenganStok = Produk::where('status', true)
            ->withSum(['stokBarang as total_stok_di_lokasi_terpantau' => function ($query) use ($lokasiUntukCekHabis) {
                $query->whereIn('lokasi', $lokasiUntukCekHabis)->where('jumlah', '>', 0); // Hanya hitung batch yang masih ada stoknya
            }], 'jumlah') // SUM dari kolom 'jumlah' di stok_barang
            ->get();

        // Filter produk yang total stok di lokasi terpantau adalah 0 atau null (jika tidak ada batch sama sekali)
        $produkHabisDiLokasiTerpantauCollection = $produkDenganStok->filter(function ($produk) {
            return ($produk->total_stok_di_lokasi_terpantau ?? 0) <= 0;
        });

        $produkHabisDiGudangDanToko = $produkHabisDiLokasiTerpantauCollection
            ->take(5) // Batasi jumlah produk yang ditampilkan di notifikasi
            ->pluck('nama')
            ->implode(', ');

        $adaProdukHabis = $produkHabisDiLokasiTerpantauCollection->isNotEmpty();


        // 4. Info Stok Opname (sama seperti sebelumnya)
        $opnameAktif = StokOpname::where('status', 'BERJALAN')
                                 ->orderBy('started_at', 'desc')
                                 ->first();
        $opnameTerakhirSelesai = null;
        if (!$opnameAktif) {
            $opnameTerakhirSelesai = StokOpname::where('status', 'SELESAI')
                                            ->orderBy('finished_at', 'desc')
                                            ->first();
        }

        return view('gudang.dashboard', compact(
            'poMenungguDiterima',
            'jumlahPoMenunggu',
            'jumlahBarangDiterimaHariIni',
            'produkHabisDiGudangDanToko', // Ganti nama variabel
            'adaProdukHabis',
            'opnameAktif',
            'opnameTerakhirSelesai'
        ));
    }

    /**
     * Menampilkan form untuk cek stok gudang.
     */
    public function showCekStokForm()
    {
        return view('gudang.stok.cek_stok');
    }

    /**
     * AJAX untuk mencari produk dan menampilkan stoknya di lokasi gudang (dan toko).
     */
    public function searchProdukStokAjaxGudang(Request $request)
    {
        // ... (Logika method ini seperti yang sudah kita buat di respons sebelumnya) ...
        // ... (Pastikan menggunakan $lokasiYangDiminatiGudang = ['GUDANG', 'GUDANG_UTAMA', 'TOKO']; )
        $searchTerm = $request->input('q', '');
        $limit = $request->input('limit', 10);

        if (empty($searchTerm)) {
            return response()->json(['items' => []]);
        }
        $lokasiYangDiminatiGudang = ['GUDANG', 'GUDANG_UTAMA', 'TOKO'];

        $produks = Produk::where('status', true)
            ->where(function ($query) use ($searchTerm) {
                $query->where('nama', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('kode_produk', 'LIKE', "%{$searchTerm}%");
            })
            ->with(['stokBarang' => function($queryStok) use ($lokasiYangDiminatiGudang) {
                $queryStok->where('jumlah', '>', 0)
                          ->whereIn('lokasi', $lokasiYangDiminatiGudang)
                          ->orderBy('lokasi', 'asc')
                          ->orderBy('diterima_at', 'asc');
            }])
            ->limit($limit)
            ->get();

        $results = $produks->map(function ($produk) use ($lokasiYangDiminatiGudang) {
            $totalStokDiSemuaLokasiDiminati = $produk->stokBarang
                                            ->whereIn('lokasi', $lokasiYangDiminatiGudang)
                                            ->sum('jumlah');
            $detailBatchDiSemuaLokasiDiminati = $produk->stokBarang
                                        ->whereIn('lokasi', $lokasiYangDiminatiGudang)
                                        ->map(function($batch){
                                            return [
                                                'id_batch' => $batch->id,
                                                'sisa_stok_batch' => $batch->jumlah,
                                                'lokasi_batch' => $batch->lokasi,
                                                'diterima_at_batch' => Carbon::parse($batch->diterima_at)->isoFormat('D MMM YYYY'),
                                                'kondisi_batch' => ucwords(str_replace('_', ' ', $batch->kondisi)),
                                            ];
                                        })->values()->all();
            return [
                'id_produk' => $produk->id,
                'nama_produk_lengkap' => $produk->nama . ($produk->kode_produk ? " ({$produk->kode_produk})" : ""),
                'satuan' => $produk->satuan,
                'total_stok_di_lokasi_diminati' => $totalStokDiSemuaLokasiDiminati,
                'stok_minimum' => $produk->stok_minimum ?: 0,
                'detail_batch_di_lokasi_diminati' => $detailBatchDiSemuaLokasiDiminati,
                'memiliki_serial' => (bool) $produk->memiliki_serial,
            ];
        });
        return response()->json(['items' => $results]);
    }
}