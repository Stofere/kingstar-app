<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;

// Controller Dasar
use App\Http\Controllers\HomeController;

// Admin Controllers
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ProdukController as AdminProdukController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\MerkController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PelangganController;
use App\Http\Controllers\Admin\PembelianController;
use App\Http\Controllers\Admin\AdminPesanBarangController; 
use App\Http\Controllers\Admin\LaporanPenjualanController;
use App\Http\Controllers\Admin\LaporanPembelianController;
use App\Http\Controllers\Admin\ReturPembelianController;
use App\Http\Controllers\Admin\LaporanStokController;
use App\Http\Controllers\Admin\ProsesReturPelangganController;
use App\Http\Controllers\Admin\KonsinyasiController;
use App\Http\Controllers\Admin\PenyesuaianStokController;
use App\Http\Controllers\Admin\LaporanHargaBeliController;


// Kasir Controllers
use App\Http\Controllers\Kasir\KasirDashboardController;
use App\Http\Controllers\Kasir\PenjualanController as KasirPenjualanController;
use App\Http\Controllers\Kasir\KasirPesanBarangController; 
use App\Http\Controllers\Kasir\ReturPenjualanController;

// Gudang Controllers
use App\Http\Controllers\Gudang\GudangDashboardController;
use App\Http\Controllers\Gudang\PenerimaanController as GudangPenerimaanController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // ==================
    // GRUP RUTE ADMIN
    // ==================
    Route::middleware(['role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('pengguna', PenggunaController::class);
        Route::resource('produk', AdminProdukController::class);
        Route::resource('merk', MerkController::class);
        Route::resource('supplier', SupplierController::class);
        Route::resource('pelanggan', PelangganController::class);
        Route::resource('pembelian', PembelianController::class);
        Route::post('pembelian/{pembelian}/lunasi', [PembelianController::class, 'lunasiPembayaran'])->name('pembelian.lunasi');
        Route::resource('retur-pembelian', ReturPembelianController::class);
        
        // Rute untuk Admin Kelola Alokasi Pesan Barang
        Route::prefix('pesan-barang-alokasi')->name('pesan_barang_alokasi.')->group(function () {
            Route::get('/', [AdminPesanBarangController::class, 'index'])->name('index');
            Route::get('/{penjualan}/form', [AdminPesanBarangController::class, 'showAlokasiForm'])->name('form');
            Route::post('/{penjualan}/store', [AdminPesanBarangController::class, 'storeAlokasi'])->name('store');
            Route::get('/ajax/available-batches', [AdminPesanBarangController::class, 'getAdminAvailableBatchesAjax'])->name('ajax.available_batches');
            Route::get('/ajax/available-serials', [AdminPesanBarangController::class, 'getAdminAvailableSerialsAjax'])->name('ajax.available_serials');
        });

        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/penjualan', [LaporanPenjualanController::class, 'index'])->name('penjualan.index');
            Route::get('/penjualan/data', [LaporanPenjualanController::class, 'getPenjualanData'])->name('penjualan.data'); // Untuk DataTables server-side
            Route::get('/pembelian', [LaporanPembelianController::class,'index'])->name('pembelian.index');
            Route::get('/pembelian/data', [LaporanPembelianController::class, 'getPembelianData'])->name('pembelian.data'); // Untuk DataTables server-side

            Route::get('/analisis-harga-beli', [\App\Http\Controllers\Admin\LaporanHargaBeliController::class, 'index'])->name('harga_beli.index');
        
            // Halaman detail riwayat per produk
            Route::get('/analisis-harga-beli/{produk}', [\App\Http\Controllers\Admin\LaporanHargaBeliController::class, 'show'])->name('harga_beli.show');
            
            // --- Laporan Stok ---
            // Grup prefix 'stok' dengan nama grup 'stok.' agar menghasilkan admin.laporan.stok.
            Route::prefix('stok')->name('stok.')->group(function () {

                Route::get('/ringkasan-produk', [LaporanStokController::class, 'ringkasanProduk'])->name('ringkasan_produk');

                Route::get('/detail-batch/{produk}', [LaporanStokController::class, 'detailBatchProduk'])->name('detail_batch_produk');

                Route::get('/kartu-stok/{produk}', [LaporanStokController::class, 'generateKartuStok'])->name('kartu_stok.data');

                Route::get('/lacak-nomor-seri', [LaporanStokController::class, 'showLacakNomorSeriForm'])->name('lacak_nomor_seri.form');
                Route::post('/lacak-nomor-seri', [LaporanStokController::class, 'getLacakNomorSeriResult'])->name('lacak_nomor_seri.result');
            });
            
            
        });

        // === RETUR PEMBELIAN ===
        Route::prefix('retur-pembelian')->name('retur_pembelian.')->group(function () {
            Route::get('/', [ReturPembelianController::class, 'index'])->name('index');
            Route::get('/create', [ReturPembelianController::class, 'create'])->name('create');
            Route::post('/', [ReturPembelianController::class, 'store'])->name('store');
            Route::get('/{returPembelian}', [ReturPembelianController::class, 'show'])->name('show');
            Route::get('/{returPembelian}/edit', [ReturPembelianController::class, 'edit'])->name('edit');
            Route::put('/{returPembelian}', [ReturPembelianController::class, 'update'])->name('update');
            // AJAX Endpoints
            Route::get('/ajax/search-batch-stok', [ReturPembelianController::class, 'searchBatchStokAjax'])->name('ajax.search_batch_stok');
            Route::get('/ajax/get-serials-from-batch', [ReturPembelianController::class, 'getSerialsFromBatchAjax'])->name('ajax.get_serials_from_batch');
                    
        });

        Route::prefix('proses-retur-pelanggan')->name('proses_retur_pelanggan.')->group(function () {
            Route::get('/', [ProsesReturPelangganController::class, 'index'])->name('index'); // Daftar retur menunggu tindakan Admin
            Route::get('/{returPenjualan}/proses', [ProsesReturPelangganController::class, 'showProsesForm'])->name('proses.form'); // Form untuk Admin memutuskan tindak lanjut
            Route::post('/{returPenjualan}/store-tindakan', [ProsesReturPelangganController::class, 'storeTindakanAdmin'])->name('store.tindakan'); // Menyimpan keputusan Admin
            Route::get('/{returPenjualan}/show', [ProsesReturPelangganController::class, 'showDetail'])->name('show');
        });

        // --- ROUTE UNTUK FITUR KONSINYASI ---
        Route::get('/konsinyasi/input-harga', [KonsinyasiController::class, 'showInputHargaForm'])->name('konsinyasi.input_harga.form');
        Route::post('/konsinyasi/input-harga', [KonsinyasiController::class, 'storeInputHarga'])->name('konsinyasi.input_harga.store');
        // --- ROUTE BARU UNTUK LAPORAN KONSINYASI ---
        Route::get('/laporan/konsinyasi', [KonsinyasiController::class, 'showLaporanKonsinyasi'])->name('laporan.konsinyasi.index');
        // --- ROUTE BARU UNTUK PENYESUAIAN STOK ---
        Route::get('/penyesuaian-stok/create', [PenyesuaianStokController::class, 'create'])->name('penyesuaian_stok.create');
        Route::post('/penyesuaian-stok', [PenyesuaianStokController::class, 'store'])->name('penyesuaian_stok.store');

        

        // AJAX umum Admin
        Route::get('/ajax/produk-pembelian/{id}', [PembelianController::class, 'getProdukDetailAjax'])->name('ajax.produk-pembelian.detail');
        Route::get('/ajax/produk/search', [AdminProdukController::class, 'searchAjax'])->name('ajax.produk.search'); // Bisa digunakan oleh berbagai modul Admin
        Route::get('/ajax/supplier/search', [SupplierController::class, 'searchAjax'])->name('ajax.supplier.search');
        Route::get('/ajax/pembelian/generate-number', [PembelianController::class, 'generateNextNumberAjax'])->name('ajax.pembelian.generate_number');
    });

    // ==================
    // GRUP RUTE KASIR
    // ==================
    Route::middleware(['role:KASIR,ADMIN'])->prefix('kasir')->name('kasir.')->group(function () {
        Route::get('/dashboard', [KasirDashboardController::class, 'index'])->name('dashboard');

        // Penjualan Biasa dan Pesan Barang Awal
        Route::get('/penjualan/create', [KasirPenjualanController::class, 'create'])->name('penjualan.create');
        Route::post('/penjualan', [KasirPenjualanController::class, 'store'])->name('penjualan.store');
        Route::get('/penjualan/nota/{id}', [KasirPenjualanController::class, 'showNota'])->name('penjualan.nota');

        // Rute untuk Kasir Menyelesaikan Pesan Barang (Pelunasan & Pengambilan)
        Route::prefix('pesan-barang-selesai')->name('pesan_barang_selesai.')->group(function () {
            Route::get('/', [KasirPesanBarangController::class, 'index'])->name('index'); 
            Route::get('/{penjualan}/form', [KasirPesanBarangController::class, 'showSelesaikanForm'])->name('form');
            Route::post('/{penjualan}/store', [KasirPesanBarangController::class, 'storeSelesaikan'])->name('store');
        });

        // ===>>> RUTE UNTUK RETUR PENJUALAN (TERBARU) <<<===
        Route::prefix('retur-penjualan')->name('retur_penjualan.')->group(function () {
            // Langkah 1: Menampilkan daftar retur yang sudah ada
            Route::get('/', [ReturPenjualanController::class, 'index'])->name('index');

            // Langkah 2: Form untuk mencari transaksi penjualan yang akan diretur
            Route::get('/cari-transaksi', [ReturPenjualanController::class, 'showCariTransaksiForm'])->name('cari_transaksi');

            // Langkah 3: AJAX endpoint untuk mendapatkan detail transaksi berdasarkan nomor nota
            Route::get('/ajax/get-transaksi-detail', [ReturPenjualanController::class, 'getTransaksiDetailAjax'])->name('ajax.get_transaksi_detail');

            // Langkah 4: Menampilkan form input detail retur setelah transaksi dipilih
            // Menggunakan route model binding untuk Penjualan
            Route::get('/form/{penjualan}', [ReturPenjualanController::class, 'showReturForm'])->name('form');

            // Langkah 5: Menyimpan data retur
            Route::post('/store/{penjualan}', [ReturPenjualanController::class, 'storeRetur'])->name('store');

            // Langkah 6: Menampilkan detail satu retur penjualan yang sudah dibuat
            // Menggunakan route model binding untuk ReturPenjualan
            Route::get('/show/{returPenjualan}', [ReturPenjualanController::class, 'show'])->name('show');

            // Langkah 7: Menampilkan form untuk menyelesaikan penukaran barang
            Route::get('/{returPenjualan}/selesaikan-penukaran', [ReturPenjualanController::class, 'showSelesaikanForm'])->name('selesaikan.form');

            // Langkah 8: Menyimpan proses penyelesaian penukaran
            Route::post('/{returPenjualan}/store-penukaran', [ReturPenjualanController::class, 'storeSelesaikanPenukaran'])->name('selesaikan.store');

            // --- ROUTE UNTUK MEMPROSES ITEM YANG DIPILIH ---
            Route::post('/pilih-item', [ReturPenjualanController::class, 'processSelectedItems'])->name('pilih_item_proses');

            Route::get('/form/{penjualan}', [ReturPenjualanController::class, 'showReturForm'])->name('form');
        });
        

        // AJAX untuk Penjualan Kasir
        Route::get('/ajax/pelanggan/search', [KasirPenjualanController::class, 'searchPelangganAjax'])->name('ajax.pelanggan.search');
        Route::get('/ajax/produk/search', [KasirPenjualanController::class, 'searchProdukAjax'])->name('ajax.produk.search'); 
        Route::get('/ajax/stok/available', [KasirPenjualanController::class, 'getAvailableStockAjax'])->name('ajax.stok.available');
        Route::get('/ajax/stok/serials', [KasirPenjualanController::class, 'getAvailableSerialsAjax'])->name('ajax.stok.serials');
    });

    // ==================
    // GRUP RUTE GUDANG
    // ==================
    Route::middleware(['role:GUDANG,ADMIN'])->prefix('gudang')->name('gudang.')->group(function () {
        Route::get('/dashboard', [GudangDashboardController::class, 'index'])->name('dashboard');

        Route::get('/penerimaan', [GudangPenerimaanController::class, 'index'])->name('penerimaan.index');
        Route::get('/penerimaan/create/{pembelian?}', [GudangPenerimaanController::class, 'create'])->name('penerimaan.create');
        Route::post('/penerimaan', [GudangPenerimaanController::class, 'store'])->name('penerimaan.store');
        
        Route::prefix('stok-opname')->name('stok-opname.')->group(function() {
            Route::get('/', [\App\Http\Controllers\Admin\StokOpnameController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\StokOpnameController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\StokOpnameController::class, 'store'])->name('store');
            Route::get('/{stokOpname}', [\App\Http\Controllers\Admin\StokOpnameController::class, 'show'])->name('show');
            // SATU ROUTE UNTUK MENYELESAIKAN DAN MENYESUAIKAN
            Route::post('/{stokOpname}/finish', [\App\Http\Controllers\Admin\StokOpnameController::class, 'finishAndAdjust'])->name('finish_and_adjust');
        });
        // Fitur Cek Stok untuk Gudang
        Route::prefix('stok')->name('stok.')->group(function () { // Membuat sub-grup 'stok' agar lebih rapi
            Route::get('/cek', [GudangDashboardController::class, 'showCekStokForm'])->name('cek_form');
            // Nama route: gudang.stok.cek_form
            // URL: /gudang/stok/cek

            Route::get('/ajax/search-produk', [GudangDashboardController::class, 'searchProdukStokAjaxGudang'])->name('ajax_search_produk_gudang');
            // Nama route menjadi: gudang.stok.ajax_search_produk_gudang
        });
    });
    Route::get('/ajax/produk-penerimaan/search', [GudangPenerimaanController::class, 'searchProdukForPenerimaanAjax'])->name('gudang.ajax.produk.search');
            Route::prefix('perpindahan-stok')->name('perpindahan-stok.')->group(function () {
            // Halaman utama untuk menampilkan riwayat perpindahan
            Route::get('/', [\App\Http\Controllers\Admin\PerpindahanStokController::class, 'index'])->name('index');
            // Halaman form untuk membuat perpindahan baru
            Route::get('/create', [\App\Http\Controllers\Admin\PerpindahanStokController::class, 'create'])->name('create');
            // Aksi untuk menyimpan perpindahan
            Route::post('/', [\App\Http\Controllers\Admin\PerpindahanStokController::class, 'store'])->name('store');
            // AJAX untuk mencari batch stok
            Route::get('/ajax/search-batch', [\App\Http\Controllers\Admin\PerpindahanStokController::class, 'searchBatchAjax'])->name('ajax.search-batch');
            
            Route::get('/ajax/get-serials', [\App\Http\Controllers\Admin\PerpindahanStokController::class, 'getSerialsFromBatch'])->name('ajax.get-serials');

        });
});