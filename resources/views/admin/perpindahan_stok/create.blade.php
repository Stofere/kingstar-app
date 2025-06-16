@extends('layouts.app')
@section('title', 'Buat Perpindahan Stok Baru')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header"><h5 class="mb-0">Formulir Perpindahan Stok</h5></div>
                <div class="card-body">
                    <form action="{{ route('perpindahan-stok.store') }}" method="POST" id="pindah-stok-form">
                        @csrf                        
                        <div class="mb-3">
                            <label for="id_stok_barang_asal" class="form-label">Pilih Batch Asal <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_stok_barang_asal" name="id_stok_barang_asal" required data-placeholder="Cari Batch ID atau Nama Produk..."></select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Lokasi Asal</label>
                                <input type="text" id="dari_lokasi_display" class="form-control" readonly value="-">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sisa Stok di Batch</label>
                                <input type="text" id="sisa_stok_display" class="form-control" readonly value="-">
                            </div>
                        </div>

                        <div class="row mb-3">
                             <div class="col-md-6">
                                <label for="jumlah_pindah" class="form-label">Jumlah Pindah <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="jumlah_pindah" name="jumlah_pindah" required min="1">
                            </div>
                            <div class="col-md-6">
                                <label for="ke_lokasi" class="form-label">Pindah ke Lokasi <span class="text-danger">*</span></label>
                                <select class="form-select" id="ke_lokasi" name="ke_lokasi" required>
                                    <option value="">Pilih Tujuan...</option>
                                    @foreach($lokasiOptions as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3"></textarea>
                        </div>

                        {{-- AREA BARU UNTUK NOMOR SERI (disembunyikan) --}}
                        <div class="mb-3" id="serial-selection-area" style="display: none;">
                            <label class="form-label fw-bold text-primary">Pilih Nomor Seri yang Dipindah <span class="text-danger">*</span></label>
                            <div id="serial-checkbox-container" class="p-2 border rounded" style="max-height: 200px; overflow-y: auto;">
                                {{-- Checkbox diisi oleh AJAX --}}
                            </div>
                            <small class="form-text text-muted">Jumlah serial yang dipilih harus sesuai dengan 'Jumlah Pindah'.</small>
                        </div>
                        
                        <div class="text-end">
                            <a href="{{ route('perpindahan-stok.index') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Perpindahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let selectedBatchHasSerial = false;
    $('#id_stok_barang_asal').select2({
        theme: "bootstrap-5",
        placeholder: $(this).data('placeholder'),
        ajax: {
            url: "{{ route('perpindahan-stok.ajax.search-batch') }}",
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        }
    }).on('select2:select', function (e) {
        var data = e.params.data;        var data = e.params.data;
        $('#dari_lokasi_display').val(data.lokasi_asal);
        $('#sisa_stok_display').val(data.sisa);
        $('#jumlah_pindah').attr('max', data.sisa).val(1); // Set max dan default qty 1
        $('#jumlah_pindah').attr('max', data.sisa).val(1).trigger('change'); // Trigger change

        selectedBatchHasSerial = data.has_serial;
        if (data.has_serial) {
            $('#serial-selection-area').show();
            loadSerials(data.id);
        } else {
            $('#serial-selection-area').hide();
        }
    });

    function loadSerials(batchId) {
        const container = $('#serial-checkbox-container');
        container.html('<div class="text-center">Memuat serial...</div>');
        $.ajax({
            url: "{{ route('perpindahan-stok.ajax.get-serials') }}", // <-- ROUTE BARU
            data: { id_stok_barang: batchId },
            success: function(response) {
                container.empty();
                if (response.success && response.serials.length > 0) {
                    let checkboxHtml = '<div class="row row-cols-2">';
                    response.serials.forEach(serial => {
                        checkboxHtml += `<div class="col"><div class="form-check"><input class="form-check-input" type="checkbox" name="nomor_seri_dipindah[]" value="${serial}" id="sn-${serial}"><label class="form-check-label" for="sn-${serial}">${serial}</label></div></div>`;
                    });
                    checkboxHtml += '</div>';
                    container.html(checkboxHtml);
                } else {
                    container.html('<p class="text-muted">Tidak ada nomor seri tersedia untuk batch ini.</p>');
                }
            }
        });
    }

    // Validasi saat jumlah pindah diubah
    $('#jumlah_pindah').on('input', function() {
        if (selectedBatchHasSerial) {
            let jumlahPindah = $(this).val();
            // Anda bisa tambahkan logika untuk membatasi jumlah check di sini jika perlu
        }
    });

    // Validasi akhir sebelum submit
    $('#pindah-stok-form').on('submit', function(e) {
        if (selectedBatchHasSerial) {
            let jumlahPindah = parseInt($('#jumlah_pindah').val());
            let serialsChecked = $('input[name="nomor_seri_dipindah[]"]:checked').length;
            if (jumlahPindah !== serialsChecked) {
                e.preventDefault();
                alert(`Validasi Gagal! Anda harus memilih tepat ${jumlahPindah} nomor seri.`);
            }
        }
    });
});
</script>
@endpush