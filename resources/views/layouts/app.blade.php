<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    {{-- Memuat CSS utama yang dikompilasi oleh Mix --}}
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    {{-- Tempat untuk menambahkan CSS spesifik per halaman --}}
    @stack('styles')
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            {{-- Konten Navbar --}}
        </nav>

        <main class="py-4 container"> {{-- Tambahkan class container atau container-fluid --}}
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    {{-- Memuat JS utama yang dikompilasi oleh Mix (termasuk jQuery, Bootstrap JS) --}}
    <script src="{{ mix('js/app.js') }}"></script>

    {{-- Tempat untuk menambahkan JS spesifik per halaman --}}
    @stack('scripts')
</body>
</html>