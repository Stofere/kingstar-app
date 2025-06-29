@extends('layouts.app')

@section('title', 'Input Harga Beli Barang Konsinyasi')

@push('styles')
<style>
    .table-harga-konsinyasi th, .table-harga-konsinyasi td {
        vertical-align: middle;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Input Harga Beli Barang Konsinyasi</h1>

    <div class="card shadow-sm">
        <div class="card-header bg-warning">
            <h5 class="mb-0 text-dark"><i class="bi bi-tag-fill me-2"></i>Batch Menunggu Harga Pokok</h5>
        </div>
        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if($batchesMenungguHarga->isEmpty())
                <div class="alert alert-info text-center">
                    <p class="mb-0">Tidak ada barang konsinyasi yang menunggu input harga saat ini.</p>
                </div>
            @else
                <form action="{{ route('admin.konsinyasi.input_harga.store') }}" method="POST">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-harga-konsinyasi">
                            <thead class="table-light">
                                <tr>
                                    <th>ID Batch</th>
                                    <th>Produk</th>
                                    <th>Supplier</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Tgl Diterima Gudang</th>
                                    <th style="width: 25%;" class="required-label">Harga Beli (Pokok)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batchesMenungguHarga as $batch)
                                <tr>
                                    <td>{{ $batch->id }}</td>
                                    <td>
                                        <strong>{{ $batch->produk->nama ?? 'N/A' }}</strong><br>
                                        <small class="text-muted">{{ $batch->produk->kode_produk ?? '' }}</small>
                                    </td>
                                    <td>{{ $batch->supplier->nama ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $batch->jumlah }}</td>
                                    <td>{{ \Carbon\Carbon::parse($batch->diterima_at)->isoFormat('D MMM YYYY, HH:mm') }}</td>
                                    <td>
                                        <input type="hidden" name="batches[{{ $loop->index }}][id]" value="{{ $batch->id }}">
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="batches[{{ $loop->index }}][harga_beli]"
                                                   class="form-control"
                                                   value="{{ old('batches.'.$loop->index.'.harga_beli') }}"
                                                   min="1"
                                                   placeholder="Masukkan harga pokok..." required>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-2"></i>Simpan Semua Harga</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection