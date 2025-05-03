{{-- resources/views/admin/produk/form.blade.php --}}
@csrf
<div class="row">
    {{-- Kolom Kiri --}}
    <div class="col-md-8">
        {{-- Nama Produk --}}
        <div class="mb-3">
            <label for="nama" class="form-label">Nama Produk <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $produk->nama) }}" required>
            @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row">
            <div class="col-md-6">
                 {{-- Merk --}}
                <div class="mb-3">
                    <label for="id_merk" class="form-label">Merk</label>
                    <select class="form-select @error('id_merk') is-invalid @enderror" id="id_merk" name="id_merk">
                        <option value="" {{ old('id_merk', $produk->id_merk) ? '' : 'selected' }}>-- Tidak Ada Merk --</option>
                        @foreach ($merk as $id => $namaMerk)
                            <option value="{{ $id }}" {{ old('id_merk', $produk->id_merk) == $id ? 'selected' : '' }}>{{ $namaMerk }}</option>
                        @endforeach
                    </select>
                    @error('id_merk') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-6">
                {{-- Kode Produk --}}
                <div class="mb-3">
                    <label for="kode_produk" class="form-label">Kode Produk</label>
                    <input type="text" class="form-control @error('kode_produk') is-invalid @enderror" id="kode_produk" name="kode_produk" value="{{ old('kode_produk', $produk->kode_produk) }}">
                    @error('kode_produk') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- Deskripsi --}}
        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi" rows="4">{{ old('deskripsi', $produk->deskripsi) }}</textarea>
            @error('deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row">
            <div class="col-md-6">
                {{-- Harga Jual Standart --}}
                <div class="mb-3">
                    <label for="harga_jual_standart" class="form-label">Harga Jual Standar</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control @error('harga_jual_standart') is-invalid @enderror" id="harga_jual_standart" name="harga_jual_standart" value="{{ old('harga_jual_standart', $produk->harga_jual_standart) }}" min="0" step="any">
                    </div>
                    @error('harga_jual_standart') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>
             <div class="col-md-6">
                 {{-- Satuan --}}
                <div class="mb-3">
                    <label for="satuan" class="form-label">Satuan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('satuan') is-invalid @enderror" id="satuan" name="satuan" value="{{ old('satuan', $produk->satuan ?? 'PCS') }}" required>
                    @error('satuan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="row">
             <div class="col-md-6">
                {{-- Memiliki Serial --}}
                <div class="mb-3">
                    <label class="form-label d-block">Memiliki Nomor Seri? <span class="text-danger">*</span></label>
                    {{-- Gunakan value="1" untuk Ya dan value="0" untuk Tidak --}}
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="memiliki_serial" id="serial_ya" value="1" {{ old('memiliki_serial', $produk->memiliki_serial) == '1' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="serial_ya">Ya</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="memiliki_serial" id="serial_tidak" value="0" {{ old('memiliki_serial', $produk->memiliki_serial) == '0' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="serial_tidak">Tidak</label>
                    </div>
                    @error('memiliki_serial') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-6">
                {{-- Durasi Garansi --}}
                <div class="mb-3">
                    <label for="durasi_garansi_standar_bulan" class="form-label">Durasi Garansi Standar (Bulan)</label>
                    <input type="number" class="form-control @error('durasi_garansi_standar_bulan') is-invalid @enderror" id="durasi_garansi_standar_bulan" name="durasi_garansi_standar_bulan" value="{{ old('durasi_garansi_standar_bulan', $produk->durasi_garansi_standar_bulan) }}" min="0">
                    <small class="form-text text-muted">Kosongkan jika tidak ada garansi standar.</small>
                    @error('durasi_garansi_standar_bulan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

         {{-- Status --}}
        <div class="mb-3">
            <label class="form-label d-block">Status Produk <span class="text-danger">*</span></label>
             {{-- Gunakan value="1" untuk Aktif dan value="0" untuk Tidak Aktif --}}
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="status_aktif" value="1" {{ old('status', $produk->status) == '1' ? 'checked' : '' }} required>
                <label class="form-check-label" for="status_aktif">Aktif</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="status_tidak_aktif" value="0" {{ old('status', $produk->status) == '0' ? 'checked' : '' }} required>
                <label class="form-check-label" for="status_tidak_aktif">Tidak Aktif</label>
            </div>
            @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

    </div>

    {{-- Kolom Kanan --}}
    <div class="col-md-4">
        {{-- Gambar Produk --}}
        <div class="mb-3">
            <label for="gambar" class="form-label">Gambar Produk</label>
            {{-- Tampilkan gambar saat ini (hanya relevan untuk edit, tapi tidak masalah ada di sini) --}}
            @if ($produk->gambar && Storage::exists('public/produk/' . $produk->gambar))
                <img src="{{ Storage::url('produk/' . $produk->gambar) }}" alt="Gambar {{ $produk->nama }}" class="img-thumbnail mb-2 d-block" style="max-height: 150px;">
            @elseif ($produk->exists)
                 <div class="text-muted mb-2">(Tidak ada gambar)</div>
            @endif
            <input class="form-control @error('gambar') is-invalid @enderror" type="file" id="gambar" name="gambar" accept="image/*">
            <small class="form-text text-muted">Kosongkan jika tidak ada gambar. Maks: 2MB (JPG, PNG, GIF, WEBP).</small>
            @error('gambar') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<hr>
<div class="d-flex justify-content-end">
    <a href="{{ route('admin.produk.index') }}" class="btn btn-secondary me-2">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i> {{ $produk->exists ? 'Update Produk' : 'Simpan Produk' }} {{-- Teks tombol dinamis --}}
    </button>
</div>