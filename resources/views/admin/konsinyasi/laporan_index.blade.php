@extends('layouts.app')

@section('title', 'Laporan Manajemen Konsinyasi')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Laporan Manajemen Konsinyasi</h1>

    {{-- Navigasi Tab --}}
    <ul class="nav nav-tabs mb-3" id="konsinyasiTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="terjual-tab" data-bs-toggle="tab" data-bs-target="#terjual-content" type="button" role="tab" aria-controls="terjual-content" aria-selected="true">
                <i class="bi bi-cash-coin me-1"></i> Barang Terjual (Belum Lunas ke Supplier)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stok-tab" data-bs-toggle="tab" data-bs-target="#stok-content" type="button" role="tab" aria-controls="stok-content" aria-selected="false">
                <i class="bi bi-box-seam me-1"></i> Stok Tersedia (Siap Dikembalikan)
            </button>
        </li>
    </ul>

    {{-- Konten Tab --}}
    <div class="tab-content" id="konsinyasiTabContent">
        {{-- =================================== --}}
        {{-- KONTEN TAB 1: BARANG TERJUAL       --}}
        {{-- =================================== --}}
        <div class="tab-pane fade show active" id="terjual-content" role="tabpanel" aria-labelledby="terjual-tab">
            <div class="card shadow-sm">
                <div class="card-header">Daftar Pembelian Konsinyasi yang Belum Dilunasi</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>No. PO Konsinyasi</th>
                                    <th>Tgl Terjual/PO</th>
                                    <th>Supplier</th>
                                    <th>Item Terjual</th>
                                    <th class="text-end">Total Utang</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($poKonsinyasiBelumLunas as $po)
                                    <tr>
                                        <td><strong>{{ $po->nomor_pembelian }}</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($po->tanggal_pembelian)->isoFormat('D MMM YYYY') }}</td>
                                        <td>{{ $po->supplier->nama ?? 'N/A' }}</td>
                                        <td>
                                            <ul class="list-unstyled mb-0">
                                            @foreach($po->detailPembelian as $detail)
                                                <li>{{ $detail->produk->nama ?? 'N/A' }} ({{ $detail->jumlah }} unit)</li>
                                            @endforeach
                                            </ul>
                                        </td>
                                        <td class="text-end text-danger fw-bold">Rp {{ number_format($po->total_harga, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            {{-- Arahkan ke halaman edit PO biasa untuk proses pelunasan --}}
                                            <a href="{{ route('admin.pembelian.edit', $po->id) }}" class="btn btn-success btn-sm" title="Proses Pelunasan">
                                                <i class="bi bi-credit-card"></i> Lunasi
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Tidak ada barang konsinyasi terjual yang perlu dilunasi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- =================================== --}}
        {{-- KONTEN TAB 2: STOK TERSEDIA        --}}
        {{-- =================================== --}}
        <div class="tab-pane fade" id="stok-content" role="tabpanel" aria-labelledby="stok-tab">
             <div class="card shadow-sm">
                <div class="card-header">Daftar Stok Konsinyasi yang Tersedia di Toko/Gudang</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                             <thead class="table-light">
                                <tr>
                                    <th>ID Batch</th>
                                    <th>Produk</th>
                                    <th>Supplier</th>
                                    <th>Lokasi</th>
                                    <th class="text-center">Jumlah Tersedia</th>
                                    <th>Tgl Terima</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stokKonsinyasiTersedia as $stok)
                                    <tr>
                                        <td>{{ $stok->id }}</td>
                                        <td>{{ $stok->produk->nama ?? 'N/A' }}</td>
                                        <td>{{ $stok->supplier->nama ?? 'N/A' }}</td>
                                        <td><span class="badge bg-info">{{ $stok->lokasi }}</span></td>
                                        <td class="text-center fw-bold">{{ $stok->jumlah }} Unit</td>
                                        <td>{{ \Carbon\Carbon::parse($stok->diterima_at)->isoFormat('D MMM YYYY') }}</td>
                                        <td class="text-center">
                                            {{-- Arahkan ke halaman penyesuaian stok yang sudah kita buat --}}
                                            <a href="{{ route('admin.penyesuaian_stok.create', ['id_stok_barang' => $stok->id, 'tipe' => 'PENGEMBALIAN_KONSINYASI']) }}" class="btn btn-warning btn-sm" title="Kembalikan ke Supplier">
                                                <i class="bi bi-box-arrow-left"></i> Kembalikan
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Tidak ada stok konsinyasi yang tersedia.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection