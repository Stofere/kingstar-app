@extends('layouts.app')
@section('title', 'Form Retur Penjualan: ' . $penjualan->nomor_penjualan)

@push('styles')
    <style>
        .item-retur-card { margin-bottom: 1.5rem; border-left: 5px solid #0dcaf0; }
        .required-label::after { content: " *"; color: red; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Form Retur Penjualan</h1>

    <form action="{{ route('kasir.retur_penjualan.store', $penjualan->id) }}" method="POST" id="form-retur-penjualan">
        @csrf
        {{-- Info Header Transaksi --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white"><h5 class="mb-0">Detail Transaksi Penjualan Asal</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><strong>No. Nota Asal:</strong> {{ $penjualan->nomor_penjualan }}</div>
                    <div class="col-md-4"><strong>Pelanggan:</strong> {{ $penjualan->pelanggan->nama ?? 'Umum' }}</div>
                    <div class="col-md-4"><strong>Tanggal Jual:</strong> {{ Carbon\Carbon::parse($penjualan->tanggal_penjualan)->isoFormat('D MMM YYYY, HH:mm') }}</div>
                </div>
            </div>
        </div>

        {{-- Form Utama --}}
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark"><h5 class="mb-0">Item yang Akan Diretur</h5></div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger pb-0">
                        <p><strong>Terjadi Kesalahan:</strong></p>
                        <ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                    </div>
                @endif
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tanggal_retur" class="form-label required-label">Tanggal Retur:</label>
                        <input type="datetime-local" class="form-control" id="tanggal_retur" name="tanggal_retur" value="{{ old('tanggal_retur', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                </div>

                @foreach ($detailItemsUntukForm as $index => $item)
                    <div class="card item-retur-card shadow-sm">
                        <div class="card-body">
                            {{-- Input-input tersembunyi --}}
                            <input type="hidden" name="items_retur[{{ $index }}][id_dpsa_asal]" value="{{ $item->id_dpsa_asal }}">
                            @if($item->nomor_seri_diretur)
                                <input type="hidden" name="items_retur[{{ $index }}][nomor_seri_diretur]" value="{{ $item->nomor_seri_diretur }}">
                            @endif

                            {{-- Info Produk & Batch --}}
                            <h6 class="card-title">{{ $item->produk->nama }}</h6>
                            <p class="card-text mb-2">
                                <small class="d-block text-muted">{{ $item->info_batch }}</small>
                                @if($item->nomor_seri_diretur)
                                    <small class="d-block">Nomor Seri: <span class="badge bg-secondary">{{ $item->nomor_seri_diretur }}</span></small>
                                @endif
                            </p>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="jumlah_retur_{{$index}}" class="form-label required-label">Jumlah Diretur:</label>
                                    @if($item->nomor_seri_diretur)
                                        <input type="text" class="form-control" id="jumlah_retur_{{$index}}" value="1" readonly>
                                        <input type="hidden" name="items_retur[{{ $index }}][jumlah_retur]" value="1">
                                    @else
                                        <input type="number" class="form-control" id="jumlah_retur_{{$index}}" name="items_retur[{{ $index }}][jumlah_retur]" value="{{ old('items_retur.'.$index.'.jumlah_retur', $item->jumlah_retur_maksimal) }}" min="1" max="{{ $item->jumlah_retur_maksimal }}" required>
                                    @endif
                                </div>
                                <div class="col-md-8"> {{-- Lebarkan kolom alasan --}}
                                    <label for="alasan_retur_{{$index}}" class="form-label required-label">Alasan Retur:</label>
                                    <select class="form-select" id="alasan_retur_{{$index}}" name="items_retur[{{ $index }}][alasan_retur]" required>
                                        <option value="">Pilih Alasan...</option>
                                        @foreach ($alasanReturOptions as $key => $value)
                                            <option value="{{ $key }}" {{ old('items_retur.'.$index.'.alasan_retur') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- ### KOLOM TINDAKAN DIHAPUS DARI SINI ### --}}
                            </div>
                            
                            <div class="mt-2">
                                <label for="catatan_item_{{ $index }}" class="form-label">Catatan Pelanggan (Item ini):</label>
                                <textarea class="form-control form-control-sm" id="catatan_item_{{ $index }}" name="items_retur[{{ $index }}][catatan_tambahan_item]" rows="1">{{ old('items_retur.'.$index.'.catatan_tambahan_item') }}</textarea>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="mt-3">
                    <label for="catatan_global_retur" class="form-label">Catatan Internal (seluruh retur):</label>
                    <textarea class="form-control" id="catatan_global_retur" name="catatan_global_retur" rows="2">{{ old('catatan_global_retur') }}</textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('kasir.retur_penjualan.cari_transaksi') }}" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-danger" id="btn-simpan-retur">
                    <i class="bi bi-arrow-return-left me-1"></i> Proses Retur
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
