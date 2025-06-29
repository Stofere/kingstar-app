{{-- kasir/retur_penjualan/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Retur Penjualan: ' . $returPenjualan->nomor_retur)

@push('styles')
<style>
    .detail-section { margin-bottom: 1.5rem; }
    .detail-label { font-weight: bold; color: #555; }
    .card-sub-header { background-color: #e9ecef; padding: 0.5rem 1rem; border-bottom: 1px solid #dee2e6; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Detail Retur Penjualan</h1>
        <a href="{{ route('kasir.retur_penjualan.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Daftar Retur
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Retur No: {{ $returPenjualan->nomor_retur }}</h5>
        </div>
        <div class="card-body">
            <div class="row detail-section">
                <div class="col-md-6">
                    <p><span class="detail-label">Tanggal Retur:</span> {{ Carbon\Carbon::parse($returPenjualan->tanggal_retur)->isoFormat('dddd, D MMMM YYYY, HH:mm') }}</p>
                    <p><span class="detail-label">Diproses oleh Kasir:</span> {{ $returPenjualan->pengguna->nama ?? '-' }}</p>
                </div>
                <div class="col-md-6">
                    <p><span class="detail-label">Pelanggan:</span> {{ $returPenjualan->detailPenjualan->penjualan->pelanggan->nama ?? 'Umum' }}</p>
                    <p><span class="detail-label">No. Nota Penjualan Asal:</span>
                            {{ $returPenjualan->detailPenjualan->penjualan->nomor_penjualan ?? '-' }} 
                    </p>
                </div>
            </div>

            <div class="detail-section">
                <h6 class="card-sub-header">Informasi Item yang Diretur</h6>
                <div class="p-2">
                    <p><span class="detail-label">Produk:</span> {{ $returPenjualan->detailPenjualan->produk->nama ?? '-' }} ({{ $returPenjualan->detailPenjualan->produk->kode_produk ?? '-' }})</p>
                    <p><span class="detail-label">Jumlah Diretur:</span> {{ $returPenjualan->jumlah_retur }} unit</p>
                    @if($returPenjualan->nomor_seri_diretur)
                    <p><span class="detail-label">Nomor Seri Diretur:</span> {{ str_replace(',', ', ', $returPenjualan->nomor_seri_diretur) }}</p>
                    @endif
                    <p><span class="detail-label">Alasan Retur:</span> {{ $returPenjualan->alasan ? \App\Helpers\ReturHelper::formatAlasanRetur($returPenjualan->alasan) : '-' }}</p> {{-- Panggil helper jika ada --}}
                    <p><span class="detail-label">Tindakan Lanjut:</span> {{ $returPenjualan->tindakan_lanjut ? \App\Helpers\ReturHelper::formatTindakanLanjut($returPenjualan->tindakan_lanjut) : '-' }}</p>
                    @if($returPenjualan->catatan_pelanggan)
                    <p><span class="detail-label">Catatan dari Pelanggan:</span> {{ $returPenjualan->catatan_pelanggan }}</p>
                    @endif
                </div>
            </div>

            @if($returPenjualan->catatan_global_retur)
            <div class="detail-section">
                <h6 class="card-sub-header">Catatan Global Retur</h6>
                <div class="p-2">
                    <p>{{ $returPenjualan->catatan_global_retur }}</p>
                </div>
            </div>
            @endif

            <div class="mt-3 text-center">
                <a href="{{ route('kasir.penjualan.nota', $returPenjualan->detailPenjualan->penjualan->id) }}" target="_blank" class="btn btn-primary mt-2">
                    {{ $returPenjualan->detailPenjualan->penjualan->nomor_penjualan ?? '-' }} <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

