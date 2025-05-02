// resources/js/app.js

// Import file bootstrap.js yang menginisialisasi dasar
require("./bootstrap");

// Import jQuery (meskipun sudah di-autoload, kadang perlu di-require di entry point)
// Jika autoload berfungsi baik, baris ini mungkin tidak wajib, tapi tidak ada salahnya.
window.$ = window.jQuery = require("jquery");

// Import Select2
require("select2");

// Import DataTables Core dan Ekstensi Bootstrap 5
require("datatables.net-bs5");
require("datatables.net-responsive-bs5");

// Inisialisasi global atau event listener umum bisa ditaruh di sini
// Contoh: Inisialisasi tooltip Bootstrap di seluruh aplikasi
document.addEventListener("DOMContentLoaded", function () {
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inisialisasi Select2 dasar untuk elemen dengan class tertentu (jika ada)
    // $('.basic-select2').select2();

    // Kode global lainnya...
});
