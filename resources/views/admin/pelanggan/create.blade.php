@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('title', 'Tambah Pelanggan Baru')

@section('content')
<div class="container">
    <h1 class="mb-4">Tambah Pelanggan Baru</h1>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Form Pelanggan</h5>
        </div>
        <div class="card-body">
            {{-- Tampilkan error validasi jika ada --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Oops!</strong> Terdapat beberapa masalah dengan input Anda:
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('admin.pelanggan.store') }}" method="POST">
                @csrf {{-- Token CSRF untuk keamanan --}}

                <div class="mb-3">
                    <label for="nama" class="form-label required-label">Nama Pelanggan</label>
                    <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama') }}" required>
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="telepon" class="form-label">No. Telepon</label>
                    <input type="text" class="form-control @error('telepon') is-invalid @enderror" id="telepon" name="telepon" value="{{ old('telepon') }}">
                    @error('telepon')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="alamat" class="form-label">Alamat</label>
                    <textarea class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" rows="3">{{ old('alamat') }}</textarea>
                    @error('alamat')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label required-label">Status</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input @error('status') is-invalid @enderror" type="radio" name="status" id="status_aktif" value="1" {{ old('status', 1) == 1 ? 'checked' : '' }} required>
                            <label class="form-check-label" for="status_aktif">Aktif</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input @error('status') is-invalid @enderror" type="radio" name="status" id="status_tidak_aktif" value="0" {{ old('status', 1) == 0 ? 'checked' : '' }} required>
                            <label class="form-check-label" for="status_tidak_aktif">Tidak Aktif</label>
                        </div>
                         @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div> {{-- d-block agar pesan error muncul --}}
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                    <a href="{{ route('admin.pelanggan.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@endpush