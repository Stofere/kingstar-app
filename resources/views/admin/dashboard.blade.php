@extends('layouts.app') 

@section('title', 'Dashboard Admin')

@push('styles')
    <style>
        .info-box { display: flex; align-items: center; }
        .info-box-icon { font-size: 2.5rem; margin-right: 1rem; }
        .info-box-content { display: flex; flex-direction: column; }
        .stok-kritis-item { font-size: 0.9rem; }
        .stok-kritis-item + .stok-kritis-item { border-top: 1px dashed #eee; padding-top: 0.5rem; margin-top: 0.5rem;}
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard Admin</h1>
        {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report</a> --}}
    </div>

    <!-- Ringkasan Angka (KPI Cards) -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center info-box">
                        <div class="col-auto">
                            <i class="bi bi-cart4 text-primary info-box-icon"></i>
                        </div>
                        <div class="col info-box-content">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Penjualan Hari Ini (Transaksi)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $jumlahTransaksiHariIni }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center info-box">
                         <div class="col-auto">
                            <i class="bi bi-cash-coin text-success info-box-icon"></i>
                        </div>
                        <div class="col info-box-content">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Omzet Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalOmzetHariIni, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center info-box">
                        <div class="col-auto">
                            <i class="bi bi-box-seam text-danger info-box-icon"></i>
                        </div>
                        <div class="col info-box-content">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Produk Stok Kritis</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $jumlahProdukStokKritis }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center info-box">
                        <div class="col-auto">
                            <i class="bi bi-truck text-info info-box-icon"></i>
                        </div>
                        <div class="col info-box-content">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">PO Aktif (Dipesan/Kirim)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $jumlahPoAktif }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Penjualan dan Daftar Stok Kritis -->
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Tren Penjualan (7 Hari Terakhir)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="penjualanMingguanChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Peringatan Stok Rendah/Habis</h6>
                </div>
                <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                    @if($produkStokKritis->isNotEmpty())
                        @foreach($produkStokKritis as $produk)
                            <div class="stok-kritis-item">
                                <a href="{{ route('admin.laporan.stok.detail_batch_produk', $produk->id) }}" class="text-decoration-none">
                                    <strong>{{ $produk->nama }}</strong>
                                    @if($produk->kode_produk) <small class="text-muted">({{ $produk->kode_produk }})</small> @endif
                                </a>
                                <span class="float-end badge {{ $produk->stok_efektif <= 0 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                    Sisa: {{ $produk->stok_efektif }} {{ $produk->satuan }}
                                </span>
                                <small class="d-block text-muted">Min: {{ $produk->stok_minimum }} {{ $produk->satuan }}</small>
                            </div>
                        @endforeach
                    @else
                        <p class="text-success text-center mt-3"><i class="bi bi-check-circle-fill me-2"></i>Semua stok produk dalam kondisi aman.</p>
                    @endif
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('admin.laporan.stok.ringkasan_produk') }}">Lihat Laporan Stok Lengkap →</a>
                </div>
            </div>
        </div>
    </div>

     <!-- Shortcut/Tombol Aksi Cepat -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Aksi Cepat</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.pembelian.create') }}" class="btn btn-outline-success me-2 mb-2">
                        <i class="bi bi-cart-plus-fill me-1"></i> Buat Pembelian Baru
                    </a>
                    <a href="{{ route('admin.laporan.stok.ringkasan_produk') }}" class="btn btn-outline-info me-2 mb-2">
                        <i class="bi bi-archive-fill me-1"></i> Laporan Status Stok
                    </a>
                    <a href="{{ route('admin.laporan.penjualan.index') }}" class="btn btn-outline-primary me-2 mb-2"> {{-- Asumsi route ini ada --}}
                        <i class="bi bi-graph-up me-1"></i> Laporan Penjualan
                    </a>
                    <a href="{{ route('admin.produk.create') }}" class="btn btn-outline-secondary me-2 mb-2">
                        <i class="bi bi-plus-square-dotted me-1"></i> Tambah Produk Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            // Data untuk chart penjualan mingguan (dari controller)
            const penjualanMingguanLabels = @json($penjualanMingguanLabels);
            const penjualanMingguanData = @json($penjualanMingguanData);

            const ctxPenjualanMingguan = document.getElementById('penjualanMingguanChart').getContext('2d');
            new Chart(ctxPenjualanMingguan, {
                type: 'line', // atau bisa ;bar'
                data: {
                    labels: penjualanMingguanLabels,
                    datasets: [{
                        label: 'Total Penjualan (Rp)',
                        data: penjualanMingguanData,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Penting agar chart bisa menyesuaikan tinggi container
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush