{{-- Ganti isi file resources/views/kasir/retur_penjualan/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detail Retur Penjualan: ' . $returPenjualan->nomor_retur)

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Detail Retur Penjualan</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Informasi Nota Retur</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>No. Retur:</strong> {{ $returPenjualan->nomor_retur }}</div>
                {{-- Mengakses data dari relasi penjualanAsal --}}
                <div class="col-md-4"><strong>No. Nota Asal:</strong> {{ $returPenjualan->penjualanAsal->nomor_penjualan ?? 'N/A' }}</div>
                <div class="col-md-4"><strong>Tanggal Retur:</strong> {{ \Carbon\Carbon::parse($returPenjualan->tanggal_retur)->isoFormat('D MMM YYYY, HH:mm') }}</div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-4"><strong>Pelanggan:</strong> {{ $returPenjualan->penjualanAsal->pelanggan->nama ?? 'Umum' }}</div>
                <div class="col-md-4"><strong>Kasir:</strong> {{ $returPenjualan->pengguna->nama ?? 'N/A' }}</div>
                <div class="col-md-4"><strong>Status:</strong>
                    @php
                        $statusRetur = $returPenjualan->status_retur;
                        $statusMapping = [
                            'MENUNGGU_PROSES_ADMIN' => ['class' => 'bg-warning text-dark', 'text' => 'Menunggu Proses Admin'],
                            'SELESAI_DIPROSES' => ['class' => 'bg-success', 'text' => 'Selesai Diproses'],
                        ];
                    @endphp
                    <span class="badge {{ $statusMapping[$statusRetur]['class'] ?? 'bg-secondary' }}">
                        {{ $statusMapping[$statusRetur]['text'] ?? ucwords(str_replace('_', ' ', $statusRetur)) }}
                    </span>
                </div>
            </div>
            @if($returPenjualan->catatan_internal_retur)
                <div class="mt-3">
                    <strong>Catatan Global Retur:</strong>
                    <p class="mb-0 fst-italic">{{ $returPenjualan->catatan_internal_retur }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Rincian Item yang Diretur</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th class="text-center">Jumlah Diretur</th>
                            <th>Nomor Seri Diretur</th>
                            <th>Alasan Retur</th>
                            <th>Tindakan Lanjut Awal</th>
                            <th>Catatan Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- INI BAGIAN UTAMA YANG DIPERBAIKI --}}
                        {{-- Kita loop melalui relasi detailReturPenjualan --}}
                        @forelse($returPenjualan->detailReturPenjualan as $index => $detail)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    {{-- Mengakses info produk melalui relasi bertingkat --}}
                                    <strong>{{ $detail->detailPenjualanAsal->produk->nama ?? 'Produk Dihapus' }}</strong><br>
                                    <small class="text-muted">{{ $detail->detailPenjualanAsal->produk->kode_produk ?? 'N/A' }}</small>
                                </td>
                                <td class="text-center">{{ $detail->jumlah_retur }}</td>
                                <td>{{ $detail->nomor_seri_diretur ?: '-' }}</td>
                                <td>{{ ucwords(strtolower(str_replace('_', ' ', $detail->alasan_retur))) }}</td>
                                <td>{{ ucwords(strtolower(str_replace('_', ' ', $detail->tindakan_lanjut))) }}</td>
                                <td>{{ $detail->catatan_pelanggan ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">Tidak ada rincian item untuk retur ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="{{ route('kasir.retur_penjualan.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Daftar Retur
            </a>
            
            <a href="{{ route('kasir.retur_penjualan.nota_retur', $returPenjualan->id) }}" target="_blank" class="btn btn-info">
                <i class="bi bi-printer me-1"></i> Cetak Ulang Bukti Retur
            </a>
        </div>
    </div>
</div>
@endsection