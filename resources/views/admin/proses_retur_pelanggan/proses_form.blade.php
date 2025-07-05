@extends('layouts.app')

@php
    // Siapkan variabel untuk kemudahan akses
    $produk = $detailReturPenjualan->detailPenjualanAsal->produk;
    $returHeader = $detailReturPenjualan->returPenjualan;
    $penjualanAsal = $returHeader->penjualanAsal;
    $stokBarangAsal = $detailReturPenjualan->alokasiAsal->stokBarang ?? null;
@endphp

@section('title', 'Proses Item Retur: ' . $produk->nama)

@push('styles')
    <style>
        .info-box { background-color: #f8f9fa; border: 1px solid #dee2e6; border-left: 5px solid #0dcaf0; }
        .info-label { font-weight: 600; color: #495057; }
        .required-label::after { content: " *"; color: red; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Proses Tindakan untuk Item Retur</h1>

    {{-- Form sekarang menunjuk ke ID detail yang benar --}}
    <form action="{{ route('admin.proses_retur_pelanggan.store.tindakan', $detailReturPenjualan->id) }}" method="POST" id="form-tindakan-admin">
        @csrf

        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                {{-- Judul spesifik untuk satu item --}}
                <h5 class="mb-0">Item: {{ $produk->nama }} @if($detailReturPenjualan->nomor_seri_diretur) (SN: {{ $detailReturPenjualan->nomor_seri_diretur }}) @endif</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger"><ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul></div>
                @endif
                
                {{-- Bagian Info Detail --}}
                <div class="info-box p-3 mb-4 rounded">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><span class="info-label d-block">No. Nota Retur:</span> {{ $returHeader->nomor_retur }}</p>
                            <p class="mb-2"><span class="info-label d-block">No. Nota Asal:</span> <a href="{{ route('kasir.penjualan.nota', $penjualanAsal->id) }}" target="_blank">{{ $penjualanAsal->nomor_penjualan }}</a></p>
                            <p class="mb-0"><span class="info-label d-block">Pelanggan:</span> {{ $penjualanAsal->pelanggan->nama ?? 'Umum' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><span class="info-label d-block">Supplier Asal:</span> <strong>{{ $stokBarangAsal->supplier->nama ?? 'N/A' }}</strong></p>
                            <p class="mb-2"><span class="info-label d-block">Alasan Awal (dari Kasir):</span> {{ ucwords(strtolower(str_replace('_', ' ', $detailReturPenjualan->alasan_retur))) }}</p>
                            <p class="mb-0"><span class="info-label d-block">Jumlah Diretur:</span> {{ $detailReturPenjualan->jumlah_retur }} unit</p>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4">Keputusan Admin</h5>
                <hr>

                {{-- Input untuk satu item, tidak lagi di dalam loop --}}
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="tindakan_admin" class="form-label required-label">Keputusan Tindak Lanjut:</label>
                        <select class="form-select" id="tindakan_admin" name="tindakan_admin" required>
                            <option value="">Pilih Tindakan...</option>
                            @foreach($tindakanAdminOptions as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 area-dinamis" id="area-lokasi" style="display: none;">
                        <label for="lokasi_tujuan_retur" class="form-label required-label">Lokasi Penyimpanan:</label>
                        <select class="form-select" id="lokasi_tujuan_retur" name="lokasi_tujuan_retur">
                            <option value="GUDANG">Gudang</option><option value="TOKO">Toko</option>
                        </select>
                    </div>
                    <div class="col-md-4 area-dinamis" id="area-harga" style="display: none;">
                        <label for="harga_beli_baru" class="form-label required-label">Harga Beli Baru:</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="harga_beli_baru" name="harga_beli_baru" placeholder="Harga Pokok" min="0" value="{{ $stokBarangAsal->harga_beli ?? 0 }}">
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label for="catatan_admin_proses" class="form-label">Catatan Proses (Opsional):</label>
                    <textarea class="form-control" id="catatan_admin_proses" name="catatan_admin_proses" rows="2"></textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.proses_retur_pelanggan.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i> Simpan Tindakan</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#tindakan_admin').on('change', function() {
        const tindakan = $(this).val();
        
        const areaLokasi = $('#area-lokasi');
        const inputLokasi = $('#lokasi_tujuan_retur');
        
        const areaHarga = $('#area-harga');
        const inputHarga = $('#harga_beli_baru');

        // Sembunyikan semua dan reset 'required'
        $('.area-dinamis').hide();
        inputLokasi.prop('required', false);
        inputHarga.prop('required', false);
        
        if (tindakan === 'KEMBALI_KE_STOK_BAIK_ADMIN') {
            areaLokasi.slideDown();
            inputLokasi.prop('required', true);
            areaHarga.slideDown();
            inputHarga.prop('required', true);
        } else if (tindakan === 'AKAN_DIRETUR_KE_SUPPLIER') {
            areaLokasi.slideDown();
            inputLokasi.prop('required', true);
        } else if (tindakan === 'BARANG_SELESAI_SERVIS') {
            areaLokasi.slideDown();
            inputLokasi.prop('required', true);
        }
    }).trigger('change'); // Trigger saat halaman dimuat untuk set kondisi awal
});
</script>
@endpush