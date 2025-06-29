{{-- resources/views/admin/proses_retur_pelanggan/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Retur Penjualan: ' . $returPenjualan->nomor_retur)

@push('styles')
<style>
    .detail-section { margin-bottom: 1.5rem; }
    .detail-label { font-weight: bold; color: #555; display: inline-block; min-width: 180px;}
    .card-sub-header { background-color: #e9ecef; padding: 0.5rem 1rem; border-bottom: 1px solid #dee2e6; font-weight: bold; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Detail Retur Penjualan</h1>
        <a href="{{ route('admin.proses_retur_pelanggan.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Daftar Retur
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Retur Penjualan No: {{ $returPenjualan->nomor_retur }}</h5>
        </div>
        <div class="card-body">
            <div class="row detail-section">
                <div class="col-md-6">
                    <p><span class="detail-label">Tanggal Retur:</span> <span>{{ \Carbon\Carbon::parse($returPenjualan->tanggal_retur)->isoFormat('dddd, D MMMM YYYY, HH:mm') }}</span></p>
                    <p><span class="detail-label">Diproses oleh Kasir:</span> <span>{{ $returPenjualan->pengguna->nama ?? '-' }}</span></p>
                    <p><span class="detail-label">Pelanggan:</span> <span>{{ $returPenjualan->detailPenjualan->penjualan->pelanggan->nama ?? 'Umum' }}</span></p>
                    <p>
                        <span class="detail-label">No. Nota Penjualan Asal:</span> 
                        <a href="{{ route('kasir.penjualan.nota', $returPenjualan->detailPenjualan->penjualan->id) }}" target="_blank">
                            {{ $returPenjualan->detailPenjualan->penjualan->nomor_penjualan }} <i class="bi bi-box-arrow-up-right small"></i>
                        </a>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><span class="detail-label">Alasan dari Pelanggan:</span> <span>{{ $returPenjualan->alasan_retur ?? '-' }}</span></p>
                    <p><span class="detail-label">Tindakan Lanjut:</span> <span>{{ ucwords(str_replace('_', ' ', $returPenjualan->tindakan_lanjut)) }}</span></p>
                    @if($returPenjualan->catatan_pelanggan)
                        <p><span class="detail-label">Catatan dari Pelanggan:</span> <span>{{ $returPenjualan->catatan_pelanggan }}</span></p>
                    @endif
                </div>
            </div>

            <div class="detail-section">
                <h6 class="card-sub-header">Informasi Item yang Diretur</h6>
                <div class="p-2">
                    <p><span class="detail-label">Produk:</span> <span>{{ $returPenjualan->detailPenjualan->produk->nama ?? '-' }} ({{ $returPenjualan->detailPenjualan->produk->kode_produk ?? '-' }})</span></p>
                    <p><span class="detail-label">Jumlah Diretur:</span> <span>{{ $returPenjualan->jumlah_retur }} unit</span></p>
                    @if($returPenjualan->nomor_seri_diretur)
                    <p><span class="detail-label">Nomor Seri Diretur:</span> <span>{{ str_replace(',', ', ', $returPenjualan->nomor_seri_diretur) }}</span></p>
                    @endif
                </div>
            </div>

            @if($returPenjualan->catatan_internal_retur)
            <div class="detail-section">
                <h6 class="card-sub-header">Catatan Internal Admin</h6>
                <div class="p-2">
                    <p>{{ $returPenjualan->catatan_internal_retur }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection