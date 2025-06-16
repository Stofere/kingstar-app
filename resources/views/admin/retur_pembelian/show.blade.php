@extends('layouts.app') 

@section('title', 'Detail Retur Pembelian: ' . $returPembelian->nomor_retur)

@push('styles')
<style>
    .detail-section { margin-bottom: 1.5rem; }
    .detail-label { font-weight: bold; color: #555; display: inline-block; min-width: 180px;}
    .card-sub-header { background-color: #e9ecef; padding: 0.5rem 1rem; border-bottom: 1px solid #dee2e6; font-weight: bold; }
    .value-text { }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Detail Retur Pembelian</h1>
        <div>
            
            <a href="{{ route('admin.retur_pembelian.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Daftar Retur
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Retur Pembelian No: {{ $returPembelian->nomor_retur }}</h5>
        </div>
        <div class="card-body">
            <div class="row detail-section">
                <div class="col-md-6">
                    <p><span class="detail-label">Tanggal Retur:</span> <span class="value-text">{{ Carbon\Carbon::parse($returPembelian->tanggal_retur)->isoFormat('dddd, D MMMM YYYY, HH:mm') }}</span></p>
                    <p><span class="detail-label">Diproses oleh Admin:</span> <span class="value-text">{{ $returPembelian->pengguna->nama ?? '-' }}</span></p>
                    <p><span class="detail-label">Supplier Tujuan Retur:</span> <span class="value-text">{{ $returPembelian->stokBarang->supplier->nama ?? 'N/A (Supplier dari Batch)' }}</span></p>
                    @if($returPembelian->stokBarang->detailPembelian && $returPembelian->stokBarang->detailPembelian->pembelian)
                        <p><span class="detail-label">No. PO Asal (Jika Ada):</span>
                            <a href="{{ route('admin.pembelian.show', $returPembelian->stokBarang->detailPembelian->pembelian->id) }}" target="_blank">
                                {{ $returPembelian->stokBarang->detailPembelian->pembelian->nomor_pembelian }} <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </p>
                    @endif
                </div>
                <div class="col-md-6">
                    <p><span class="detail-label">Alasan Retur:</span> <span class="value-text">{{ $returPembelian->alasan_retur ? \App\Helpers\ReturHelper::formatAlasanReturPembelian($returPembelian->alasan_retur) : '-' }}</span></p> {{-- Butuh helper baru --}}
                    <p><span class="detail-label">Tindak Lanjut Diharapkan dari Supplier:</span> <span class="value-text">{{ $returPembelian->tindakan_lanjut_supplier ? \App\Helpers\ReturHelper::formatTindakanLanjutSupplier($returPembelian->tindakan_lanjut_supplier) : '-' }}</span></p>
                    @if($returPembelian->catatan_ke_supplier)
                        <p><span class="detail-label">Catatan untuk Supplier:</span> <span class="value-text">{{ $returPembelian->catatan_ke_supplier }}</span></p>
                    @endif
                </div>
            </div>

            <div class="detail-section">
                <h6 class="card-sub-header">Informasi Batch dan Item yang Diretur</h6>
                <div class="p-2">
                    <p><span class="detail-label">ID Batch Stok Diretur:</span> <span class="value-text">{{ $returPembelian->id_stok_barang }}</span></p>
                    <p><span class="detail-label">Produk:</span> <span class="value-text">{{ $returPembelian->stokBarang->produk->nama ?? '-' }} ({{ $returPembelian->stokBarang->produk->kode_produk ?? '-' }})</span></p>
                    <p><span class="detail-label">Jumlah Diretur dari Batch:</span> <span class="value-text">{{ $returPembelian->jumlah_retur }} unit</span></p>
                    @if($returPembelian->nomor_seri_diretur)
                    <p><span class="detail-label">Nomor Seri Diretur:</span> <span class="value-text">{{ str_replace(',', ', ', $returPembelian->nomor_seri_diretur) }}</span></p>
                    @endif
                     <p><span class="detail-label">Harga Beli Batch Asal:</span> <span class="value-text">{{ $returPembelian->stokBarang ? 'Rp ' . number_format($returPembelian->stokBarang->harga_beli, 0, ',', '.') : '-' }}</span></p>
                     <p><span class="detail-label">Tanggal Terima Batch Asal:</span> <span class="value-text">{{ $returPembelian->stokBarang ? Carbon\Carbon::parse($returPembelian->stokBarang->diterima_at)->isoFormat('D MMM YYYY') : '-' }}</span></p>
                </div>
            </div>

            @if($returPembelian->catatan_internal_retur)
            <div class="detail-section">
                <h6 class="card-sub-header">Catatan Internal Proses Retur</h6>
                <div class="p-2">
                    <p>{{ $returPembelian->catatan_internal_retur }}</p>
                </div>
            </div>
            @endif

            @php
                // Cek apakah PO pengganti sudah pernah dibuat dengan mencari di catatan
                $poPenggantiId = null;
                if ($returPembelian->catatan_internal_retur && str_starts_with($returPembelian->catatan_internal_retur, 'replacement_po_id:')) {
                    $poPenggantiId = explode(':', $returPembelian->catatan_internal_retur)[1];
                }
                $poPengganti = $poPenggantiId ? \App\Models\Pembelian::find($poPenggantiId) : null;
            @endphp

            {{-- REVISI TOTAL BLOK INI --}}
            @if ($returPembelian->tindakan_lanjut_supplier === 'PROSES_PENGGANTIAN_BARANG' && !$poPengganti)
                <hr>
                <div class="alert alert-info">
                    <h5 class="alert-heading">Tindak Lanjut</h5>
                    <p>Status retur ini adalah menunggu barang pengganti dari supplier. Jika Anda sudah mendapat konfirmasi bahwa barang akan dikirim, lanjutkan untuk membuat Purchase Order (PO) barang pengganti.</p>

                    {{-- Tombol sekarang menjadi link cerdas, bukan form submit --}}
                    <a href="{{ route('admin.pembelian.create', [
                            'from_retur' => $returPembelian->id,
                            'supplier' => $returPembelian->stokBarang->id_supplier,
                            'produk' => $returPembelian->stokBarang->id_produk,
                            'qty' => $returPembelian->jumlah_retur,
                            'nomor_retur' => $returPembelian->nomor_retur
                        ]) }}" class="btn btn-success">
                        <i class="bi bi-file-earmark-plus-fill me-1"></i> Buat PO Barang Pengganti
                    </a>
                </div>
            @elseif ($poPengganti)
                <hr>
                <div class="alert alert-success">
                    <h5 class="alert-heading">PO Barang Pengganti Telah Dibuat</h5>
                    <p class="mb-0">
                        PO untuk barang pengganti sudah dibuat dengan nomor:
                        <strong>
                            <a href="{{ route('admin.pembelian.show', $poPengganti->id) }}" target="_blank">
                                {{ $poPengganti->nomor_pembelian }}
                            </a>
                        </strong>.
                        Silakan tunggu konfirmasi dari bagian gudang saat barang tiba.
                    </p>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
