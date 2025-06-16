@extends('layouts.app')
@section('title', 'Mulai Sesi Stok Opname Baru')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Formulir Sesi Stok Opname Baru</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('gudang.stok-opname.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="tanggal_opname" class="form-label">Tanggal Opname <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('tanggal_opname') is-invalid @enderror" id="tanggal_opname" name="tanggal_opname" value="{{ old('tanggal_opname', date('Y-m-d')) }}" required>
                            @error('tanggal_opname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="lokasi" class="form-label">Lokasi (Opsional)</label>
                            <select class="form-select" id="lokasi" name="lokasi">
                                <option value="">Semua Lokasi</option>
                                <option value="GUDANG" {{ old('lokasi') == 'GUDANG' ? 'selected' : '' }}>GUDANG</option>
                                <option value="TOKO" {{ old('lokasi') == 'TOKO' ? 'selected' : '' }}>TOKO</option>
                            </select>
                            <div class="form-text">Kosongkan jika ingin opname semua lokasi.</div>
                        </div>
                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan Awal (Opsional)</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3">{{ old('catatan') }}</textarea>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('gudang.stok-opname.index') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Mulai Sesi Opname</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection