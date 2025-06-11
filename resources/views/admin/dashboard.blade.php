@extends('layouts.app') 

@section('title', 'Dashboard Admin')

@push('styles')
<style>
    .stok-rendah-alert ul {
        padding-left: 1.2rem;
        margin-bottom: 0;
    }
    .stok-rendah-alert li {
        font-size: 0.9rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard Admin</h1>
        {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                class="fas fa-download fa-sm text-white-50"></i> Generate Report</a> --}}
    </div>

    {{-- Notifikasi Stok Rendah --}}
    @if(isset($produkStokRendah) && $produkStokRendah->isNotEmpty())
        <div class="alert alert-danger stok-rendah-alert" role="alert">
            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Peringatan Stok Rendah!</h4>
            <p>Produk berikut memiliki jumlah stok yang mencapai atau di bawah batas minimum:</p>
            <hr>
            <ul>
                @foreach ($produkStokRendah as $produk)
                    <li>
                        <strong>{{ $produk->nama }} ({{ $produk->kode_produk ?? 'N/A' }})</strong> -
                        Stok Saat Ini: <span class="fw-bold">{{ $produk->total_stok_fisik ?: 0 }} {{ $produk->satuan }}</span>,
                        Batas Minimum: <span class="fw-bold">{{ $produk->stok_minimum }} {{ $produk->satuan }}</span>.
                        <a href="{{ route('admin.laporan.stok.detail_batch_produk', $produk->id) }}" class="btn btn-sm btn-outline-danger ms-2 py-0 px-1" title="Lihat Detail Stok">
                            <i class="bi bi-search"></i> Cek
                        </a>
                    </li>
                @endforeach
            </ul>
            <p class="mb-0 mt-3">Harap segera lakukan pengecekan dan pertimbangkan untuk melakukan pembelian ulang.</p>
        </div>
    @else
        <div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Semua produk dengan batas minimum terpantau memiliki stok yang cukup.
        </div>
    @endif

    {{-- Konten Dashboard Lainnya --}}
    <div class="row">
        {{-- Contoh Card Statistik (bisa Anda tambahkan nanti) --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Penjualan (Bulan Ini)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp 0</div> {{-- Ganti dengan data asli --}}
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-check-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Produk Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ \App\Models\Produk::where('status', true)->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-box-seam-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Tambahkan card statistik lain jika perlu --}}
    </div>

    <div class="row">
        <div class="col-lg-12 mb-4">
            {{-- Link Cepat ke Fitur Lain --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Akses Cepat</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.produk.index') }}" class="btn btn-outline-info m-1"><i class="bi bi-box me-1"></i> Kelola Produk</a>
                    <a href="{{ route('admin.pembelian.index') }}" class="btn btn-outline-warning m-1"><i class="bi bi-cart-plus me-1"></i> Kelola Pembelian</a>
                    <a href="{{ route('kasir.penjualan.create') }}" class="btn btn-outline-success m-1"><i class="bi bi-cash-stack me-1"></i> Buat Penjualan (Kasir)</a>
                    <a href="{{ route('admin.laporan.stok.ringkasan_produk') }}" class="btn btn-outline-primary m-1"><i class="bi bi-bar-chart-line me-1"></i> Laporan Stok</a>
                    {{-- Tambahkan link lain --}}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    {{-- Script tambahan untuk dashboard jika perlu --}}
@endpush