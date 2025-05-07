@extends('layouts.app')

@section('title', 'Dashboard Gudang')

@section('content')
<div class="container">
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-person-workspace me-2"></i> Dashboard Gudang - Selamat Datang, {{ Auth::user()->nama }}!
                    </h4>
                </div>
                <div class="card-body">
                    <p class="lead">Ini adalah area manajemen gudang. Dari sini Anda dapat mengelola penerimaan barang, stok, dan operasional gudang lainnya.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Kartu untuk Penerimaan Barang --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-box-arrow-in-down fs-1 text-success mb-3"></i>
                    <h5 class="card-title">Penerimaan Barang</h5>
                    <p class="card-text">Catat barang yang masuk dari supplier atau berdasarkan Purchase Order.</p>
                    <a href="{{ route('gudang.penerimaan.index') }}" class="btn btn-outline-secondary btn-sm mb-2 w-100">
                        <i class="bi bi-list-ul me-1"></i> Lihat Daftar Penerimaan
                    </a>
                    <a href="{{ route('gudang.penerimaan.create') }}" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle-fill me-1"></i> Buat Penerimaan Baru
                    </a>
                </div>
            </div>
        </div>

        {{-- Kartu untuk Manajemen Stok (Placeholder) --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-boxes fs-1 text-info mb-3"></i>
                    <h5 class="card-title">Manajemen Stok</h5>
                    <p class="card-text">Lihat ketersediaan stok barang, lakukan perpindahan, dan penyesuaian.</p>
                    <a href="#" class="btn btn-info w-100 disabled"> {{-- Ganti # dengan route yang sesuai nanti --}}
                        <i class="bi bi-search me-1"></i> Lihat Stok Barang (Segera)
                    </a>
                    {{-- <a href="#" class="btn btn-outline-info btn-sm mt-2 w-100 disabled">Perpindahan Stok (Segera)</a> --}}
                </div>
            </div>
        </div>

        {{-- Kartu untuk Stok Opname (Placeholder) --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-clipboard-check fs-1 text-warning mb-3"></i>
                    <h5 class="card-title">Stok Opname</h5>
                    <p class="card-text">Lakukan perhitungan fisik stok barang secara periodik.</p>
                    <a href="#" class="btn btn-warning w-100 disabled"> {{-- Ganti # dengan route yang sesuai nanti --}}
                        <i class="bi bi-check2-square me-1"></i> Mulai Stok Opname (Segera)
                    </a>
                </div>
            </div>
        </div>

        {{-- Tambahkan kartu lain jika ada fitur gudang tambahan --}}

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Jika ada JS spesifik untuk dashboard gudang, tambahkan di sini
    // console.log('Dashboard Gudang dimuat!');
</script>
@endpush