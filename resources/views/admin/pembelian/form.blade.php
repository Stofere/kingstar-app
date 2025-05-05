{{-- ====================================================== --}}
{{-- INFORMASI PEMBELIAN (HEADER)                          --}}
{{-- ====================================================== --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Informasi Pembelian</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Supplier --}}
            <div class="col-md-6">
                <label for="id_supplier" class="form-label">Supplier <span class="text-danger">*</span></label>
                <select class="form-select @error('id_supplier') is-invalid @enderror" id="id_supplier" name="id_supplier" required data-placeholder="Pilih Supplier">
                    <option value=""></option>
                    @foreach ($suppliers as $id => $nama)
                        {{-- Gunakan old() atau data dari $pembelian jika ada --}}
                        <option value="{{ $id }}" {{ old('id_supplier', $pembelian->id_supplier ?? '') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
                @error('id_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Tanggal Pembelian --}}
            <div class="col-md-6">
                <label for="tanggal_pembelian" class="form-label">Tanggal Pembelian <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('tanggal_pembelian') is-invalid @enderror" id="tanggal_pembelian" name="tanggal_pembelian" value="{{ old('tanggal_pembelian', isset($pembelian) ? $pembelian->tanggal_pembelian->format('Y-m-d') : date('Y-m-d')) }}" required>
                @error('tanggal_pembelian') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Nomor Pembelian Internal --}}
            <div class="col-md-6">
                <label for="nomor_pembelian" class="form-label">Nomor Pembelian (Internal)</label>
                <input type="text" class="form-control @error('nomor_pembelian') is-invalid @enderror" id="nomor_pembelian" name="nomor_pembelian" value="{{ old('nomor_pembelian', $pembelian->nomor_pembelian ?? '') }}" placeholder="Otomatis jika kosong">
                @error('nomor_pembelian') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Nomor Faktur Supplier --}}
            <div class="col-md-6">
                <label for="nomor_faktur_supplier" class="form-label">Nomor Faktur Supplier</label>
                <input type="text" class="form-control @error('nomor_faktur_supplier') is-invalid @enderror" id="nomor_faktur_supplier" name="nomor_faktur_supplier" value="{{ old('nomor_faktur_supplier', $pembelian->nomor_faktur_supplier ?? '') }}">
                @error('nomor_faktur_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

             {{-- Status Pembelian --}}
            <div class="col-md-6">
                <label for="status_pembelian" class="form-label">Status Pembelian <span class="text-danger">*</span></label>
                <select class="form-select @error('status_pembelian') is-invalid @enderror" id="status_pembelian" name="status_pembelian" required {{ isset($pembelian) && !in_array($pembelian->status_pembelian, ['DRAFT', 'DIPESAN']) ? 'disabled' : '' }}> {{-- Disable jika status tidak memungkinkan edit --}}
                    <option value="DRAFT" {{ old('status_pembelian', $pembelian->status_pembelian ?? 'DRAFT') == 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                    <option value="DIPESAN" {{ old('status_pembelian', $pembelian->status_pembelian ?? '') == 'DIPESAN' ? 'selected' : '' }}>DIPESAN</option>
                    {{-- Tambahkan status lain jika diperlukan & logikanya sesuai --}}
                    @if(isset($pembelian) && !in_array($pembelian->status_pembelian, ['DRAFT', 'DIPESAN']))
                     <option value="{{ $pembelian->status_pembelian }}" selected disabled>{{ $pembelian->status_pembelian }} (Tidak bisa diubah)</option>
                    @endif
                </select>
                @error('status_pembelian') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Status Pembayaran --}}
            <div class="col-md-6">
                <label for="status_pembayaran" class="form-label">Status Pembayaran <span class="text-danger">*</span></label>
                <select class="form-select @error('status_pembayaran') is-invalid @enderror" id="status_pembayaran" name="status_pembayaran" required>
                    <option value="BELUM_LUNAS" {{ old('status_pembayaran', $pembelian->status_pembayaran ?? 'BELUM_LUNAS') == 'BELUM_LUNAS' ? 'selected' : '' }}>BELUM LUNAS</option>
                    <option value="LUNAS" {{ old('status_pembayaran', $pembelian->status_pembayaran ?? '') == 'LUNAS' ? 'selected' : '' }}>LUNAS</option>
                    <option value="JATUH_TEMPO" {{ old('status_pembayaran', $pembelian->status_pembayaran ?? '') == 'JATUH_TEMPO' ? 'selected' : '' }}>JATUH TEMPO</option>
                </select>
                @error('status_pembayaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

             {{-- Tanggal Bayar (Opsional) --}}
             <div class="col-md-6" id="tanggal-bayar-group" style="{{ old('status_pembayaran', $pembelian->status_pembayaran ?? '') == 'LUNAS' ? '' : 'display: none;' }}">
                <label for="dibayar_at" class="form-label">Tanggal Bayar</label>
                <input type="date" class="form-control @error('dibayar_at') is-invalid @enderror" id="dibayar_at" name="dibayar_at" value="{{ old('dibayar_at', isset($pembelian) && $pembelian->dibayar_at ? $pembelian->dibayar_at->format('Y-m-d') : '') }}">
                @error('dibayar_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

        </div>
    </div>
</div>

{{-- ====================================================== --}}
{{-- DETAIL ITEM PEMBELIAN                                --}}
{{-- ====================================================== --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Detail Item Pembelian</h5>
    </div>
    <div class="card-body">
         @if ($errors->has('details') || $errors->has('details.*'))
            <div class="alert alert-danger">
                Terdapat kesalahan pada input detail item. Mohon periksa kembali.
                <ul>
                     @foreach ($errors->get('details.*') as $key => $messages)
                        @foreach($messages as $message)
                            <li>{{ $message }} (Baris: {{ (int) filter_var($key, FILTER_SANITIZE_NUMBER_INT) + 1 }})</li>
                        @endforeach
                     @endforeach
                </ul>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="detail-pembelian-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40%;">Produk <span class="text-danger">*</span></th>
                        <th style="width: 15%;">Jumlah <span class="text-danger">*</span></th>
                        <th style="width: 20%;">Harga Beli Satuan <span class="text-danger">*</span></th>
                        <th style="width: 20%;">Subtotal</th>
                        <th style="width: 5%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="detail-pembelian-body">
                    {{-- Jika mode edit, tampilkan detail yang sudah ada --}}
                    @if(isset($pembelian) && ($details = old('details', $pembelian->detailPembelian ?? [])))
                         @foreach($details as $index => $detail)
                            @php
                                // Jika $detail adalah model, ambil propertinya. Jika array (dari old()), akses sebagai array.
                                $detail_id = $detail->id ?? ($detail['detail_id'] ?? null); // Untuk input hidden di edit
                                $id_produk = $detail->id_produk ?? ($detail['id_produk'] ?? null);
                                $produk_nama = $detail->produk->nama ?? (\App\Models\Produk::find($id_produk)->nama ?? 'Produk tidak ditemukan');
                                $jumlah = $detail->jumlah ?? ($detail['jumlah'] ?? 1);
                                $harga_beli = $detail->harga_beli ?? ($detail['harga_beli'] ?? 0);
                            @endphp
                            <tr class="detail-item-row">
                                {{-- Input hidden untuk ID detail saat edit --}}
                                @if($detail_id)
                                <input type="hidden" name="details[{{ $index }}][detail_id]" value="{{ $detail_id }}">
                                @endif
                                <td>
                                    <select class="form-select product-select @error('details.'.$index.'.id_produk') is-invalid @enderror" name="details[{{ $index }}][id_produk]" required data-placeholder="Cari Produk...">
                                        {{-- Opsi ini akan diisi oleh Select2, tapi kita sediakan opsi terpilih awal --}}
                                        @if($id_produk)
                                            <option value="{{ $id_produk }}" selected>{{ $produk_nama }}</option>
                                        @endif
                                    </select>
                                    {{-- Error handling bisa ditambahkan di sini jika perlu --}}
                                </td>
                                <td>
                                    <input type="number" class="form-control item-jumlah text-end @error('details.'.$index.'.jumlah') is-invalid @enderror" name="details[{{ $index }}][jumlah]" value="{{ $jumlah }}" required min="1" step="1">
                                </td>
                                <td>
                                    <input type="number" class="form-control item-harga text-end @error('details.'.$index.'.harga_beli') is-invalid @enderror" name="details[{{ $index }}][harga_beli]" value="{{ $harga_beli }}" required min="0" step="0.01">
                                </td>
                                <td>
                                    <span class="item-subtotal fw-bold">Rp 0</span> {{-- Akan dihitung JS --}}
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm delete-item-btn" title="Hapus Item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                     {{-- Baris baru akan ditambahkan di sini oleh JS --}}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold border-0">Grand Total</td>
                        <td colspan="2" class="fw-bold border-0"><span id="grand-total">Rp 0</span></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button type="button" class="btn btn-success btn-sm mt-2" id="add-item-btn">
            <i class="bi bi-plus-circle"></i> Tambah Item
        </button>
    </div>
</div>

{{-- ====================================================== --}}
{{-- INFORMASI TAMBAHAN                                   --}}
{{-- ====================================================== --}}
<div class="card shadow-sm mb-4">
     <div class="card-header bg-light">
        <h5 class="mb-0">Informasi Tambahan</h5>
    </div>
    <div class="card-body">
         {{-- Catatan --}}
        <div class="mb-3">
            <label for="catatan" class="form-label">Catatan</label>
            <textarea class="form-control @error('catatan') is-invalid @enderror" id="catatan" name="catatan" rows="3">{{ old('catatan', $pembelian->catatan ?? '') }}</textarea>
            @error('catatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

{{-- Template untuk baris detail item (hidden) - Sama seperti di create --}}
<template id="detail-item-template">
    <tr class="detail-item-row">
        <td>
            <select class="form-select product-select" name="details[__INDEX__][id_produk]" required data-placeholder="Cari Produk...">
                <option value=""></option>
            </select>
        </td>
        <td>
            <input type="number" class="form-control item-jumlah text-end" name="details[__INDEX__][jumlah]" value="1" required min="1" step="1">
        </td>
        <td>
            <input type="number" class="form-control item-harga text-end" name="details[__INDEX__][harga_beli]" value="0" required min="0" step="0.01">
        </td>
        <td>
            <span class="item-subtotal fw-bold">Rp 0</span>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm delete-item-btn" title="Hapus Item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>