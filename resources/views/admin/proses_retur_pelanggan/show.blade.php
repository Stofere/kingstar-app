@extends('layouts.app')

@section('title', 'Detail Retur: ' . $returPenjualan->nomor_retur)

@push('styles')
<style>
    .detail-section { margin-bottom: 1.5rem; }
    .detail-label { font-weight: bold; color: #555; display: inline-block; min-width: 180px;}
    .card-sub-header { background-color: #e9ecef; padding: 0.75rem 1.25rem; border-bottom: 1px solid #dee2e6; font-weight: bold; }
    .table-detail-retur th { width: 25%; background-color: #f8f9fa; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Detail Nota Retur</h1>
        <a href="{{ route('admin.proses_retur_pelanggan.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Nota Retur No: {{ $returPenjualan->nomor_retur }}</h5>
        </div>
        <div class="card-body">
            {{-- BAGIAN INFO UMUM NOTA RETUR --}}
            <div class="row detail-section">
                <div class="col-md-6">
                    <p><span class="detail-label">Tanggal Retur:</span> <span>{{ \Carbon\Carbon::parse($returPenjualan->tanggal_retur)->isoFormat('dddd, D MMMM YYYY, HH:mm') }}</span></p>
                    <p><span class="detail-label">Kasir Proses Awal:</span> <span>{{ $returPenjualan->pengguna->nama ?? '-' }}</span></p>
                    <p><span class="detail-label">Status Proses:</span> 
                        <span class="badge bg-primary">{{ ucwords(strtolower(str_replace('_', ' ', $returPenjualan->status_retur))) }}</span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><span class="detail-label">Pelanggan:</span> <span>{{ $returPenjualan->penjualanAsal->pelanggan->nama ?? 'Umum' }}</span></p>
                    <p>
                        <span class="detail-label">Ref. Nota Penjualan:</span> 
                        <a href="{{ route('kasir.penjualan.nota', $returPenjualan->penjualanAsal->id) }}" target="_blank">
                            {{ $returPenjualan->penjualanAsal->nomor_penjualan }} <i class="bi bi-box-arrow-up-right small"></i>
                        </a>
                    </p>
                </div>
            </div>

            {{-- BAGIAN RINCIAN ITEM YANG DIRETUR --}}
            <div class="detail-section">
                <h5 class="card-sub-header">Rincian Item yang Diretur</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                         <thead class="table-light">
                            <tr>
                                <th class="text-center">No</th>
                                <th>Produk</th>
                                <th class="text-center">Jml Diretur</th>
                                <th>Alasan</th>
                                <th>Tindakan Lanjut</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- INI BAGIAN UTAMA YANG DIPERBAIKI --}}
                            @forelse($returPenjualan->detailReturPenjualan as $index => $detail)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $detail->detailPenjualanAsal->produk->nama ?? 'Produk Dihapus' }}</strong><br>
                                        @if($detail->nomor_seri_diretur)
                                            <small class="text-muted">SN: {{ $detail->nomor_seri_diretur }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $detail->jumlah_retur }}</td>
                                    <td>{{ ucwords(strtolower(str_replace('_', ' ', $detail->alasan_retur))) }}</td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            {{ ucwords(strtolower(str_replace('_', ' ', $detail->tindakan_lanjut))) }}
                                        </span>
                                    </td>
                                </tr>
                                @if($detail->catatan_pelanggan)
                                <tr class="table-info">
                                    <td></td>
                                    <td colspan="4">
                                        <small><strong>Catatan:</strong> {{ $detail->catatan_pelanggan }}</small>
                                    </td>
                                </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Tidak ada rincian item pada nota retur ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- BAGIAN CATATAN GLOBAL --}}
            @if($returPenjualan->catatan_internal_retur)
            <div class="detail-section">
                <h5 class="card-sub-header">Catatan Internal (Untuk Nota Retur Ini)</h5>
                <div class="p-3 bg-light rounded">
                    <p class="mb-0 fst-italic">{{ $returPenjualan->catatan_internal_retur }}</p>
                </div>
            </div>
            @endif
        </div>
        <div class="card-footer text-end">
            <a href="{{ route('admin.proses_retur_pelanggan.nota_retur', $returPenjualan->id) }}" class="btn btn-info" target="_blank">
                <i class="bi bi-printer me-1"></i> Cetak Bukti Retur
            </a>
        </div>
    </div>
</div>
@endsection