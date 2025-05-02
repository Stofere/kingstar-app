@extends('layouts.app')

@section('title', 'Edit Pengguna: ' . $pengguna->nama)

@section('content')
<div class="container">
    <h1>Edit Pengguna: {{ $pengguna->nama }}</h1>
    <div class="card">
        <div class="card-header">Form Edit Pengguna</div>
        <div class="card-body">
            <form action="{{ route('admin.pengguna.update', $pengguna->id) }}" method="POST">
                @csrf
                @method('PUT') {{-- Method untuk update --}}

                {{-- Nama --}}
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $pengguna->nama) }}" required>
                    @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Username --}}
                <div class="mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $pengguna->username) }}" required>
                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Password (Opsional) --}}
                <div class="mb-3">
                    <label for="password" class="form-label">Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                </div>

                {{-- Role --}}
                <div class="mb-3">
                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                        <option value="" disabled>Pilih Role...</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" {{ old('role', $pengguna->role) == $role ? 'selected' : '' }}>{{ $role }}</option>
                        @endforeach
                    </select>
                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Status --}}
                 <div class="mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status_aktif" value="1" {{ old('status', $pengguna->status) == '1' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="status_aktif">Aktif</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status_tidak_aktif" value="0" {{ old('status', $pengguna->status) == '0' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="status_tidak_aktif">Tidak Aktif</label>
                    </div>
                    @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>


                <a href="{{ route('admin.pengguna.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
</div>
@endsection