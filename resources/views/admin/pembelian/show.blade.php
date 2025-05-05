@extends('layouts.app')

@section('title', 'Detail Pembelian: ' . ($pembelian->nomor_pembelian ?? $pembelian->id))

@push('styles')
<style>
    .detail-label { font-weight: 600; }
    .badge { font-size: 0.9em; }
</style>
@endpush

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Detail Pembelian</h1>
        <div>
            @if(in_array($pembelian->status_pembelian, ['DRAFT', 'DIPESAN']))
                <a href="{{ route('admin.pembelian.edit', $pembelian->id) }}" class="btn btn-warning btn-sm me-2">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
            @endif
             {{-- Tombol Cetak (jika ada) --}}
             {{-- <a href="{{ route('admin.pembelian.print', $pembelian->id) }}" class="btn btn-secondary btn-sm me-2" target="_blank">
                <i class="bi bi-printer"></i> Cetak
            </a> --}}
            <a href="{{ route('admin.pembelian.index') }}" class="btn btn-light btn-sm border">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- Informasi Header Pembelian --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Informasi Pembelian</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <span class="detail-label">Nomor Pembelian:</span>
                    <p>{{ $pembelian->nomor_pembelian ?? '-' }}</p>
                </div>
                <div class="col-md-6">
                    <span class="detail-label">Nomor Faktur Supplier:</span>
                    <p>{{ $pembelian->nomor_faktur_supplier ?? '-' }}</p>
                </div>
                <div class="col-md-6">
                    <span class="detail-label">Supplier:</span>
                    <p>{{ $pembelian->supplier->nama ?? 'N/A' }}</p>
                </div>
                 <div class="col-md-6">
                    <span class="detail-label">Tanggal Pembelian:</span>
                    <p>{{ $pembelian->tanggal_pembelian->isoFormat('dddd, D MMMM YYYY') }}</p>
                </div>
                 <div class="col-md-6">
                    <span class="detail-label">Status Pembelian:</span>
                    <p>
                         @php
                            $statusClass = 'secondary';
                            if ($pembelian->status_pembelian == 'DIPESAN') $statusClass = 'info';
                            elseif ($pembelian->status_pembelian == 'PENGIRIMAN') $statusClass = 'primary';
                            elseif ($pembelian->status_pembelian == 'TIBA_SEBAGIAN') $statusClass = 'warning';
                            elseif ($pembelian->status_pembelian == 'SELESAI') $statusClass = 'success';
                            elseif ($pembelian->status_pembelian == 'DIBATALKAN') $statusClass = 'danger';
                        @endphp
                        <span class="badge bg-{{ $statusClass }}">{{ $pembelian->status_pembelian }}</span>
                    </p>
                </div>
                 <div class="col-md-6">
                    <span class="detail-label">Status Pembayaran:</span>
                    <p>
                        @php
                            $bayarClass = 'danger';
                            if ($pembelian->status_pembayaran == 'LUNAS') $bayarClass = 'success';
                            elseif ($pembelian->status_pembayaran == 'JATUH_TEMPO') $bayarClass = 'warning';
                        @endphp
                        <span class="badge bg-{{ $bayarClass }}">{{ $pembelian->status_pembayaran }}</span>
                        @if($pembelian->status_pembayaran == 'LUNAS' && $pembelian->dibayar_at)
                            <small class="text-muted">(Pada: {{ $pembelian->dibayar_at->isoFormat('D MMM YYYY') }})</small>
                        @endif
                    </p>
                </div>
                 <div class="col-md-6">
                    <span class="detail-label">Metode Pembayaran:</span>
                    <p>{{ $pembelian->metode_pembayaran ?? '-' }}</p>
                </div>
                 <div class="col-md-6">
                    <span class="detail-label">Dicatat oleh:</span>
                    <p>{{ $pembelian->pengguna->nama ?? 'N/A' }}</p>
                </div>
                 @if($pembelian->catatan)
                 <div class="col-12">
                    <span class="detail-label">Catatan:</span>
                    <p>{{ $pembelian->catatan }}</p>
                 </div>
                 @endif
            </div>
        </div>
    </div>

    {{-- Detail Item Pembelian --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Detail Item</h5>
        </div>
        <div class="card-body p-0"> {{-- p-0 agar tabel rapat --}}
            <div class="table-responsive">
                <table class="table table-striped table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th>Kode Produk</th>
                            <th class="text-center">Jumlah Dipesan</th>
                            <th class="text-center">Jumlah Diterima</th>
                            <th class="text-end">Harga Beli Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pembelian->detailPembelian as $detail)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $detail->produk->nama ?? 'N/A' }}</td>
                            <td>{{ $detail->produk->kode_produk ?? '-' }}</td>
                            <td class="text-center">{{ $detail->jumlah }}</td>
                            <td class="text-center">{{ $detail->jumlah_diterima }}</td>
                            <td class="text-end">Rp {{ number_format($detail->harga_beli, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($detail->jumlah * $detail->harga_beli, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Tidak ada item detail.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="6" class="text-end fw-bold">Grand Total</td>
                            <td class="text-end fw-bold">Rp {{ number_format($pembelian->total_harga, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

     <div class="text-end">
         <a href="{{ route('admin.pembelian.index') }}" class="btn btn-light border">
             <i class="bi bi-arrow-left"></i> Kembali ke Daftar
         </a>
     </div>

</div>
@endsection

@push('scripts')
    {{-- Tidak perlu JS khusus untuk halaman show ini biasanya --}}
@endpush