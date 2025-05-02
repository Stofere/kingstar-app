@extends('layouts.app') // atau layout custom Anda

@section('title', 'Buat Penjualan Baru')

@push('styles')
    {{-- Jika ada CSS khusus untuk halaman ini --}}
    {{-- <link rel="stylesheet" href="/path/to/specific.css"> --}}
@endpush

@section('content')
    {{-- Konten HTML form penjualan Anda --}}
    <h1>Form Penjualan</h1>
    <form id="form-penjualan">
        {{-- ... elemen form ... --}}
    </form>

    {{-- Modal Pilih Batch --}}
    {{-- Modal Pilih Serial --}}
    {{-- Modal Tambah Pelanggan Cepat --}}
@endsection

@push('scripts')
    {{-- Memuat file JS yang sudah dikompilasi khusus untuk halaman penjualan --}}
    <script src="{{ mix('js/pages/penjualan.js') }}"></script>

    {{-- Anda juga bisa menambahkan script inline kecil di sini jika perlu --}}
    <script>
        // Contoh: Mengambil data dari Blade ke JavaScript
        const someDataFromBackend = @json($dataUntukJs ?? []);
        console.log('Data dari backend:', someDataFromBackend);

        // Panggil fungsi inisialisasi dari penjualan.js jika ada
        // Pastikan kode di penjualan.js dieksekusi setelah DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            // Contoh: Jika Anda punya fungsi initPenjualanForm di penjualan.js
            // if (typeof initPenjualanForm === 'function') {
            //     initPenjualanForm();
            // }
        });
    </script>
@endpush