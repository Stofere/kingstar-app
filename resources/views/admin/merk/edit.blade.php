@extends('layouts.app')

@section('title', 'Edit Merk: ' . $merk->nama)

@section('content')
<div class="container">
    <h1 class="mb-4">Edit Merk: <span class="fw-normal">{{ $merk->nama }}</span></h1>
     <div class="card shadow-sm">
         <div class="card-header bg-light">
             <h5 class="mb-0">Form Edit Merk</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.merk.update', $merk->id) }}" method="POST">
                @csrf
                @method('PUT') {{-- Method untuk update --}}

                {{-- Nama Merk --}}
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Merk <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $merk->nama) }}" required autofocus>
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                 <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.merk.index') }}" class="btn btn-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection