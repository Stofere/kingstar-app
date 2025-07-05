{{-- Ganti SELURUH isi file resources/views/admin/retur_pembelian/edit.blade.php --}}

@extends('layouts.app')

@section('title', 'Update Tindak Lanjut Retur: ' . $returPembelian->nomor_retur)

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Update Tindak Lanjut dari Supplier</h1>

    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Nota Retur No: {{ $returPembelian->nomor_retur }}</h5>
        </div>
        <div class="card-body">
            {{-- Tampilkan info ringkas --}}
            <p><strong>Supplier Tujuan:</strong> {{ $returPembelian->supplier->nama ?? 'N/A' }}</p>
            <p><strong>Item yang diretur:</strong></p>
            <ul>
                @foreach($returPembelian->detailReturPembelian as $detail)
                    <li>
                        {{ $detail->stokBarang->produk->nama ?? 'N/A' }} ({{ $detail->jumlah_retur }} unit)
                        @if($detail->nomor_seri_diretur)
                            <small class="text-muted">- SN: {{ $detail->nomor_seri_diretur }}</small>
                        @endif
                    </li>
                @endforeach
            </ul>
            <hr>

            <form action="{{ route('admin.retur_pembelian.update', $returPembelian->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="tindakan_lanjut_supplier" class="form-label required-label">Update Status Final dari Supplier:</label>
                    <select class="form-select" id="tindakan_lanjut_supplier" name="tindakan_lanjut_supplier" required>
                        <option value="">Pilih status final...</option>
                        @foreach($tindakanLanjutSupplierFinalOptions as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="catatan_internal_retur" class="form-label">Catatan Update (Opsional):</label>
                    <textarea class="form-control" name="catatan_internal_retur" rows="2" placeholder="Contoh: Konfirmasi via telepon dengan Bpk. Budi dari supplier."></textarea>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>Perhatian:</strong> Setelah status final disimpan, retur ini tidak dapat diedit lagi. Jika Anda memilih "Barang Sudah Diganti", Anda harus membuat penerimaan barang baru secara manual saat barang fisik tiba.
                </div>
        </div>
        <div class="card-footer text-end">
            <a href="{{ route('admin.retur_pembelian.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Status Final</button>
            </form>
        </div>
    </div>
</div>
@endsection