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
use App\Http\Controllers\Admin\StokOpnameController;


// Kasir Controllers
use App\Http\Controllers\Kasir\KasirDashboardController;
use App\Http\Controllers\Kasir\PenjualanController as KasirPenjualanController;
use App\Http\Controllers\Kasir\KasirPesanBarangController; 
use App\Http\Controllers\Kasir\ReturPenjualanController;

// Gudang Controllers
use App\Http\Controllers\Gudang\GudangDashboardController;
use App\Http\Controllers\Gudang\PenerimaanController as GudangPenerimaanController;
use App\Http\Controllers\Admin\StokOpnameController as GudangStokOpnameController;

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
        Route::resource('retur-pembelian', ReturPembelianController::class);
        // Route::resource('stok-opname', StokOpnameController::class)->except(['destroy', 'show', 'update', 'createAdjustment', 'updatePhysicalCount']); // Admin hanya bisa index, create, store
        // Route::post('stok-opname/{stokOpname}/create-adjustment', [StokOpnameController::class, 'createAdjustment'])->name('stok-opname.create_adjustment');
        // Route::post('stok-opname/{stokOpname}/update-physical-count', [StokOpnameController::class, 'updatePhysicalCount'])->name('stok-opname.update_physical_count');
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

            // --- Laporan Stok ---
            // Grup prefix 'stok' dengan nama grup 'stok.' agar menghasilkan admin.laporan.stok.
            Route::prefix('stok')->name('stok.')->group(function () {

                Route::get('/ringkasan-produk', [LaporanStokController::class, 'ringkasanProduk'])->name('ringkasan_produk');

                Route::get('/detail-batch/{produk}', [LaporanStokController::class, 'detailBatchProduk'])->name('detail_batch_produk');

                Route::get('/kartu-stok/{produk}', [LaporanStokController::class, 'generateKartuStok'])->name('kartu_stok.data');

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
            // Tambahkan route PO pengganti di sini
            Route::post('/{returPembelian}/create-replacement-po', [ReturPembelianController::class, 'createReplacementPo'])->name('create_replacement_po');
        });

        Route::prefix('proses-retur-pelanggan')->name('proses_retur_pelanggan.')->group(function () {
            Route::get('/', [ProsesReturPelangganController::class, 'index'])->name('index'); // Daftar retur menunggu tindakan Admin
            Route::get('/{returPenjualan}/proses', [ProsesReturPelangganController::class, 'showProsesForm'])->name('proses.form'); // Form untuk Admin memutuskan tindak lanjut
            Route::post('/{returPenjualan}/store-tindakan', [ProsesReturPelangganController::class, 'storeTindakanAdmin'])->name('store.tindakan'); // Menyimpan keputusan Admin
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