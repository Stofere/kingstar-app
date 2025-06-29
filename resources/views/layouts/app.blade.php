<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CV Kingstar System') }} - @yield('title', 'Dashboard')</title> 

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Global Stylesheets from CDN -->
    {{-- Bootstrap 5 CSS (Jika app.css Anda TIDAK menyertakan Bootstrap dari SASS) --}}
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> --}}

    {{-- DataTables Bootstrap 5 CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    {{-- Select2 Bootstrap 5 Theme CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    {{-- SweetAlert2 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    {{-- Litepicker CSS (PENTING untuk filter tanggal) --}}
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css"/>
    
     <!-- Styles Aplikasi Utama (dari Laravel Mix - HARUSNYA INI SUDAH TERMASUK BOOTSTRAP CSS) -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    {{-- Tempat untuk menambahkan CSS spesifik per halaman --}}
     @stack('styles')

    <style>
        /* Style global Anda bisa tetap di sini atau dipindah ke file SASS terpisah */
        html, body { height: 100%; }
        body { display: flex; flex-direction: column; font-size: 0.95rem; background-color: #f4f6f9; }
        #app { flex: 1; display: flex; flex-direction: column; }
        .navbar { z-index: 1030; }
        .navbar-brand img { max-height: 30px; margin-right: 0.5rem; }
        main.py-4 { padding-top: 1.5rem !important; padding-bottom: 2rem !important; flex-grow: 1; }
        .footer { background-color: #ffffff; border-top: 1px solid #dee2e6; padding: 1rem 0; font-size: 0.85em; margin-top: auto; color: #6c757d; }
        /* z-index overrides */
        .select2-container--open { z-index: 9999 !important; }
        .swal2-container { z-index: 10000 !important; }
        .litepicker { z-index: 9998 !important; }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm sticky-top">
            <div class="container-fluid"> {{-- Ubah ke fluid untuk layout lebih lebar --}}
                <a class="navbar-brand d-flex align-items-center" href="{{ Auth::check() ? route(strtolower(Auth::user()->role) . '.dashboard') : url('/') }}">
                    <img src="{{ asset('images/kingstar_logo.png') }}" alt="{{ config('app.name', 'CV Kingstar') }} Logo">
                    <span class="d-none d-sm-inline fw-bold ms-1">{{ config('app.name', 'Kingstar') }}</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Navigasi Dimuat Berdasarkan Peran -->
                    <ul class="navbar-nav me-auto mb-2 mb-md-0">
                        @auth
                            @if(Auth::user()->role == 'ADMIN')
                                @include('layouts.partials._nav_admin')
                            @elseif(Auth::user()->role == 'KASIR')
                                @include('layouts.partials._nav_kasir')
                            @elseif(Auth::user()->role == 'GUDANG')
                                @include('layouts.partials._nav_gudang')
                            @endif
                        @endauth
                    </ul>

                    <!-- Navigasi Kanan (Profil Pengguna) -->
                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item"><a class="nav-link" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Login') }}</a></li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="bi bi-person-circle me-1"></i>
                                    {{ Auth::user()->nama }} <span class="badge bg-secondary align-middle">{{ Auth::user()->role }}</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right me-1"></i> {{ __('Logout') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4"> {{-- py-4 adalah padding atas & bawah dari Bootstrap --}}
            @yield('content')
        </main>

        <footer class="footer">
            <div class="container text-center">
                <small>© {{ date('Y') }} {{ config('app.name', 'CV Kingstar System') }}. Dibuat untuk CV Kingstar.</small>
            </div>
        </footer>
    </div> 
    <!-- Scripts -->
    {{-- 1. Memuat JS utama yang dikompilasi oleh Mix --}}
    <script src="{{ mix('js/app.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{-- 2. PASTIKAN jQuery dimuat SEBELUM plugin jQuery seperti Inputmask & Select2 --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    {{-- 3. Memuat Pustaka JS Pihak Ketiga dari CDN SETELAH jQuery (jika dari CDN) dan app.js --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Litepicker JS (PENTING untuk filter tanggal) --}}
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/plugins/ranges.js"></script> 

    {{-- Tambahan global script jika ada, misal untuk konfigurasi default AJAX CSRF token --}}
    <script>
        // Setup CSRF token untuk semua request AJAX jQuery (jika belum diatur di app.js)
        if (typeof $ !== 'undefined' && $.ajaxSetup) { // Pastikan jQuery ada
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        }

        // Fungsi helper global (opsional, bisa diletakkan di file JS terpisah dan di-bundle oleh Mix)
        function formatRupiahGlobal(angka, prefix = 'Rp ') {
            if (isNaN(angka) || angka === null || angka === undefined) return prefix + '0';
            let number_string = Math.round(angka).toString().replace(/[^,\d]/g, ''),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);
            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
            return prefix + rupiah;
        }

        function parseRupiahGlobal(rupiahString) {
            if (typeof rupiahString !== 'string') return 0;
            return parseInt(rupiahString.replace(/[^0-9]/g, ''), 10) || 0;
        }
    </script>
    {{-- 4. Tempat untuk menambahkan JS spesifik per halaman (AKAN DIISI OLEH @push) --}}
    @stack('scripts')


</body>
</html>