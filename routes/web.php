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


// Kasir Controllers
use App\Http\Controllers\Kasir\KasirDashboardController;
use App\Http\Controllers\Kasir\PenjualanController as KasirPenjualanController;
use App\Http\Controllers\Kasir\KasirPesanBarangController; 

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

        // Rute untuk Admin Kelola Alokasi Pesan Barang
        Route::prefix('pesan-barang-alokasi')->name('pesan_barang_alokasi.')->group(function () {
            Route::get('/', [AdminPesanBarangController::class, 'index'])->name('index');
            Route::get('/{penjualan}/form', [AdminPesanBarangController::class, 'showAlokasiForm'])->name('form');
            Route::post('/{penjualan}/store', [AdminPesanBarangController::class, 'storeAlokasi'])->name('store');
            // AJAX untuk Admin (bisa diletakkan di sini atau di grup AJAX global jika ada)
            Route::get('/ajax/available-batches', [AdminPesanBarangController::class, 'getAdminAvailableBatchesAjax'])->name('ajax.available_batches');
            Route::get('/ajax/available-serials', [AdminPesanBarangController::class, 'getAdminAvailableSerialsAjax'])->name('ajax.available_serials');
        });

        Route::prefix('laporan')->name('laporan.')->group(function () {
             
            Route::get('/penjualan', [LaporanPenjualanController::class, 'index'])->name('penjualan.index');
            Route::get('/penjualan/data', [LaporanPenjualanController::class, 'getPenjualanData'])->name('penjualan.data'); // Untuk DataTables server-side

            Route::get('/pembelian', [LaporanPembelianController::class,'index'])->name('pembelian.index');
            Route::get('/pembelian/data', [LaporanPembelianController::class, 'getPembelianData'])->name('pembelian.data'); // Untuk DataTables server-side
        });



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
            Route::get('/', [KasirPesanBarangController::class, 'index'])->name('index'); // Daftar pesanan menunggu pelunasan/pengambilan
            Route::get('/{penjualan}/form', [KasirPesanBarangController::class, 'showSelesaikanForm'])->name('form');
            Route::post('/{penjualan}/store', [KasirPesanBarangController::class, 'storeSelesaikan'])->name('store');
        });

        // AJAX untuk Penjualan Kasir
        Route::get('/ajax/pelanggan/search', [KasirPenjualanController::class, 'searchPelangganAjax'])->name('ajax.pelanggan.search');
        Route::get('/ajax/produk/search', [KasirPenjualanController::class, 'searchProdukAjax'])->name('ajax.produk.search'); // Ini bisa bentrok dengan AJAX Admin jika tidak hati-hati
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
    });
});