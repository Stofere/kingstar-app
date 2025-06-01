// resources/js/app.js

// Import file bootstrap.js yang menginisialisasi dasar (termasuk jQuery & Bootstrap JS)
require("./bootstrap");

// Plugin jQuery lain bisa diimpor di sini (karena jQuery sudah global dari bootstrap.js)
require("select2");
require("datatables.net-bs5");
require("datatables.net-responsive-bs5");
require("inputmask");

// Inisialisasi global atau event listener umum bisa ditaruh di sini
document.addEventListener("DOMContentLoaded", function () {
    // Contoh: Inisialisasi tooltip Bootstrap di seluruh aplikasi
    // Perhatikan: 'bootstrap' sekarang adalah objek dari require('bootstrap') di bootstrap.js
    // jika Anda ingin mengakses instance, Anda mungkin perlu cara lain jika tidak diexpose global.
    // Namun, plugin jQuery seperti modal seharusnya sudah otomatis ter-attach.
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    var tooltipList = tooltipTriggerList
        .map(function (tooltipTriggerEl) {
            // Untuk Bootstrap 5, instance dibuat melalui objek global 'bootstrap'
            // Jika 'bootstrap' diimpor di bootstrap.js tanpa di-assign ke window,
            // Anda mungkin perlu cara lain atau meng-assign-nya ke window di bootstrap.js
            // window.bootstrap = require('bootstrap'); // di bootstrap.js
            if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
                // Periksa apakah bootstrap global ada
                return new bootstrap.Tooltip(tooltipTriggerEl);
            }
            return null;
        })
        .filter(Boolean);

    // Anda bisa menginisialisasi plugin jQuery di sini juga jika perlu
    // misalnya, default settings untuk Select2 global
    // if ($.fn.select2) {
    //    $.fn.select2.defaults.set("theme", "bootstrap-5");
    //    $.fn.select2.defaults.set("width", "100%");
    // }
});
