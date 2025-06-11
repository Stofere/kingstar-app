@extends('layouts.app') 

@section('title', 'Update Tindak Lanjut Retur Pembelian No: ' . $returPembelian->nomor_retur)

@push('styles')
    <style>
        .detail-retur-info { background-color: #f8f9fa; padding: 1rem; border-radius: .25rem; margin-bottom: 1.5rem; }
        .detail-label { font-weight: bold; color: #555; display: inline-block; min-width: 180px;}
        .value-text { }
        .form-section-update { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #dee2e6;}
        .required-label::after { content: " *"; color: red; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0 h3">Update Tindak Lanjut Retur Pembelian</h1>
        <a href="{{ route('admin.retur_pembelian.show', $returPembelian->id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye me-1"></i> Lihat Detail Retur
        </a>
    </div>

    <form action="{{ route('admin.retur_pembelian.update', $returPembelian->id) }}" method="POST" id="form-update-retur-pembelian">
        @csrf
        @method('PUT') {{-- Method untuk update --}}

        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Retur Pembelian No: {{ $returPembelian->nomor_retur }}</h5>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger pb-0">
                        <p class="fw-bold">Terdapat kesalahan pada input Anda:</p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Informasi Retur Awal (Read Only) --}}
                <div class="detail-retur-info">
                    <h6 class="mb-3">Informasi Retur Awal</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><span class="detail-label">Tanggal Retur Awal:</span> <span class="value-text">{{ Carbon\Carbon::parse($returPembelian->tanggal_retur)->isoFormat('D MMM YYYY, HH:mm') }}</span></p>
                            <p><span class="detail-label">Admin Proses Awal:</span> <span class="value-text">{{ $returPembelian->pengguna->nama ?? '-' }}</span></p>
                            <p><span class="detail-label">Produk Diretur:</span> <span class="value-text">{{ $returPembelian->stokBarang->produk->nama ?? '-' }} (Batch ID: {{ $returPembelian->id_stok_barang }})</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><span class="detail-label">Supplier Asal Batch:</span> <span class="value-text">{{ $returPembelian->stokBarang->supplier->nama ?? '-' }}</span></p>
                            <p><span class="detail-label">Jumlah Diretur:</span> <span class="value-text">{{ $returPembelian->jumlah_retur }} unit</span></p>
                            @if($returPembelian->nomor_seri_diretur)
                            <p><span class="detail-label">Nomor Seri Diretur:</span> <span class="value-text">{{ str_replace(',', ', ', $returPembelian->nomor_seri_diretur) }}</span></p>
                            @endif
                            <p><span class="detail-label">Alasan Retur Awal:</span> <span class="value-text">{{ $returPembelian->alasan_retur ? \App\Helpers\ReturHelper::formatAlasanReturPembelian($returPembelian->alasan_retur) : '-' }}</span></p>
                        </div>
                    </div>
                </div>

                {{-- Form Update Tindak Lanjut --}}
                <div class="form-section-update">
                    <h5 class="mb-3">Update Tindak Lanjut dari Supplier</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tindakan_lanjut_supplier" class="form-label required-label">Status Tindak Lanjut Supplier:</label>
                            <select class="form-select @error('tindakan_lanjut_supplier') is-invalid @enderror" id="tindakan_lanjut_supplier" name="tindakan_lanjut_supplier" required>
                                <option value="">Pilih Status...</option>
                                @foreach ($tindakanLanjutSupplierFinalOptions as $key => $value)
                                    <option value="{{ $key }}" {{ old('tindakan_lanjut_supplier', $returPembelian->tindakan_lanjut_supplier) == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tindakan_lanjut_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="catatan_ke_supplier" class="form-label">Catatan Update untuk Supplier (Opsional):</label>
                            <textarea class="form-control @error('catatan_ke_supplier') is-invalid @enderror" id="catatan_ke_supplier" name="catatan_ke_supplier" rows="2">{{ old('catatan_ke_supplier', $returPembelian->catatan_ke_supplier) }}</textarea>
                            @error('catatan_ke_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="catatan_internal_retur" class="form-label">Catatan Update Internal (Opsional):</label>
                            <textarea class="form-control @error('catatan_internal_retur') is-invalid @enderror" id="catatan_internal_retur" name="catatan_internal_retur" rows="2">{{ old('catatan_internal_retur', $returPembelian->catatan_internal_retur) }}</textarea>
                            @error('catatan_internal_retur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.retur_pembelian.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-success" id="btn-update-retur-pembelian">
                    <i class="bi bi-save2 me-1"></i> Update Tindak Lanjut
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        // Konfirmasi sebelum submit update
        $('#form-update-retur-pembelian').on('submit', function(e) {
            e.preventDefault(); // Cegah submit default
            Swal.fire({
                title: 'Konfirmasi Update',
                text: "Apakah Anda yakin ingin memperbarui tindak lanjut retur pembelian ini?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754', // Warna hijau untuk update
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Update!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#btn-update-retur-pembelian').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memperbarui...');
                    $(this).off('submit').submit(); // Hapus handler, lalu submit form asli
                }
            });
        });
    });
    </script>
@endpush