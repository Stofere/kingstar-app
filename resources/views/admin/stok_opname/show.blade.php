@extends('layouts.app')
@section('title', 'Proses Stok Opname #' . $stokOpname->id)

@push('styles')
<style>
    .serial-adjustment-row { display: none; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Proses Stok Opname #{{ $stokOpname->id }}</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Tanggal Opname:</strong> {{ \Carbon\Carbon::parse($stokOpname->tanggal_opname)->isoFormat('D MMMM YYYY') }}</p>
                    <p class="mb-1"><strong>Lokasi:</strong> {{ $stokOpname->lokasi ?? 'Semua Lokasi' }}</p>
                    <p class="mb-1"><strong>Dimulai Oleh:</strong> {{ $stokOpname->penggunaMulai->nama ?? '-' }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Status:</strong> <span class="badge bg-{{ $stokOpname->status === 'BERJALAN' ? 'info' : 'success' }}">{{ $stokOpname->status }}</span></p>
                    @if($stokOpname->penggunaSelesai)
                    <p class="mb-1"><strong>Diselesaikan Oleh:</strong> {{ $stokOpname->penggunaSelesai->nama }} pada {{ \Carbon\Carbon::parse($stokOpname->finished_at)->isoFormat('D MMM YYYY, HH:mm') }}</p>
                    @endif
                    @if($stokOpname->catatan)
                    <p class="mb-0"><strong>Catatan Sesi:</strong> {{ $stokOpname->catatan }}</p>
                    @endif
                </div>
            </div>
            @php $isAdjusted = Str::contains($stokOpname->catatan, '[SISTEM] Penyesuaian stok dibuat'); @endphp
            @if($isAdjusted)
                <div class="alert alert-success mt-2 mb-0"><i class="bi bi-check-circle-fill"></i> Penyesuaian stok untuk sesi ini sudah dibuat.</div>
            @endif
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header"><h5 class="mb-0">Daftar Item untuk Dihitung</h5></div>
        {{-- SATU FORM UNTUK SEMUA --}}
        <form id="opname-form" action="{{ route('gudang.stok-opname.finish_and_adjust', $stokOpname->id) }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Produk</th><th>Batch ID</th><th>Lokasi</th>
                                <th class="text-center">Jml Sistem</th><th class="text-center" style="width: 15%;">Jml Fisik</th>
                                <th class="text-center">Selisih</th><th style="width: 25%;">Catatan Selisih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($detailOpname as $detail)
                            <tr id="row-{{ $detail->id }}" class="opname-item-row">
                                <td>{{ $detail->stokBarang->produk->nama }} @if($detail->stokBarang->produk->memiliki_serial)<small class="text-muted d-block">(Berserial)</small>@endif</td>
                                <td>{{ $detail->id_stok_barang }}</td>
                                <td>{{ $detail->stokBarang->lokasi }}</td>
                                <td class="text-center">{{ $detail->jumlah_sistem }}</td>
                                <td>
                                    <input type="number" class="form-control form-control-sm text-center physical-count-input"
                                           name="details[{{ $detail->id }}][jumlah_fisik]"
                                           data-detail-id="{{ $detail->id }}" data-jumlah-sistem="{{ $detail->jumlah_sistem }}"
                                           data-has-serial="{{ $detail->stokBarang->produk->memiliki_serial ? 'true' : 'false' }}"
                                           value="{{ $detail->jumlah_fisik }}" min="0" 
                                           {{ $stokOpname->status !== 'BERJALAN' ? 'readonly' : '' }}>
                                </td>
                                <td class="text-center selisih-display">-</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                           name="details[{{ $detail->id }}][catatan]"
                                           value="{{ $detail->catatan }}"
                                           {{ $stokOpname->status !== 'BERJALAN' ? 'readonly' : '' }}>
                                </td>
                            </tr>
                            <tr class="serial-adjustment-row" id="serial-row-{{ $detail->id }}">
                                <td colspan="7" class="bg-light p-3">
                                    <div class="serial-content-area"></div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Tidak ada item stok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                @if($stokOpname->status === 'BERJALAN')
                    <button type="submit" class="btn btn-success" onclick="return confirm('Anda yakin ingin menyelesaikan dan membuat penyesuaian untuk sesi opname ini?');">
                        <i class="bi bi-check-all"></i> Selesaikan & Buat Penyesuaian
                    </button>
                @else
                    <div class="alert alert-success d-inline-block p-2 me-2">Sesi ini sudah Selesai.</div>
                @endif
                <a href="{{ route('gudang.stok-opname.index') }}" class="btn btn-secondary">Tutup</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const availableSerials = @json($availableSerials ?? []);
    const opnameForm = $('#opname-form'); // Variabel opnameForm dideklarasikan
    const lokasiOptions = @json($lokasiOptions ?? []);

    function handleInputChange(inputElement) {
        let input = $(inputElement);
        let detailId = input.data('detail-id');
        let serialRow = $(`#serial-row-${detailId}`);
        let serialContentArea = serialRow.find('.serial-content-area');
        let jumlahFisik = input.val() === '' ? null : parseInt(input.val());
        let jumlahSistem = parseInt(input.data('jumlah-sistem'));
        let hasSerial = input.data('has-serial') === true;
        let selisih = (jumlahFisik === null) ? null : jumlahFisik - jumlahSistem;

        if (selisih !== null) {
            let badgeClass = selisih === 0 ? 'bg-success' : (selisih > 0 ? 'bg-primary' : 'bg-danger');
            input.closest('tr').find('.selisih-display').html(`<span class="badge ${badgeClass}">${selisih > 0 ? '+' : ''}${selisih}</span>`);
        } else {
            input.closest('tr').find('.selisih-display').html('-');
        }

        serialContentArea.empty();
        if (selisih !== 0 && '{{ $stokOpname->status }}' !== 'SELESAI') {
            serialRow.show();
            if (selisih < 0) {
                let absSelisih = Math.abs(selisih);
                let itemSerials = availableSerials[detailId] || [];
                let serialHtml = `<p class="mb-1 fw-bold text-danger">Pilih ${absSelisih} Nomor Seri yang Hilang/Kurang:</p>`;
                if (itemSerials.length > 0) {
                    serialHtml += `<div class="row g-2">`; // Nama input disesuaikan
                    itemSerials.forEach(serial => {
                        serialHtml += `<div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="details[${detailId}][serials_kurang][]" value="${serial}"><label class="small">${serial}</label></div></div>`;
                    });
                    serialHtml += `</div>`;
                }
                serialContentArea.html(serialHtml);
            } else if (selisih > 0) {
                let serialHtml = `<p class="mb-1 fw-bold text-primary">Detail untuk ${selisih} Barang Temuan:</p><div class="row g-3">`;
                serialHtml += `<div class="col-md-4"><label class="form-label form-label-sm">Harga Beli Baru</label><input type="number" class="form-control form-control-sm" name="details[${detailId}][harga_beli_lebih]" value="0" min="0"></div>`; // Nama input disesuaikan
                let lokasiSelectHtml = `<div class="col-md-4"><label class="form-label form-label-sm">Lokasi Baru</label><select class="form-select form-select-sm" name="details[${detailId}][lokasi_lebih]">`; // Nama input disesuaikan
                lokasiOptions.forEach(lok => { lokasiSelectHtml += `<option value="${lok}">${lok}</option>`; });
                lokasiSelectHtml += `</select></div>`;
                serialHtml += lokasiSelectHtml + `</div>`;
                if (hasSerial) {
                    serialHtml += `<p class="mb-1 mt-3 fw-bold text-primary">Input ${selisih} Nomor Seri Baru:</p>`;
                    for(let i = 0; i < selisih; i++) {
                        serialHtml += `<input type="text" class="form-control form-control-sm mb-1" name="details[${detailId}][serials_lebih][]" placeholder="Nomor Seri Baru ${i + 1}" required>`; // Nama input disesuaikan
                    }
                }
                serialContentArea.html(serialHtml);
            }
        } else {
            serialRow.hide();
        }
    }

    $('.physical-count-input').each(function() { handleInputChange(this); });
    $('.physical-count-input').on('change keyup', function() { handleInputChange(this); });

    // Event handler untuk #btn-selesaikan dan #btn-penyesuaian dihapus
    // karena tombol-tombol tersebut tidak ada di HTML dan fungsionalitasnya
    // tampaknya ditangani oleh tombol submit utama form.
});
</script>
@endpush