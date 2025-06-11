{{-- admin/retur_pembelian/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Retur Pembelian Baru')

@push('styles')
    <style>
        .select2-container--bootstrap-5 .select2-selection { min-height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; font-size: 1rem; line-height: 1.5; border-radius: .25rem; }
        .required-label::after { content: " *"; color: red; }
        .item-retur-pembelian-card { margin-bottom: 1.5rem; border-left: 5px solid #fd7e14; } 
        .serial-checkbox-list .form-check { margin-bottom: 0.25rem; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Form Retur Pembelian ke Supplier</h1>

    <form action="{{ route('admin.retur_pembelian.store') }}" method="POST" id="form-retur-pembelian">
        @csrf
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Informasi Umum Retur Pembelian</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="nomor_retur_display" class="form-label">Nomor Retur Pembelian:</label>
                        <input type="text" class="form-control" id="nomor_retur_display" value="(Otomatis)" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="tanggal_retur" class="form-label required-label">Tanggal Retur:</label>
                        <input type="datetime-local" class="form-control @error('tanggal_retur') is-invalid @enderror"
                               id="tanggal_retur" name="tanggal_retur"
                               value="{{ old('tanggal_retur', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('tanggal_retur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="id_supplier_retur" class="form-label">Supplier Tujuan Retur:</label>
                        <input type="text" class="form-control" id="id_supplier_retur_display" readonly placeholder="Akan terisi otomatis dari batch pertama">
                        {{-- ID Supplier akan diambil dari batch pertama yang dipilih --}}
                        <input type="hidden" name="id_supplier_tujuan" id="id_supplier_tujuan">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Item Batch yang Akan Diretur</h5>
                <button type="button" class="btn btn-success btn-sm" id="btn-tambah-item-retur-pembelian">
                    <i class="bi bi-plus-circle-fill me-1"></i> Tambah Item/Batch
                </button>
            </div>
            <div class="card-body">
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

                <div id="item-retur-pembelian-container">
                    {{-- Item retur pembelian akan ditambahkan di sini oleh JavaScript --}}
                </div>

                <div class="mt-3">
                    <label for="catatan_global_retur_pembelian" class="form-label">Catatan Global Retur ke Supplier (Opsional):</label>
                    <textarea class="form-control" id="catatan_global_retur_pembelian" name="catatan_global_retur_pembelian" rows="2">{{ old('catatan_global_retur_pembelian') }}</textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.retur_pembelian.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger" id="btn-simpan-retur-pembelian">
                    <i class="bi bi-send-arrow-up-fill me-1"></i> Proses Retur ke Supplier
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Template untuk Baris Item Retur Pembelian (Hidden) --}}
<template id="item-retur-pembelian-template">
    <div class="card item-retur-pembelian-card shadow-sm" data-item-index="__INDEX__">
        <div class="card-body">
            <input type="hidden" name="items_retur[__INDEX__][id_stok_barang]" class="item-id-stok-barang">
            <input type="hidden" name="items_retur[__INDEX__][id_produk_retur]" class="item-id-produk-retur">
             <button type="button" class="btn-close float-end delete-item-retur-pembelian-btn" aria-label="Hapus Item"></button>
            <div class="row g-3">
                <div class="col-md-12 mb-2">
                    <label for="items_retur___INDEX___batch_stok_cari" class="form-label required-label">Pilih Batch Stok:</label>
                    <select class="form-select select2-batch-stok-retur" name="items_retur[__INDEX__][batch_stok_select]" required data-placeholder="Cari Batch ID, Produk, atau Supplier...">
                        <option></option> {{-- Option kosong untuk placeholder --}}
                    </select>
                    <div class="mt-1 selected-batch-info-display">
                        <small class="d-block">Produk: <span class="nama-produk-info">-</span></small>
                        <small class="d-block">Supplier: <span class="nama-supplier-info">-</span> | Sisa Stok Batch: <span class="sisa-stok-batch-info">-</span> unit</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="items_retur___INDEX___jumlah_retur" class="form-label required-label">Jumlah Diretur:</label>
                    <input type="number" class="form-control form-control-sm item-jumlah-retur-pembelian"
                           name="items_retur[__INDEX__][jumlah_retur]" value="0" min="0" max="0" required
                           data-memiliki-serial="false" data-index="__INDEX__">
                </div>

                <div class="col-md-4">
                    <label for="items_retur___INDEX___alasan_retur" class="form-label required-label">Alasan Retur:</label>
                    <select class="form-select form-select-sm item-alasan-retur-pembelian" name="items_retur[__INDEX__][alasan_retur]" required>
                        <option value="">Pilih Alasan...</option>
                        @foreach ($alasanReturOptions as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5">
                    <label for="items_retur___INDEX___tindakan_lanjut_supplier" class="form-label required-label">Tindak Lanjut Diharapkan dari Supplier:</label>
                    <select class="form-select form-select-sm item-tindakan-lanjut-supplier" name="items_retur[__INDEX__][tindakan_lanjut_supplier]" required>
                        <option value="">Pilih Tindakan...</option>
                        @foreach ($tindakanLanjutSupplierOptions as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3 serial-selection-area-pembelian" id="serial-selection-area-pembelian___INDEX__" style="display:none;">
                <label class="form-label">Pilih Nomor Seri yang Diretur (<span class="selected-serial-count-pembelian">0</span> / <span class="max-serial-select-pembelian">0</span>):</label>
                <div class="serial-checkbox-list-pembelian row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2">
                </div>
            </div>
            <div class="mt-2">
                <label for="items_retur___INDEX___catatan_ke_supplier_item" class="form-label">Catatan untuk Supplier (Item ini):</label>
                <textarea class="form-control form-control-sm" name="items_retur[__INDEX__][catatan_ke_supplier_item]" rows="1"></textarea>
            </div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
    <script>
    $(document).ready(function() {
        let itemReturPembelianCounter = 0;

        function initializeBatchSearchSelect(element) {
            $(element).select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: $(element).data('placeholder'),
                allowClear: true,
                ajax: {
                    url: "{{ route('admin.retur_pembelian.ajax.search_batch_stok') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term, page: params.page || 1 }; },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.items, // Data sudah diformat di controller
                            pagination: { more: (params.page * 15) < data.total_count }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
            }).on('select2:select', function(e) {
                const data = e.params.data;
                const itemCard = $(this).closest('.item-retur-pembelian-card');
                itemCard.find('.item-id-stok-barang').val(data.id);
                itemCard.find('.item-id-produk-retur').val(data.id_produk);
                itemCard.find('.nama-produk-info').text(data.nama_produk);
                itemCard.find('.nama-supplier-info').text(data.nama_supplier);
                itemCard.find('.sisa-stok-batch-info').text(data.sisa_stok_batch);

                const jumlahInput = itemCard.find('.item-jumlah-retur-pembelian');
                jumlahInput.attr('max', data.sisa_stok_batch).val(0).data('memiliki-serial', data.memiliki_serial);
                toggleSerialAreaPembelian(jumlahInput);

                // Set supplier tujuan jika ini item pertama atau suppliernya sama
                if (itemReturPembelianCounter === 1 || $('#id_supplier_tujuan').val() === '' || $('#id_supplier_tujuan').val() == data.id_supplier) {
                    $('#id_supplier_retur_display').val(data.nama_supplier);
                    $('#id_supplier_tujuan').val(data.id_supplier);
                    // Disable pemilihan batch lain jika supplier berbeda? (Opsional)
                    // $('.select2-batch-stok-retur').each(function() {
                    //     if ($(this).val() && $(this).select2('data')[0].id_supplier != data.id_supplier) {
                    //         // Mungkin beri warning atau disable
                    //     }
                    // });
                } else if (data.id_supplier && $('#id_supplier_tujuan').val() != data.id_supplier) {
                    Swal.fire('Perhatian!', 'Retur pembelian idealnya ditujukan ke satu supplier per transaksi retur. Batch ini dari supplier yang berbeda.', 'warning');
                    // Anda bisa memilih untuk mengosongkan pilihan ini atau membiarkannya
                    // $(this).val(null).trigger('change'); // Contoh mengosongkan
                }


            }).on('select2:unselect', function(e){
                const itemCard = $(this).closest('.item-retur-pembelian-card');
                itemCard.find('.item-id-stok-barang, .item-id-produk-retur, #id_supplier_tujuan').val('');
                itemCard.find('.nama-produk-info, .nama-supplier-info, .sisa-stok-batch-info').text('-');
                $('#id_supplier_retur_display').val('Akan terisi otomatis');
                const jumlahInput = itemCard.find('.item-jumlah-retur-pembelian');
                jumlahInput.attr('max', 0).val(0).data('memiliki-serial', false);
                toggleSerialAreaPembelian(jumlahInput);
            });
        }

        function toggleSerialAreaPembelian(jumlahInput) {
            const itemCard = $(jumlahInput).closest('.item-retur-pembelian-card');
            const memilikiSerial = $(jumlahInput).data('memiliki-serial') === true || $(jumlahInput).data('memiliki-serial') === 'true';
            const jumlahRetur = parseInt($(jumlahInput).val()) || 0;
            const serialArea = itemCard.find('.serial-selection-area-pembelian');
            const maxSerialSelectSpan = serialArea.find('.max-serial-select-pembelian');
            const serialCheckboxContainer = serialArea.find('.serial-checkbox-list-pembelian');

            serialCheckboxContainer.empty(); // Selalu bersihkan dulu
            if (memilikiSerial && jumlahRetur > 0) {
                serialArea.slideDown();
                maxSerialSelectSpan.text(jumlahRetur);
                updateSelectedSerialCountPembelian(serialArea); // Update count jadi 0

                const idStokBarang = itemCard.find('.item-id-stok-barang').val();
                if (idStokBarang) {
                    serialCheckboxContainer.html('<small class="text-muted">Memuat nomor seri...</small>');
                    $.ajax({
                        url: "{{ route('admin.retur_pembelian.ajax.get_serials_from_batch') }}",
                        type: "GET",
                        data: { id_stok_barang: idStokBarang },
                        success: function(response) {
                            serialCheckboxContainer.empty();
                            if (response.success && response.serials && response.serials.length > 0) {
                                response.serials.forEach(function(serial, i) {
                                    const uniqueId = `retur_beli_serial_${itemCard.data('item-index')}_${i}`;
                                    serialCheckboxContainer.append(`
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input nomor-seri-retur-pembelian-checkbox" type="checkbox"
                                                       name="items_retur[${itemCard.data('item-index')}][nomor_seri_diretur][]"
                                                       value="${serial}" id="${uniqueId}">
                                                <label class="form-check-label" for="${uniqueId}">${serial}</label>
                                            </div>
                                        </div>
                                    `);
                                });
                            } else {
                                serialCheckboxContainer.html('<small class="text-danger">Tidak ada nomor seri tersedia untuk batch ini atau semua sudah diretur.</small>');
                            }
                        },
                        error: function() {
                            serialCheckboxContainer.html('<small class="text-danger">Gagal memuat nomor seri.</small>');
                        }
                    });
                }

            } else {
                serialArea.slideUp();
            }
        }

        function updateSelectedSerialCountPembelian(serialArea) {
            const count = serialArea.find('.nomor-seri-retur-pembelian-checkbox:checked').length;
            serialArea.find('.selected-serial-count-pembelian').text(count);
        }

        $('#item-retur-pembelian-container').on('input change', '.item-jumlah-retur-pembelian', function() {
            const val = parseInt($(this).val()) || 0;
            const max = parseInt($(this).attr('max'));
            if (val > max) { $(this).val(max); }
            if (val < 0) { $(this).val(0); }
            toggleSerialAreaPembelian(this);
        });

        $('#item-retur-pembelian-container').on('change', '.nomor-seri-retur-pembelian-checkbox', function() {
            const serialArea = $(this).closest('.serial-selection-area-pembelian');
            const jumlahRetur = parseInt(serialArea.closest('.item-retur-pembelian-card').find('.item-jumlah-retur-pembelian').val()) || 0;
            const checkedCount = serialArea.find('.nomor-seri-retur-pembelian-checkbox:checked').length;

            if (checkedCount > jumlahRetur) {
                $(this).prop('checked', false);
                Swal.fire('Perhatian', 'Jumlah nomor seri yang dipilih tidak boleh melebihi jumlah item yang diretur.', 'warning');
            }
            updateSelectedSerialCountPembelian(serialArea);
        });

        $('#btn-tambah-item-retur-pembelian').on('click', function() {
            itemReturPembelianCounter++;
            let template = $('#item-retur-pembelian-template').html();
            let newRowHtml = template.replace(/__INDEX__/g, itemReturPembelianCounter);
            $('#item-retur-pembelian-container').append(newRowHtml);
            let newRowCard = $(`.item-retur-pembelian-card[data-item-index="${itemReturPembelianCounter}"]`);
            initializeBatchSearchSelect(newRowCard.find('.select2-batch-stok-retur'));
            newRowCard.find('.item-jumlah-retur-pembelian').data('index', itemReturPembelianCounter); // Update data-index
        });

        $('#item-retur-pembelian-container').on('click', '.delete-item-retur-pembelian-btn', function() {
            $(this).closest('.item-retur-pembelian-card').remove();
            // Jika ini adalah item pertama dan satu-satunya, reset supplier
            if ($('.item-retur-pembelian-card').length === 0) {
                $('#id_supplier_retur_display').val('Akan terisi otomatis dari batch pertama');
                $('#id_supplier_tujuan').val('');
                itemReturPembelianCounter = 0; // Reset counter jika semua dihapus
            } else if ($(this).closest('.item-retur-pembelian-card').data('item-index') === 1 && $('#id_supplier_tujuan').val() === $(this).closest('.item-retur-pembelian-card').find('.select2-batch-stok-retur').select2('data')[0]?.id_supplier) {
                // Jika item pertama dihapus, dan supplier global diset dari item ini, reset supplier global
                 $('#id_supplier_retur_display').val('Akan terisi otomatis dari batch pertama');
                 $('#id_supplier_tujuan').val('');
            }
        });

        // Tambah item pertama saat halaman dimuat
        if ($('.item-retur-pembelian-card').length === 0) {
            $('#btn-tambah-item-retur-pembelian').click();
        }

        // Validasi Form Submit Utama
        $('#form-retur-pembelian').on('submit', function(e) {
            let formIsValid = true;
            let errorMessages = [];
            let firstInvalidElement = null;

            if (!$('#tanggal_retur').val()) {
                errorMessages.push('Tanggal retur wajib diisi.');
                formIsValid = false; if(!firstInvalidElement) firstInvalidElement = $('#tanggal_retur');
            }
            if ($('.item-retur-pembelian-card').length === 0) {
                errorMessages.push('Minimal harus ada satu item/batch yang diretur.');
                formIsValid = false; if(!firstInvalidElement) firstInvalidElement = $('#btn-tambah-item-retur-pembelian');
            }

            let totalItemDenganQtyRetur = 0;

            $('.item-retur-pembelian-card').each(function(index) {
                const itemCard = $(this);
                const itemIndexDisplay = index + 1; // Untuk pesan error
                const batchSelect = itemCard.find('.select2-batch-stok-retur');
                const jumlahInput = itemCard.find('.item-jumlah-retur-pembelian');
                const alasanSelect = itemCard.find('.item-alasan-retur-pembelian');
                const tindakanSelect = itemCard.find('.item-tindakan-lanjut-supplier');
                const qtyDiretur = parseInt(jumlahInput.val()) || 0;

                if (!batchSelect.val()) {
                    errorMessages.push(`Batch stok wajib dipilih untuk item retur ke-${itemIndexDisplay}.`);
                    formIsValid = false; if(!firstInvalidElement) firstInvalidElement = batchSelect;
                }
                if (qtyDiretur <= 0) {
                    // Tidak dianggap error jika 0, tapi tidak akan diproses.
                    // Namun, jika ini satu-satunya item, form utama akan error.
                } else {
                    totalItemDenganQtyRetur++;
                    if (!alasanSelect.val()) {
                        errorMessages.push(`Alasan retur wajib dipilih untuk item ke-${itemIndexDisplay}.`);
                        formIsValid = false; if(!firstInvalidElement) firstInvalidElement = alasanSelect;
                    }
                    if (!tindakanSelect.val()) {
                        errorMessages.push(`Tindak lanjut diharapkan wajib dipilih untuk item ke-${itemIndexDisplay}.`);
                        formIsValid = false; if(!firstInvalidElement) firstInvalidElement = tindakanSelect;
                    }

                    const memilikiSerial = jumlahInput.data('memiliki-serial') === true || jumlahInput.data('memiliki-serial') === 'true';
                    if (memilikiSerial) {
                        const serialArea = itemCard.find('.serial-selection-area-pembelian');
                        const checkedSerials = serialArea.find('.nomor-seri-retur-pembelian-checkbox:checked').length;
                        if (checkedSerials !== qtyDiretur) {
                            errorMessages.push(`Jumlah No. Seri dipilih (${checkedSerials}) tidak sesuai dengan Jumlah Diretur (${qtyDiretur}) untuk item ke-${itemIndexDisplay}.`);
                            formIsValid = false; if(!firstInvalidElement) firstInvalidElement = serialArea.find('.nomor-seri-retur-pembelian-checkbox:first');
                        }
                    }
                }
            });

            if (totalItemDenganQtyRetur === 0 && $('.item-retur-pembelian-card').length > 0) {
                errorMessages.push('Minimal ada satu item dengan jumlah retur lebih dari 0.');
                formIsValid = false; if(!firstInvalidElement) firstInvalidElement = $('.item-jumlah-retur-pembelian:first');
            }


            if (!formIsValid) {
                e.preventDefault();
                let errorHtml = '<ul>';
                errorMessages.forEach(function(msg) { errorHtml += `<li>${msg}</li>`; });
                errorHtml += '</ul>';
                Swal.fire({
                    icon: 'error', title: 'Validasi Gagal!', html: errorHtml,
                    confirmButtonText: 'OK Mengerti'
                }).then(() => {
                    if (firstInvalidElement && $(firstInvalidElement).is(':visible')) {
                        $('html, body').animate({ scrollTop: $(firstInvalidElement).offset().top - 150 }, 500, () => $(firstInvalidElement).focus());
                    }
                });
            } else {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Proses Retur Pembelian',
                    text: "Apakah Anda yakin ingin memproses retur ke supplier ini? Stok akan dikurangi.",
                    icon: 'warning', showCancelButton: true,
                    confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Proses Retur!', cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#btn-simpan-retur-pembelian').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                        $(this).off('submit').submit();
                    }
                });
            }
        });
    });
    </script>
@endpush