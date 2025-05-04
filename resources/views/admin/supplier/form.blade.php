{{-- resources/views/admin/supplier/form.blade.php --}}
@csrf
<div class="row">
    <div class="col-md-6">
        {{-- Nama Supplier --}}
        <div class="mb-3">
            <label for="nama" class="form-label">Nama Supplier <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $supplier->nama) }}" required>
            @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-6">
        {{-- Nomor Telepon --}}
        <div class="mb-3">
            <label for="telepon" class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('telepon') is-invalid @enderror" id="telepon" name="telepon" value="{{ old('telepon', $supplier->telepon) }}" required>
            @error('telepon') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $supplier->email) }}">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
     <div class="col-md-6">
        {{-- Status --}}
        <div class="mb-3">
            <label class="form-label d-block">Status <span class="text-danger">*</span></label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="status_aktif" value="1" {{ old('status', $supplier->status) == '1' ? 'checked' : '' }} required>
                <label class="form-check-label" for="status_aktif">Aktif</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="status_tidak_aktif" value="0" {{ old('status', $supplier->status) == '0' ? 'checked' : '' }} required>
                <label class="form-check-label" for="status_tidak_aktif">Tidak Aktif</label>
            </div>
            @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

{{-- Alamat --}}
<div class="mb-3">
    <label for="alamat" class="form-label">Alamat</label>
    <textarea class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" rows="3">{{ old('alamat', $supplier->alamat) }}</textarea>
    @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<hr>
<div class="d-flex justify-content-end">
    <a href="{{ route('admin.supplier.index') }}" class="btn btn-secondary me-2">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i> {{ $supplier->exists ? 'Update Supplier' : 'Simpan Supplier' }}
    </button>
</div>