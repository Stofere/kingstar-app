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
                   <div class="mb-3">
                        <label for="tindakan_admin" class="form-label required-label">Keputusan Tindak Lanjut oleh Admin</label>
                        <select class="form-select" id="tindakan_admin" name="tindakan_admin" required>
                            <option value="">Pilih Tindakan...</option>
                            @foreach($tindakanAdminOptions as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- AREA BARU: Pilihan Lokasi (disembunyikan secara default) --}}
                    <div class="mb-3" id="area-pilihan-lokasi" style="display: none;">
                        <label for="lokasi_tujuan_retur" class="form-label required-label">Pilih Lokasi Penyimpanan Barang</label>
                        <select class="form-select" id="lokasi_tujuan_retur" name="lokasi_tujuan_retur">
                            {{-- Opsi akan diisi oleh JS --}}
                        </select>
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
        const tindakan = $(this).val();
        const areaLokasi = $('#area-pilihan-lokasi');
        const selectLokasi = $('#lokasi_tujuan_retur');
        
        // Opsi lokasi yang akan digunakan
        const lokasiRusak = [
            { value: 'GUDANG', text: 'Gudang (Area Retur/Rusak)' },
            { value: 'TOKO', text: 'Toko (Area Retur/Rusak)' }
        ];
        const lokasiBaik = [
            { value: 'GUDANG', text: 'Gudang' },
            { value: 'TOKO', text: 'Toko' }
        ];

        selectLokasi.empty().prop('required', false); // Selalu reset saat berubah

        if (tindakan === 'KEMBALI_KE_STOK_BAIK_ADMIN') {
            $.each(lokasiBaik, function(i, loc) {
                selectLokasi.append($('<option>', { value: loc.value, text: loc.text }));
            });
            areaLokasi.slideDown();
            selectLokasi.prop('required', true);
        } else if (tindakan === 'CATAT_SEBAGAI_STOK_RUSAK_FINAL') {
            $.each(lokasiRusak, function(i, loc) {
                selectLokasi.append($('<option>', { value: loc.value, text: loc.text }));
            });
            areaLokasi.slideDown();
            selectLokasi.prop('required', true);
        } 
        // --- INI PERBAIKANNYA: TAMBAHKAN KONDISI UNTUK RETUR SUPPLIER ---
        else if (tindakan === 'AKAN_DIRETUR_KE_SUPPLIER') {
            // Untuk retur ke supplier, kita juga menggunakan lokasi "Area Retur/Rusak"
            $.each(lokasiRusak, function(i, loc) {
                selectLokasi.append($('<option>', { value: loc.value, text: loc.text }));
            });
            areaLokasi.slideDown();
            selectLokasi.prop('required', true);
        }
        // --- AKHIR PERBAIKAN ---
        else {
            areaLokasi.slideUp();
        }
    }).trigger('change'); // Trigger saat halaman dimuat untuk menangani old input
});
</script>
@endpush