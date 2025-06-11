@extends('layouts.app')

@section('title', 'Proses Tindakan Retur Pelanggan: ' . $returPenjualan->nomor_retur)

@push('styles')
    {{-- Tambahkan style jika perlu --}}
    <style>
        .detail-retur-info { background-color: #f8f9fa; padding: 1rem; border-radius: .25rem; margin-bottom: 1.5rem; }
        .required-label::after { content: " *"; color: red; }
        .form-section-retur-admin { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #dee2e6;}
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Proses Tindakan untuk Retur Pelanggan <span class="text-primary">#{{ $returPenjualan->nomor_retur }}</span></h1>

    <form action="{{ route('admin.proses_retur_pelanggan.store.tindakan', $returPenjualan->id) }}" method="POST" id="form-tindakan-admin-retur">
        @csrf
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Detail Item Retur</h5>
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

                <div class="detail-retur-info">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>No. Nota Asal:</strong> {{ $returPenjualan->detailPenjualan->penjualan->nomor_penjualan ?? '-' }}</p>
                            <p><strong>Pelanggan:</strong> {{ $returPenjualan->detailPenjualan->penjualan->pelanggan->nama ?? 'Umum' }}</p>
                            <p><strong>Produk:</strong> {{ $returPenjualan->detailPenjualan->produk->nama ?? '-' }} ({{ $returPenjualan->detailPenjualan->produk->kode_produk ?? '-' }})</p>
                            <p><strong>Jumlah Diretur:</strong> {{ $returPenjualan->jumlah_retur }} unit</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tanggal Retur oleh Kasir:</strong> {{ Carbon\Carbon::parse($returPenjualan->tanggal_retur)->isoFormat('D MMM YYYY, HH:mm') }}</p>
                            <p><strong>Kasir Proses Awal:</strong> {{ $returPenjualan->pengguna->nama ?? '-' }}</p>
                            <p><strong>Alasan Retur Awal:</strong> {{ $returPenjualan->alasan_retur ? \App\Helpers\ReturHelper::formatAlasanRetur($returPenjualan->alasan_retur) : '-' }}</p>
                            <p><strong>Tindakan Awal Kasir:</strong> {{ $returPenjualan->tindakan_lanjut ? \App\Helpers\ReturHelper::formatTindakanLanjut($returPenjualan->tindakan_lanjut) : '-' }}</p>
                        </div>
                    </div>
                    @if($returPenjualan->nomor_seri_diretur)
                    <p><strong>Nomor Seri Diretur:</strong> <span class="fw-bold">{{ str_replace(',', ', ', $returPenjualan->nomor_seri_diretur) }}</span></p>
                    @endif
                    @if($returPenjualan->catatan_pelanggan)
                    <p><strong>Catatan dari Pelanggan:</strong> {{ $returPenjualan->catatan_pelanggan }}</p>
                    @endif
                </div>

                <div class="form-section-retur-admin">
                    <h5 class="mb-3">Keputusan Tindak Lanjut oleh Admin</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tindakan_admin" class="form-label required-label">Pilih Tindakan Akhir:</label>
                            <select class="form-select @error('tindakan_admin') is-invalid @enderror" id="tindakan_admin" name="tindakan_admin" required>
                                <option value="">Pilih Keputusan...</option>
                                @foreach ($tindakanAdminOptions as $key => $value)
                                    <option value="{{ $key }}" {{ old('tindakan_admin') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('tindakan_admin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div id="detail-kembali-ke-stok-baik" class="row g-3 mt-2" style="display:none;">
                         <div class="col-md-4">
                            <label for="harga_beli_batch_retur" class="form-label">Harga Beli Batch Retur (Opsional):</label>
                            <input type="number" class="form-control @error('harga_beli_batch_retur') is-invalid @enderror"
                                   id="harga_beli_batch_retur" name="harga_beli_batch_retur"
                                   value="{{ old('harga_beli_batch_retur', $returPenjualan->detailPenjualan->produk->harga_beli_terakhir_valid ?? 0) }}" min="0" step="any">
                            @error('harga_beli_batch_retur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="form-text text-muted">Default dari harga beli terakhir produk. Isi jika berbeda.</small>
                        </div>
                        <div class="col-md-4">
                            <label for="lokasi_stok_retur" class="form-label">Lokasi Penyimpanan Baru:</label>
                            <select class="form-select @error('lokasi_stok_retur') is-invalid @enderror" id="lokasi_stok_retur" name="lokasi_stok_retur">
                                @foreach ($lokasiPenyimpanan as $key => $value)
                                    <option value="{{ $key }}" {{ old('lokasi_stok_retur', 'GUDANG_RETUR_BAIK') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('lokasi_stok_retur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="tipe_garansi_retur" class="form-label">Tipe Garansi Batch Retur:</label>
                            <select class="form-select @error('tipe_garansi_retur') is-invalid @enderror" id="tipe_garansi_retur" name="tipe_garansi_retur">
                                @foreach ($tipeGaransiOptions as $key => $value)
                                    <option value="{{ $key }}" {{ old('tipe_garansi_retur', $returPenjualan->detailPenjualan->produk->tipe_garansi_default_saat_beli ?? 'NONE') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('tipe_garansi_retur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="catatan_admin_proses" class="form-label">Catatan Proses Admin (Opsional):</label>
                        <textarea class="form-control @error('catatan_admin_proses') is-invalid @enderror" id="catatan_admin_proses" name="catatan_admin_proses" rows="2">{{ old('catatan_admin_proses') }}</textarea>
                        @error('catatan_admin_proses') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.proses_retur_pelanggan.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-success" id="btn-simpan-tindakan-admin">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Tindakan
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
        $('#tindakan_admin').on('change', function() {
            if ($(this).val() === 'KEMBALI_KE_STOK_BAIK_ADMIN') {
                $('#detail-kembali-ke-stok-baik').slideDown();
                $('#harga_beli_batch_retur').prop('required', true);
                $('#lokasi_stok_retur').prop('required', true);
                $('#tipe_garansi_retur').prop('required', true);
            } else {
                $('#detail-kembali-ke-stok-baik').slideUp();
                $('#harga_beli_batch_retur').prop('required', false);
                $('#lokasi_stok_retur').prop('required', false);
                $('#tipe_garansi_retur').prop('required', false);
            }
        }).trigger('change'); // Trigger saat halaman dimuat untuk set kondisi awal

        $('#form-tindakan-admin-retur').on('submit', function(e) {
            e.preventDefault(); // Cegah submit default
            Swal.fire({
                title: 'Konfirmasi Tindakan',
                text: "Apakah Anda yakin dengan tindakan yang dipilih untuk retur ini?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Simpan Tindakan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#btn-simpan-tindakan-admin').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                    $(this).off('submit').submit(); // Hapus handler, lalu submit form asli
                }
            });
        });
    });
    </script>
@endpush