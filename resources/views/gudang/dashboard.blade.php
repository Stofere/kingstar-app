@extends('layouts.app') 

@section('title', 'Dashboard Gudang')

@push('styles')
    <style>
        .dashboard-card-link { text-decoration: none; color: inherit; }
        .dashboard-card-link .card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15)!important; transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
        .stat-icon { font-size: 2.8rem; opacity: 0.5; color: #adb5bd; }
        .card-icon-bg { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); }
        .border-left-custom-gudang { border-left: .25rem solid #17a2b8 !important; } /* Contoh warna info untuk gudang */
        .text-custom-gudang { color: #17a2b8 !important; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard Gudang</h1>
        {{-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                class="fas fa-download fa-sm text-white-50"></i> Generate Report</a> --}}
    </div>

    <!-- Content Row: Cards Statistik -->
    <div class="row">
        <!-- Card: Cek Stok Barang -->
        <div class="col-xl-4 col-md-6 mb-4">
            <a href="{{ route('gudang.stok.cek_form') }}" class="dashboard-card-link">
                <div class="card border-left-custom-gudang shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-custom-gudang text-uppercase mb-1">
                                    Cek Stok Barang</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">Pencarian Produk</div>
                            </div>
                            <div class="col-auto card-icon-bg">
                                <i class="bi bi-search stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Card: PO Menunggu Penerimaan -->
        <div class="col-xl-4 col-md-6 mb-4">
            <a href="{{ route('gudang.penerimaan.index') }}" class="dashboard-card-link">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    PO Menunggu Diterima</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $jumlahPoMenunggu ?? 0 }}</div>
                            </div>
                            <div class="col-auto card-icon-bg">
                                <i class="bi bi-box-arrow-in-down stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Card: Barang Diterima Hari Ini -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2"> {{-- Tidak perlu link jika hanya display --}}
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Barang Diterima Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $jumlahBarangDiterimaHariIni ?? 0 }} Unit</div>
                        </div>
                        <div class="col-auto card-icon-bg">
                            <i class="bi bi-truck stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row: Notifikasi dan Tabel Ringkasan -->
    <div class="row">
        <div class="col-lg-12 mb-4">
            {{-- Notifikasi atau Informasi Penting Lainnya --}}
                @if($adaProdukHabis && !empty($produkHabisDiGudangDanToko)) {{-- Gunakan variabel baru --}}
                <div class="row">
                    <div class="col-lg-12 mb-4">
                        <div class="alert alert-danger">
                            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Perhatian! Stok Habis</h5>
                            <p class="mb-0">Produk berikut terdeteksi habis stoknya (di Gudang maupun Toko): <strong>{{ $produkHabisDiGudangDanToko }}</strong>. Mohon segera lakukan pengecekan atau informasikan Admin.</p>
                        </div>
                    </div>
                </div>
                @endif

            {{-- Informasi Stok Opname (jika ada) --}}
            @if($opnameAktif)
            <div class="alert alert-info">
                <h5 class="alert-heading"><i class="bi bi-clipboard-check-fill"></i> Informasi Stok Opname</h5>
                <p class="mb-0">Saat ini sedang ada sesi Stok Opname yang <strong>BERJALAN</strong> (ID Sesi: {{ $opnameAktif->id }}, dimulai: {{ Carbon\Carbon::parse($opnameAktif->started_at)->isoFormat('D MMM YYYY, HH:mm') }}). Harap berkoordinasi dengan Admin.</p>
            </div>
            @elseif($opnameTerakhirSelesai)
             <div class="alert alert-secondary">
                <h5 class="alert-heading"><i class="bi bi-clipboard-data-fill"></i> Info Stok Opname Terakhir</h5>
                <p class="mb-0">Stok Opname terakhir telah <strong>SELESAI</strong> pada tanggal {{ Carbon\Carbon::parse($opnameTerakhirSelesai->finished_at)->isoFormat('D MMM YYYY, HH:mm') }} (ID Sesi: {{ $opnameTerakhirSelesai->id }}).</p>
            </div>
            @endif
        </div>

        <div class="col-lg-12">
             {{-- Daftar Singkat PO Menunggu Penerimaan --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">PO Menunggu Penerimaan (Maks. 5)</h6>
                    <a href="{{ route('gudang.penerimaan.index') }}">Lihat Semua →</a>
                </div>
                <div class="card-body">
                    @if($poMenungguDiterima && $poMenungguDiterima->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                {{-- ... (thead dan tbody untuk tabel PO sama seperti sebelumnya) ... --}}
                                <thead>
                                    <tr>
                                        <th>No. PO</th>
                                        <th>Supplier</th>
                                        <th>Tgl. PO</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($poMenungguDiterima as $po)
                                    <tr>
                                        <td><a href="{{ route('admin.pembelian.show', $po->id) }}" target="_blank">{{ $po->nomor_pembelian }}</a></td>
                                        <td>{{ $po->supplier->nama ?? 'N/A' }}</td>
                                        <td>{{ Carbon\Carbon::parse($po->tanggal_pembelian)->isoFormat('D MMM YYYY') }}</td>
                                        <td>
                                            @php $statusClass = 'secondary'; @endphp
                                            @if ($po->status_pembelian == 'DIPESAN') @php $statusClass = 'info'; @endphp
                                            @elseif ($po->status_pembelian == 'PENGIRIMAN') @php $statusClass = 'primary'; @endphp
                                            @elseif ($po->status_pembelian == 'TIBA_SEBAGIAN') @php $statusClass = 'warning text-dark'; @endphp
                                            @endif
                                            <span class="badge bg-{{ $statusClass }}">{{ str_replace('_',' ',$po->status_pembelian) }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('gudang.penerimaan.create', ['pembelian' => $po->id]) }}" class="btn btn-success btn-xs">
                                                <i class="bi bi-box-arrow-in-down"></i> Terima
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted my-3">Tidak ada PO yang menunggu untuk diterima saat ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@endpush