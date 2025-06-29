@extends('layouts.app')
@section('title', 'Laporan Analisis Harga Beli')

@push('styles')
<style>
    .price-change-up { color: #d9534f; } /* Merah untuk naik */
    .price-change-down { color: #5cb85c; } /* Hijau untuk turun */
    .table th, .table td { vertical-align: middle; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Laporan Analisis Harga Beli</h1>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Ringkasan Harga Beli per Produk</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th class="text-end">Harga Beli Terakhir</th>
                            <th>Supplier Terakhir</th>
                            <th class="text-end">Harga Rata-rata</th>
                            <th class="text-end">Harga Terendah</th>
                            <th class="text-end">Harga Tertinggi</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($produks as $index => $produk)
                            <tr>
                                <td>{{ $produks->firstItem() + $index }}</td>
                                <td>
                                    <strong>{{ $produk->nama }}</strong>
                                    <small class="d-block text-muted">{{ $produk->kode_produk }}</small>
                                </td>
                                <td class="text-end">
                                    {{-- Mengambil harga dari relasi yang sudah di-load --}}
                                    @if($produk->detailPembelianTerbaru)
                                        Rp {{ number_format($produk->detailPembelianTerbaru->harga_beli, 0, ',', '.') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($produk->detailPembelianTerbaru && $produk->detailPembelianTerbaru->pembelian && $produk->detailPembelianTerbaru->pembelian->supplier)
                                        {{ $produk->detailPembelianTerbaru->pembelian->supplier->nama }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{-- Mengambil dari kolom agregat virtual 'harga_rata_rata' --}}
                                    Rp {{ number_format($produk->harga_rata_rata, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    Rp {{ number_format($produk->harga_terendah, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    Rp {{ number_format($produk->harga_tertinggi, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.laporan.harga_beli.show', $produk->id) }}" class="btn btn-info btn-sm" title="Lihat Riwayat Harga">
                                        <i class="bi bi-clock-history"></i> Riwayat
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Tidak ada data pembelian produk yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Navigasi Paginasi --}}
            <div class="mt-3">
                {{ $produks->links() }}
            </div>
        </div>
    </div>
</div>
@endsection