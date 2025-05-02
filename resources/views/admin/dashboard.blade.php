@extends('layouts.app') {{-- Gunakan layout utama --}}

@section('title', 'Admin Dashboard') {{-- Judul Halaman --}}

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12"> {{-- Buat lebih lebar --}}
            <div class="card">
                <div class="card-header">{{ __('Admin Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ __('Selamat datang, Admin!') }}
                    <p>Ini adalah halaman dashboard khusus untuk Admin.</p>
                    {{-- Tambahkan widget atau ringkasan data admin di sini --}}

                     <div class="mt-4">
                         <h5>Akses Cepat:</h5>
                         <a href="{{ route('admin.pengguna.index') }}" class="btn btn-primary me-2">Kelola Pengguna</a>
                         <a href="{{ route('admin.produk.index') }}" class="btn btn-secondary me-2">Kelola Produk</a>
                         {{-- Tambah tombol akses cepat lain --}}
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- <script src="{{ mix('js/pages/admin_dashboard.js') }}"></script> --}}
     {{-- Jika perlu JS khusus untuk dashboard admin --}}
@endpush