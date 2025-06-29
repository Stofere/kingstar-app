@extends('layouts.app')
@section('title', 'Riwayat Harga Beli: ' . $produk->nama)

@push('styles')
<style>
    .price-change-up { color: #d9534f; font-weight: bold; } /* Merah untuk NAIK */
    .price-change-down { color: #5cb85c; font-weight: bold; } /* Hijau untuk TURUN */
    .price-stable { color: #777; } /* Abu-abu untuk STABIL */
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1 h3">Riwayat Harga Beli</h1>
            <h2 class="mb-0 h5 text-muted">Produk: <span class="fw-bold">{{ $produk->nama }} ({{ $produk->kode_produk }})</span></h2>
        </div>
        <a href="{{ route('admin.laporan.harga_beli.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Ringkasan
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header"><h5 class="mb-0">Tabel Riwayat</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal Pembelian</th>
                            <th>No. PO</th>
                            <th>Supplier</th>
                            <th class="text-end">Harga Beli</th>
                            <th class="text-center">Perubahan dari Sebelumnya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hargaSebelumnya = null; @endphp
                        @forelse ($riwayatHarga as $detail)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($detail->pembelian->tanggal_pembelian)->isoFormat('D MMMM YYYY') }}</td>
                                <td>
                                    <a href="{{ route('admin.pembelian.show', $detail->pembelian->id) }}" target="_blank">
                                        {{ $detail->pembelian->nomor_pembelian }}
                                    </a>
                                </td>
                                <td>{{ $detail->pembelian->supplier->nama ?? '-' }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format($detail->harga_beli, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    {{-- ======================================================= --}}
                                    {{-- ## AWAL LOGIKA PERBANDINGAN YANG DIPERBAIKI         ## --}}
                                    {{-- ======================================================= --}}
                                    @if ($hargaSebelumnya !== null)
                                        @php
                                            // Selisih = Harga Saat Ini - Harga Sebelumnya
                                            $selisih = $detail->harga_beli - $hargaSebelumnya;
                                            
                                            // Persentase = (Selisih / Harga Sebelumnya) * 100
                                            // Cek agar tidak terjadi division by zero
                                            $persentase = ($hargaSebelumnya > 0) ? ($selisih / $hargaSebelumnya) * 100 : 0;
                                        @endphp
                                        
                                        @if ($selisih > 0)
                                            <span class="price-change-up">
                                                <i class="bi bi-arrow-up-short"></i> Naik Rp {{ number_format($selisih) }} ({{ number_format($persentase, 1) }}%)
                                            </span>
                                        @elseif ($selisih < 0)
                                            <span class="price-change-down">
                                                <i class="bi bi-arrow-down-short"></i> Turun Rp {{ number_format(abs($selisih)) }} ({{ number_format(abs($persentase), 1) }}%)
                                            </span>
                                        @else
                                            <span class="price-stable">- Stabil -</span>
                                        @endif
                                    @else
                                        {{-- Ini adalah baris pertama, tidak ada pembanding --}}
                                        <span class="text-muted">-</span>
                                    @endif
                                    {{-- ======================================================= --}}
                                    {{-- ## AKHIR LOGIKA PERBANDINGAN YANG DIPERBAIKI          ## --}}
                                    {{-- ======================================================= --}}
                                </td>
                            </tr>
                            {{-- Setelah menampilkan baris, update $hargaSebelumnya untuk iterasi berikutnya --}}
                            @php $hargaSebelumnya = $detail->harga_beli; @endphp
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Belum ada riwayat pembelian untuk produk ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Navigasi Paginasi akan berfungsi jika Anda menggunakan paginate() di controller --}}
            {{-- <div class="mt-3">{{ $riwayatHarga->links() }}</div> --}}
        </div>
    </div>
</div>
@endsection