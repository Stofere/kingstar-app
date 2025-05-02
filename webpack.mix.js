// webpack.mix.js
const mix = require("laravel-mix");

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

// 1. Compile CSS Aplikasi Utama (Termasuk Bootstrap & nanti CSS DataTables)
mix.sass("resources/sass/app.scss", "public/css").options({
    processCssUrls: false, // Optional: Mencegah Mix memproses URL di CSS
});

// 2. Compile JavaScript Aplikasi Utama (Termasuk Bootstrap JS, Popper, jQuery)
mix.js("resources/js/app.js", "public/js").autoload({
    // Membuat jQuery tersedia secara global ($ dan jQuery)
    jquery: ["$", "window.jQuery", "jQuery"],
});

// 3. Compile JavaScript Spesifik per Halaman/Modul (Contoh)
//    Ini akan membuat file JS terpisah agar tidak semua JS dimuat di setiap halaman.
mix.js("resources/js/pages/penjualan.js", "public/js/pages"); // Untuk form penjualan
mix.js("resources/js/pages/penerimaan.js", "public/js/pages"); // Untuk form penerimaan
mix.js("resources/js/pages/produk.js", "public/js/pages"); // Untuk CRUD produk (jika perlu JS khusus)
// Tambahkan file JS spesifik lainnya sesuai kebutuhan

// 4. Copy Assets Pihak Ketiga (jika diperlukan, misal font atau image dari library)
// mix.copyDirectory('node_modules/some-library/fonts', 'public/fonts');

// 5. Pengaturan Tambahan (Opsional)
if (mix.inProduction()) {
    mix.version(); // Menambahkan hash unik ke nama file aset di mode produksi (cache busting)
} else {
    mix.sourceMaps(); // Membuat source maps untuk debugging di mode development
}

// Nonaktifkan notifikasi OS saat kompilasi selesai (opsional)
mix.disableNotifications();
