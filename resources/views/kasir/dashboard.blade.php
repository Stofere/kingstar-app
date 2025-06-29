@extends('layouts.app')

@section('title', 'Dashboard Kasir')

@push('styles')
<style>
    .kpi-card {
        border-left: 5px solid var(--bs-primary);
    }
    .kpi-card .card-body {
        padding: 1rem;
    }
    .kpi-card .kpi-value {
        font-size: 1.75rem;
        font-weight: 700;
    }
    .kpi-card .kpi-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #6c757d;
    }
    .list-group-item-action:hover {
        background-color: #f8f9fa;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard Kasir</h1>
        <span class="text-muted">{{ Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY') }}</span>
    </div>

    <!-- Ringkasan KPI -->
    <div class="row">
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm kpi-card border-left-success">
                <div class="card-body">
                    <div class="kpi-label text-success">Total Omzet Toko (Hari Ini)</div>
                    <div class="kpi-value text-gray-800">Rp {{ number_format($totalPenjualanTokoHariIni ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm kpi-card border-left-info">
                <div class="card-body">
                    <div class="kpi-label text-info">Jumlah Transaksi Toko (Hari Ini)</div>
                    <div class="kpi-value text-gray-800">{{ $jumlahTransaksiTokoHariIni ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-4 mb-4">
            <div class="card shadow-sm kpi-card {{ $jumlahPesanBarangMenunggu > 0 ? 'border-left-warning' : 'border-left-secondary' }}">
                <div class="card-body">
                    <div class="kpi-label {{ $jumlahPesanBarangMenunggu > 0 ? 'text-warning' : 'text-secondary' }}">Pesan Barang Menunggu Proses</div>
                    <div class="kpi-value text-gray-800">{{ $jumlahPesanBarangMenunggu ?? 0 }}</div>
                     @if($jumlahPesanBarangMenunggu > 0)
                    <a href="{{ route('kasir.pesan_barang_selesai.index') }}" class="btn btn-sm btn-outline-warning mt-1 float-end">Lihat Daftar</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Transaksi Terakhir -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-receipt me-2"></i>5 Transaksi Penjualan Terakhir</h6>
                </div>
                <div class="card-body p-0">
                    @if($transaksiTerakhir->isNotEmpty())
                    <ul class="list-group list-group-flush">
                        @foreach ($transaksiTerakhir as $trx)
                        <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('kasir.penjualan.nota', $trx->id) }}" target="_blank" class="fw-bold text-decoration-none">{{ $trx->nomor_penjualan }}</a>
                                <small class="d-block text-muted">{{ $trx->pelanggan->nama ?? 'Pelanggan Umum' }} • {{ $trx->tanggal_penjualan->diffForHumans() }}</small>
                            </div>
                            <span class="badge bg-info rounded-pill">Rp {{ number_format($trx->total_harga, 0, ',', '.') }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-center text-muted p-3">Belum ada transaksi selesai hari ini.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pesan Barang Menunggu Tindakan -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light py-3">
                    <h6 class="m-0 font-weight-bold text-warning"><i class="bi bi-hourglass-split me-2"></i>Pesan Barang Siap Diproses Kasir ({{ $jumlahPesanBarangMenunggu }})</h6>
                </div>
                <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                     @if($pesanBarangMenunggu->isNotEmpty())
                    <ul class="list-group list-group-flush">
                        @foreach ($pesanBarangMenunggu as $pesanan)
                        <li class="list-group-item list-group-item-action">
                            <a href="{{ route('kasir.pesan_barang_selesai.form', $pesanan->id) }}" class="text-decoration-none d-block">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $pesanan->nomor_penjualan }}</h6>
                                    <small class="text-muted">{{ $pesanan->tanggal_penjualan->isoFormat('D MMM') }}</small>
                                </div>
                                <p class="mb-1"><small>{{ $pesanan->pelanggan->nama ?? 'Pelanggan Umum' }} - Sisa: Rp {{ number_format($pesanan->sisa_pembayaran, 0, ',', '.') }}</small></p>
                                @foreach($pesanan->detailPenjualan->take(2) as $detail) {{-- Tampilkan maks 2 item --}}
                                    <small class="d-block text-muted fst-italic">• {{ $detail->produk->nama }} ({{$detail->jumlah}})</small>
                                @endforeach
                                @if($pesanan->detailPenjualan->count() > 2)
                                     <small class="d-block text-muted fst-italic">• dan lainnya...</small>
                                @endif
                            </a>
                        </li>
                        @endforeach
                    </ul>
                     @else
                    <p class="text-center text-muted p-3">Tidak ada pesan barang yang menunggu tindakan.</p>
                    @endif
                </div>
                @if($jumlahPesanBarangMenunggu > 0)
                <div class="card-footer text-center">
                    <a href="{{ route('kasir.pesan_barang_selesai.index') }}">Lihat Semua Pesanan Menunggu →</a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tombol Aksi Utama Kasir -->
    <div class="row mt-2">
        <div class="col-12">
            <div class="card shadow-sm">
                 <div class="card-header bg-light py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-lightning-charge-fill me-2"></i>Aksi Cepat</h6>
                </div>
                <div class="card-body text-center">
                    <a href="{{ route('kasir.penjualan.create') }}" class="btn btn-lg btn-primary me-2 mb-2">
                        <i class="bi bi-cart-plus-fill me-1"></i> Buat Penjualan Baru
                    </a>
                    <a href="{{ route('kasir.pesan_barang_selesai.index') }}" class="btn btn-lg btn-success me-2 mb-2">
                        <i class="bi bi-box-seam me-1"></i> Selesaikan Pesan Barang
                    </a>
                    <a href="{{ route('kasir.retur_penjualan.cari_transaksi') }}" class="btn btn-lg btn-danger mb-2">
                        <i class="bi bi-arrow-return-left me-1"></i> Proses Retur Penjualan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // JS spesifik dashboard Kasir (jika ada, misal auto-refresh data tertentu)
    // console.log('Dashboard Kasir dimuat!');
</script>
@endpush