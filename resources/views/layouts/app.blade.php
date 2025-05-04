<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CV Kingstar System') }} - @yield('title', 'Dashboard')</title> {{-- Default title --}}

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Styles -->
    {{-- Memuat CSS utama yang dikompilasi oleh Mix (termasuk Bootstrap) --}}
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    {{-- Tempat untuk menambahkan CSS spesifik per halaman --}}
    @stack('styles')

    {{-- Style tambahan untuk layout (opsional, bisa dipindah ke app.scss) --}}
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f8f9fa; /* Warna latar belakang sedikit abu-abu */
        }
        #app {
            flex: 1; /* Membuat konten utama mengisi ruang yang tersedia */
            display: flex;
            flex-direction: column;
        }
        main {
            flex-grow: 1; /* Konten utama bisa tumbuh */
        }
        .navbar {
             border-bottom: 1px solid #dee2e6; /* Garis bawah tipis di navbar */
        }
        .nav-link.active {
            font-weight: bold; /* Membuat link aktif sedikit tebal */
            color: #0d6efd !important; /* Warna biru utama Bootstrap */
        }
        .navbar-brand img {
            margin-right: 0.5rem;
        }
        .footer {
            background-color: #e9ecef; /* Warna footer sedikit lebih gelap */
            padding: 1rem 0;
            font-size: 0.9em;
            margin-top: auto; /* Mendorong footer ke bawah */
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm sticky-top"> {{-- sticky-top agar navbar tetap terlihat saat scroll --}}
            <div class="container">
                {{-- Brand/Logo --}}
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/home') }}">
                    {{-- Ganti 'images/logo.png' dengan path logo Anda di folder public --}}
                    <img src="{{ asset('images/kingstar_logo.png') }}" alt="{{ config('app.name', 'CV Kingstar') }} Logo" height="35">
                    {{-- <span class="fw-bold">{{ config('app.name', 'CV Kingstar System') }}</span> --}}
                    {{-- Tampilkan nama aplikasi jika tidak ada logo atau sebagai tambahan --}}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar - Menu Utama Berdasarkan Role -->
                    <ul class="navbar-nav me-auto mb-2 mb-md-0"> {{-- mb-2 mb-md-0 untuk spacing mobile --}}
                        @auth {{-- Tampilkan hanya jika login --}}
                            @if(Auth::user()->role == 'ADMIN')
                                <li class="nav-item">
                                    {{-- Cek route aktif --}}
                                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item dropdown">
                                     <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.produk.*') || request()->routeIs('admin.merk.*') || request()->routeIs('admin.supplier.*') || request()->routeIs('admin.pelanggan.*') ? 'active' : '' }}" href="#" id="masterDataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                         <i class="bi bi-database me-1"></i> Master Data
                                     </a>
                                     <ul class="dropdown-menu" aria-labelledby="masterDataDropdown">
                                         <li><a class="dropdown-item {{ request()->routeIs('admin.produk.*') ? 'active' : '' }}" href="{{ route('admin.produk.index') }}">Produk</a></li>
                                         <li><a class="dropdown-item {{ request()->routeIs('admin.merk.*') ? 'active' : '' }}" href="{{ route('admin.merk.index') }}">Merk</a></li> 
                                         <li><a class="dropdown-item {{ request()->routeIs('admin.supplier.*') ? 'active' : '' }}" href="{{ route('admin.supplier.index') }}">Supplier</a></li> 
                                         <li><a class="dropdown-item {{ request()->routeIs('admin.pelanggan.*') ? 'active' : '' }}" href="{{ route('admin.pelanggan.index') }}">Pelanggan</a></li> 
                                     </ul>
                                </li>
                                 <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.pengguna.*') ? 'active' : '' }}" href="{{ route('admin.pengguna.index') }}">
                                        <i class="bi bi-people me-1"></i> Pengguna
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.pembelian.*') ? 'active' : '' }}" href="{{ route('admin.pembelian.index')}}"> {{-- Ganti # dengan route('admin.pembelian.index') --}}
                                        <i class="bi bi-cart-plus me-1"></i> Pembelian
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}" href="#"> {{-- Ganti # dengan route('admin.laporan.index') --}}
                                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan
                                    </a>
                                </li>
                                {{-- Tambah menu admin lain --}}

                            @elseif(Auth::user()->role == 'KASIR')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('kasir.dashboard') ? 'active' : '' }}" href="{{ route('kasir.dashboard') }}">
                                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('kasir.penjualan.create') ? 'active' : '' }}" href="{{ route('kasir.penjualan.create') }}">
                                        <i class="bi bi-cart-check me-1"></i> Buat Penjualan
                                    </a>
                                </li>
                                 <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('kasir.transaksi.hari-ini') ? 'active' : '' }}" href="#"> {{-- Ganti # dengan route yang sesuai --}}
                                        <i class="bi bi-calendar-day me-1"></i> Transaksi Hari Ini
                                    </a>
                                </li>
                                 {{-- Tambah menu kasir lain --}}

                            @elseif(Auth::user()->role == 'GUDANG')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('gudang.dashboard') ? 'active' : '' }}" href="{{ route('gudang.dashboard') }}">
                                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('gudang.penerimaan.*') ? 'active' : '' }}" href="{{ route('gudang.penerimaan.index') }}">
                                        <i class="bi bi-box-arrow-in-down me-1"></i> Penerimaan Barang
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('gudang.stok.*') ? 'active' : '' }}" href="#"> {{-- Ganti # dengan route('gudang.stok.index') --}}
                                        <i class="bi bi-boxes me-1"></i> Lihat Stok
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('gudang.opname.*') ? 'active' : '' }}" href="#"> {{-- Ganti # dengan route('gudang.opname.index') --}}
                                        <i class="bi bi-clipboard-check me-1"></i> Stok Opname
                                    </a>
                                </li>
                                {{-- Tambah menu gudang lain --}}
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar - User Info & Logout -->
                    <ul class="navbar-nav ms-auto">
                        @guest
                            {{-- Jika perlu link login/register ditampilkan --}}
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Login') }}</a>
                                </li>
                            @endif
                            {{-- @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif --}}
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="bi bi-person-circle me-1"></i>
                                    {{ Auth::user()->nama }} ({{ Auth::user()->role }})
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    {{-- Tambahkan link ke profil jika ada --}}
                                    {{-- <a class="dropdown-item" href="#">
                                        <i class="bi bi-person-gear me-1"></i> Profil Saya
                                    </a> --}}
                                    {{-- <div class="dropdown-divider"></div> --}}
                                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right me-1"></i> {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4"> {{-- Container dipindah ke dalam section content agar lebih fleksibel --}}
            <div class="container"> {{-- Tambahkan container di sini atau di dalam view spesifik --}}
                {{-- Menampilkan Pesan Error/Sukses --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Menampilkan error validasi form --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                         <i class="bi bi-exclamation-octagon-fill me-2"></i> <strong>Error!</strong> Terdapat masalah dengan input Anda:
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Konten Utama Halaman --}}
                @yield('content')
            </div>
        </main>

        <footer class="footer mt-auto py-3 bg-light">
            <div class="container text-center text-muted">
                <small>&copy; {{ date('Y') }} {{ config('app.name', 'CV Kingstar System') }}. Developed by Roger Jeremy.</small>
            </div>
        </footer>

    </div> {{-- End #app --}}

    <!-- Scripts -->
    {{-- Memuat JS utama yang dikompilasi oleh Mix (termasuk jQuery, Bootstrap JS) --}}
    <script src="{{ mix('js/app.js') }}"></script>

    {{-- Tempat untuk menambahkan JS spesifik per halaman --}}
    @stack('scripts')

</body>
</html>