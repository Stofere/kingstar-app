<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth; // Import Auth
use App\Http\Controllers\Auth\LoginController;

// Controller Dasar (buat controllernya nanti)
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ProdukController as AdminProdukController;
use App\Http\Controllers\Admin\PenggunaController; // Contoh Controller Admin
// ... (tambahkan controller lain sesuai kebutuhan)
use App\Http\Controllers\Kasir\KasirDashboardController;
use App\Http\Controllers\Kasir\PenjualanController as KasirPenjualanController;
// ... (tambahkan controller lain sesuai kebutuhan)
use App\Http\Controllers\Gudang\GudangDashboardController;
use App\Http\Controllers\Gudang\PenerimaanController as GudangPenerimaanController;
// ... (tambahkan controller lain sesuai kebutuhan)


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Halaman Landing Page (jika ada, bisa diakses publik)
Route::get('/', function () {
    // return view('welcome'); // View bawaan Laravel
    // Atau redirect ke login jika tidak ada landing page publik
     return redirect()->route('login');
});


Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Rute setelah login (membutuhkan autentikasi)
// Middleware 'auth' memastikan hanya user yang sudah login bisa akses
Route::middleware(['auth'])->group(function () {

    // Dashboard Umum (mungkin redirect ke dashboard spesifik role)
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // ==================
    // GRUP RUTE ADMIN
    // ==================
    // Middleware 'role:ADMIN' memastikan hanya user dengan role 'ADMIN' bisa akses
    Route::middleware(['role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Contoh CRUD Pengguna
        Route::resource('pengguna', PenggunaController::class);
        // Contoh CRUD Produk (oleh Admin)
        Route::resource('produk', AdminProdukController::class);
        // ... (Rute Admin lainnya: Supplier, Merk, Pelanggan, Pembelian, Laporan, dll.)

    });

    // ==================
    // GRUP RUTE KASIR
    // ==================
    // Middleware 'role:KASIR,ADMIN' memastikan KASIR dan ADMIN bisa akses
    // (Admin mungkin perlu akses ke fitur kasir untuk supervisi/bantuan)
    Route::middleware(['role:KASIR,ADMIN'])->prefix('kasir')->name('kasir.')->group(function () {
        Route::get('/dashboard', [KasirDashboardController::class, 'index'])->name('dashboard');

        // Contoh Penjualan (oleh Kasir)
        Route::get('/penjualan/baru', [KasirPenjualanController::class, 'create'])->name('penjualan.create');
        Route::post('/penjualan', [KasirPenjualanController::class, 'store'])->name('penjualan.store');
        // ... (Rute Kasir lainnya: Lihat transaksi hari ini, Retur Penjualan, dll.)

        // Contoh AJAX Endpoint untuk Penjualan (harus tetap diproteksi middleware)
        Route::get('/ajax/produk/search', [KasirPenjualanController::class, 'searchProdukAjax'])->name('ajax.produk.search');
        Route::get('/ajax/stok/available', [KasirPenjualanController::class, 'getAvailableStockAjax'])->name('ajax.stok.available');
        Route::get('/ajax/stok/serials', [KasirPenjualanController::class, 'getAvailableSerialsAjax'])->name('ajax.stok.serials'); // Single Batch
        Route::post('/ajax/stok/serials-multi', [KasirPenjualanController::class, 'getAvailableSerialsMultiBatchAjax'])->name('ajax.stok.serials-multi'); // Multi Batch
        Route::get('/ajax/pelanggan/search', [KasirPenjualanController::class, 'searchPelangganAjax'])->name('ajax.pelanggan.search');
        Route::post('/ajax/pelanggan/quick-store', [KasirPenjualanController::class, 'quickStorePelangganAjax'])->name('ajax.pelanggan.quickstore');


    });

    // ==================
    // GRUP RUTE GUDANG
    // ==================
    // Middleware 'role:GUDANG,ADMIN' memastikan GUDANG dan ADMIN bisa akses
    Route::middleware(['role:GUDANG,ADMIN'])->prefix('gudang')->name('gudang.')->group(function () {
        Route::get('/dashboard', [GudangDashboardController::class, 'index'])->name('dashboard');

        // Contoh Penerimaan Barang
        Route::get('/penerimaan', [GudangPenerimaanController::class, 'index'])->name('penerimaan.index');
        Route::get('/penerimaan/create/{pembelian?}', [GudangPenerimaanController::class, 'create'])->name('penerimaan.create'); // Bisa dari PO atau manual
        Route::post('/penerimaan', [GudangPenerimaanController::class, 'store'])->name('penerimaan.store');
        // ... (Rute Gudang lainnya: Perpindahan Stok, Stok Opname, Lihat Stok, dll.)

    });

    // Rute Logout (biasanya sudah ada dari Auth::routes(), tapi bisa didefinisikan ulang jika perlu)
    // Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

});