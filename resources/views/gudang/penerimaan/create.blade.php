@extends('layouts.app')

@php
    // Logika judul dinamis
    $title = 'Buat Penerimaan Manual (Stok Lama)';
    if ($tipe_penerimaan === 'PO') {
        $title = 'Proses Penerimaan dari PO: ' . $selectedPembelian->nomor_pembelian;
    } elseif ($tipe_penerimaan === 'RETUR') {
        $title = 'Proses Penerimaan Barang Pengganti dari Retur: ' . $selectedPembelian->nomor_pembelian;
    }
@endphp

@section('title', $title)

@push('styles')
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            border-radius: .25rem;
        }
        .required-label::after { content: " *"; color: red; }
        .item-penerimaan-card { margin-bottom: 1.5rem; }
        .serial-input-container input[type="text"] { margin-bottom: 0.5rem; }
        .table-detail-penerimaan th, .table-detail-penerimaan td { vertical-align: middle; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">@yield('title')</h1>

    {{-- Notifikasi Error & Info --}}
    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi Kesalahan Validasi!</h5>
        <ul class="mb-0 ps-4">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($tipe_penerimaan === 'RETUR' && isset($selectedPembelian))
    <div class="alert alert-success mb-4">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>INFO:</strong> Ini adalah penerimaan untuk barang pengganti. Pastikan barang dan jumlahnya sesuai.
        <br>
        <small>Catatan Asal PO: {{ $selectedPembelian->catatan }}</small>
    </div>
    @endif

    <form action="{{ route('gudang.penerimaan.store') }}" method="POST" id="form-penerimaan">
        @csrf
        <input type="hidden" name="tipe_penerimaan" value="{{ $tipe_penerimaan }}">
        @if(isset($selectedPembelian))
            <input type="hidden" name="id_pembelian" value="{{ $selectedPembelian->id }}">
        @endif

         {{-- Informasi Header Penerimaan --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Informasi Umum Penerimaan</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipe Penerimaan</label>
                        <input type="text" class="form-control" value="{{ strtoupper(str_replace('_', ' ', $tipe_penerimaan)) }}" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
                        @if($tipe_penerimaan === 'PO' || $tipe_penerimaan === 'RETUR')
                            <input type="text" class="form-control" value="{{ $selectedPembelian->supplier->nama ?? 'N/A' }}" readonly>
                        @else {{-- Mode MANUAL --}}
                            <select class="form-select select2-supplier" name="id_supplier_manual" data-placeholder="Pilih Supplier (Opsional)">
                                <option value=""></option>
                                @foreach ($suppliers as $id => $nama)
                                    <option value="{{ $id }}" {{ old('id_supplier_manual') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label for="diterima_at" class="form-label required-label">Tanggal Penerimaan Fisik</label>
                        <input type="datetime-local" class="form-control @error('diterima_at') is-invalid @enderror" id="diterima_at" name="diterima_at" value="{{ old('diterima_at', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('diterima_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Item Penerimaan --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detail Item Diterima</h5>
                @if($tipe_penerimaan === 'MANUAL')
                <button type="button" class="btn btn-success btn-sm" id="add-manual-item-btn">
                    <i class="bi bi-plus-circle"></i> Tambah Item
                </button>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-detail-penerimaan" id="detail-penerimaan-table">
                        <thead class="table-light align-middle">
                            <tr>
                                @if($tipe_penerimaan === 'MANUAL') <th class="text-center" style="width: 5%;">Aksi</th> @endif
                                <th style="min-width: 200px;">Produk</th>
                                <th class="text-center" style="width: 10%;">Dipesan</th>
                                <th class="text-center" style="width: 10%;">Sudah Diterima</th>
                                <th class="text-center required-label" style="width: 12%;">Diterima Sekarang</th>
                                <th style="width: 12%;">Lokasi</th>
                                <th style="width: 15%;">Kondisi</th>
                                <th style="width: 15%;">Tipe Garansi</th>
                                <th style="min-width: 250px;">Nomor Seri (jika ada)</th>
                            </tr>
                        </thead>
                        <tbody id="detail-penerimaan-body">
                            {{-- Render item dari PO atau PO Retur --}}
                            @if(!empty($detailItems))
                                @foreach($detailItems as $index => $item)
                                    <tr class="detail-item-row" data-index="{{ $index }}" data-product-id="{{ $item['id_produk'] }}" data-has-serial="{{ $item['memiliki_serial'] ? 'true' : 'false' }}">
                                        <input type="hidden" name="items[{{ $index }}][id_detail_pembelian]" value="{{ $item['id_detail_pembelian'] }}">
                                        <input type="hidden" name="items[{{ $index }}][id_produk]" value="{{ $item['id_produk'] }}">
                                        <td>
                                            {{ $item['nama_produk'] }}
                                            @if($item['memiliki_serial']) <span class="badge bg-info ms-1">SERIAL</span> @endif
                                        </td>
                                        <td class="text-center">{{ $item['jumlah_pesan'] }}</td>
                                        <td class="text-center">{{ $item['jumlah_sudah_diterima'] }}</td>
                                        <td>
                                            <input type="number" class="form-control item-jumlah-diterima text-end @error('items.'.$index.'.jumlah_diterima_sekarang') is-invalid @enderror"
                                                   name="items[{{ $index }}][jumlah_diterima_sekarang]"
                                                   value="{{ old('items.'.$index.'.jumlah_diterima_sekarang', 0) }}"
                                                   required min="0" max="{{ $item['jumlah_belum_diterima'] }}"
                                                   data-item-index="{{ $index }}"
                                                   data-has-serial="{{ $item['memiliki_serial'] ? 'true' : 'false' }}">
                                        </td>
                                        <td>
                                            <select class="form-select item-lokasi" name="items[{{ $index }}][lokasi]" required>
                                                @foreach($lokasiPenyimpanan as $val => $label)
                                                    <option value="{{ $val }}" {{ 'GUDANG' == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select item-kondisi" name="items[{{ $index }}][kondisi]" required>
                                                @foreach($kondisiBarang as $val => $label)
                                                    <option value="{{ $val }}" {{ 'BAIK' == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                             <select class="form-select item-tipe-garansi" name="items[{{ $index }}][tipe_garansi]" required>
                                                @foreach($tipeGaransi as $val => $label)
                                                    <option value="{{ $val }}" {{ 'NONE' == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="serial-input-area-container" style="display: none;">
                                                <div class="serial-input-container"></div>
                                                <small class="text-muted serial-count-feedback">Jumlah No. Seri: 0</small>
                                                <div class="invalid-feedback d-block serial-error-feedback"></div>
                                            </div>
                                            @if(!$item['memiliki_serial']) <span class="text-muted">-</span> @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @elseif($tipe_penerimaan === 'MANUAL' && empty(old('items')))
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Klik tombol "Tambah Item" untuk memulai.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3 text-end">
            <a href="{{ route('gudang.penerimaan.index') }}" class="btn btn-secondary me-2">Batal</a>
            <button type="submit" class="btn btn-primary" id="submit-penerimaan-btn">Simpan Penerimaan</button>
        </div>
    </form>
</div>

{{-- Template untuk item manual --}}
<template id="manual-item-template">
     <tr class="detail-item-row manual-item" data-index="__INDEX__" data-product-id="" data-has-serial="false">
        <td class="text-center align-middle">
            <button type="button" class="btn btn-danger btn-sm delete-manual-item-btn" title="Hapus Item"><i class="bi bi-trash"></i></button>
        </td>
        <td>
            <select class="form-select product-select-manual" name="items[__INDEX__][id_produk]" required data-placeholder="Cari Produk...">
                <option value=""></option> {{-- Option kosong untuk placeholder Select2 --}}
            </select>
            <input type="hidden" class="has-serial-flag" value="false">
            <div class="invalid-feedback d-block product-error-feedback"></div>
        </td>
        <td class="text-center">-</td>
        <td class="text-center">-</td>
        <td>
            <input type="number" class="form-control item-jumlah-diterima text-end" name="items[__INDEX__][jumlah_diterima_sekarang]" value="1" required min="1" step="1" data-item-index="__INDEX__" data-has-serial="false">
        </td>
        <td>
            <select class="form-select item-lokasi" name="items[__INDEX__][lokasi]" required>
                 @foreach($lokasiPenyimpanan as $val => $label)
                    <option value="{{ $val }}" {{ 'GUDANG' == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="form-select item-kondisi" name="items[__INDEX__][kondisi]" required>
                @foreach($kondisiBarang as $val => $label)
                    <option value="{{ $val }}" {{ 'BAIK' == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td>
             <select class="form-select item-tipe-garansi" name="items[__INDEX__][tipe_garansi]" required>
                @foreach($tipeGaransi as $val => $label)
                    <option value="{{ $val }}" {{ 'NONE' == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <div class="serial-input-area-container" id="serial-input-area-container-__INDEX__" style="display: none;">
                <div class="serial-input-container">
                    {{-- Input serial akan ditambahkan oleh JS --}}
                </div>
                <small class="text-muted serial-count-feedback" id="serial-count-feedback-__INDEX__">Jumlah No. Seri: 0</small>
                <div class="invalid-feedback d-block serial-error-feedback" id="serial-error-feedback-__INDEX__"></div>
            </div>
            <span class="text-muted no-serial-placeholder">-</span>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
    <script>
    $(document).ready(function() {
        // Inisialisasi Select2 untuk supplier jika mode manual
        if ($('input[name="tipe_penerimaan"]').val() === 'MANUAL') {
            $('#id_supplier_manual').select2({
                theme: "bootstrap-5",
                placeholder: $(this).data('placeholder'),
                allowClear: true
            });
        }

        let manualItemNextIndex = {{ old('items') ? count(old('items')) : ($detailItems ? count($detailItems) : 0) }};

        function initializeManualProductSelect(element) {
            $(element).select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: $(element).data('placeholder'),
                allowClear: true,
                ajax: {
                    // REVISI: Gunakan route baru yang bisa diakses Gudang
                    url: "{{ route('gudang.ajax.produk.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term, page: params.page || 1 }; },
                    processResults: function(data, params) {
                        return {
                            results: data.results.map(function(item) {
                                return { id: item.id, text: item.text, has_serial: item.has_serial };
                            })
                        };
                    },
                },
                minimumInputLength: 1,
            }).on('select2:select', function (e) {
                var data = e.params.data;
                var row = $(this).closest('.detail-item-row');
                row.data('product-id', data.id);
                let hasSerial = data.has_serial;
                row.data('has-serial', hasSerial);
                row.find('.has-serial-flag').val(hasSerial ? 'true' : 'false');
                row.find('.item-jumlah-diterima').data('has-serial', hasSerial ? 'true' : 'false'); // Update juga di input jumlah

                toggleSerialInputArea(row, hasSerial);
                updateSerialInputs(row.find('.item-jumlah-diterima')); // Trigger update serial inputs
                row.find('.product-error-feedback').text('');
            }).on('select2:unselect', function (e) {
                var row = $(this).closest('.detail-item-row');
                row.data('product-id', '');
                row.data('has-serial', false);
                row.find('.has-serial-flag').val('false');
                row.find('.item-jumlah-diterima').data('has-serial', 'false');

                toggleSerialInputArea(row, false);
                updateSerialInputs(row.find('.item-jumlah-diterima'));
            });
        }

        function toggleSerialInputArea(row, show) {
            const serialArea = row.find('.serial-input-area-container');
            const noSerialPlaceholder = row.find('.no-serial-placeholder');
            if (show) {
                serialArea.show();
                noSerialPlaceholder.hide();
            } else {
                serialArea.hide();
                noSerialPlaceholder.show();
                serialArea.find('.serial-input-container').empty(); // Bersihkan input serial
                updateSerialCountFeedback(row, 0);
            }
        }

        // Fungsi untuk meng-update input nomor seri berdasarkan jumlah yang diinput
        function updateSerialInputs(jumlahInput) {
            const row = $(jumlahInput).closest('.detail-item-row');
            const itemIndex = $(jumlahInput).data('item-index');
            const hasSerial = $(jumlahInput).data('has-serial') === true;
            const qtyDiterima = parseInt($(jumlahInput).val()) || 0;
            const serialAreaContainer = row.find('.serial-input-area-container');
            const serialContainer = serialAreaContainer.find('.serial-input-container');

            // Selalu bersihkan input lama
            serialContainer.empty();
            
            // Tampilkan/sembunyikan area input serial
            if (hasSerial && qtyDiterima > 0) {
                serialAreaContainer.show();
                for (let i = 0; i < qtyDiterima; i++) {
                    serialContainer.append(
                        `<input type="text" name="items[${itemIndex}][nomor_seri][]" class="form-control mb-1 nomor-seri-input" placeholder="Nomor Seri ${i + 1}" required>`
                    );
                }
            } else {
                serialAreaContainer.hide();
            }
            // Update feedback jumlah serial
            updateSerialCountFeedback(row, serialContainer.find('.nomor-seri-input').length);
        }

        function updateSerialCountFeedback(row, count) {
            row.find('.serial-count-feedback').text('Jumlah No. Seri: ' + count);
        }

        // Event listener utama untuk input jumlah
        $('#detail-penerimaan-body').on('input change', '.item-jumlah-diterima', function() {
            updateSerialInputs(this);
        });

        // Inisialisasi untuk item PO yang sudah ada
        $('#detail-penerimaan-body .detail-item-row:not(.manual-item)').each(function() {
            const qtyInput = $(this).find('.item-jumlah-diterima');
            // Hanya panggil updateSerialInputs jika ada qty input (untuk item PO)
            if(qtyInput.length > 0) {
                updateSerialInputs(qtyInput);
            }
        });
         // Inisialisasi untuk item manual dari old input
        $('#detail-penerimaan-body .manual-item').each(function() {
            const itemIndex = $(this).data('index');
            initializeManualProductSelect($(this).find('.product-select-manual'), itemIndex);
            const qtyInput = $(this).find('.item-jumlah-diterima');
             if(qtyInput.length > 0) {
                updateSerialInputs(qtyInput); // Ini akan generate ulang serial berdasarkan old value
            }
        });


        $('#add-manual-item-btn').on('click', function() {
            let template = $('#manual-item-template').html();
            let newRowHtml = template.replace(/__INDEX__/g, manualItemNextIndex);
            $('#detail-penerimaan-body').append(newRowHtml);
            let newRow = $('#detail-penerimaan-body tr.manual-item:last');
            initializeManualProductSelect(newRow.find('.product-select-manual'), manualItemNextIndex);
            newRow.find('.item-jumlah-diterima').data('item-index', manualItemNextIndex); // Set data-item-index
            manualItemNextIndex++;
        });

        $('#detail-penerimaan-body').on('click', '.delete-manual-item-btn', function() {
            $(this).closest('.manual-item').remove();
        });

        function validateSerialNumbers(row) {
            const qtyDiterima = parseInt(row.find('.item-jumlah-diterima').val()) || 0;
            const hasSerial = row.data('has-serial') === true || row.find('.has-serial-flag').val() === 'true';
            const serialInputs = row.find('.nomor-seri-input');
            const serialErrorFeedback = row.find('.serial-error-feedback');
            let isValid = true;
            let errorMessage = "";

            serialErrorFeedback.text(''); // Clear previous error

            if (hasSerial && qtyDiterima > 0) {
                if (serialInputs.length !== qtyDiterima) {
                    errorMessage = `Jumlah No. Seri (${serialInputs.length}) harus sama dengan Jumlah Diterima (${qtyDiterima}).`;
                    isValid = false;
                } else {
                    const serialValues = [];
                    let hasEmptySerial = false;
                    serialInputs.each(function() {
                        const val = $(this).val().trim();
                        if (val === '') {
                            hasEmptySerial = true;
                        }
                        if (serialValues.includes(val) && val !== '') {
                            errorMessage = `Nomor Seri "${val}" duplikat pada item ini.`;
                            isValid = false;
                            return false; // break loop
                        }
                        if (val !== '') {
                            serialValues.push(val);
                        }
                    });
                    if (hasEmptySerial && serialValues.length < qtyDiterima) {
                         errorMessage = `Semua input Nomor Seri wajib diisi.`;
                         isValid = false;
                    }
                }
            } else if (hasSerial && qtyDiterima === 0 && serialInputs.length > 0) {
                errorMessage = "Tidak boleh ada Nomor Seri jika Jumlah Diterima adalah 0.";
                isValid = false;
            }

            if (!isValid) {
                serialErrorFeedback.text(errorMessage);
            }
            return isValid;
        }


        $('#form-penerimaan').on('submit', function(e) {
            let overallFormIsValid = true;
            let alertMessages = [];
            let firstInvalidElement = null;

            // Validasi header (jika perlu, misal tanggal)
            const tanggalTerima = $('#diterima_at').val();
            if (!tanggalTerima) {
                alertMessages.push('Tanggal Penerimaan Fisik wajib diisi.');
                overallFormIsValid = false;
                if (!firstInvalidElement) firstInvalidElement = $('#diterima_at');
            }

            // Validasi supplier manual jika tipe manual dan field diisi tapi tidak valid (Select2 biasanya handle ini)
            if ($('input[name="tipe_penerimaan"]').val() === 'MANUAL') {
                const supplierManual = $('#id_supplier_manual').val();
                // Jika Anda mewajibkan supplier manual, tambahkan validasi di sini
                // if (!supplierManual) {
                //     alertMessages.push('Supplier wajib dipilih untuk penerimaan manual.');
                //     overallFormIsValid = false;
                //     if (!firstInvalidElement) firstInvalidElement = $('#id_supplier_manual');
                // }
            }


            let totalItemsToReceive = 0;
            $('#detail-penerimaan-body .detail-item-row').each(function(idx) {
                let row = $(this);
                let itemNumber = idx + 1;
                let qtyInput = row.find('.item-jumlah-diterima');
                let qtyDiterima = parseInt(qtyInput.val()) || 0;
                let maxQty = parseInt(qtyInput.data('max-qty')); // Untuk item PO

                totalItemsToReceive += qtyDiterima;

                // Validasi produk dipilih untuk item manual
                if (row.hasClass('manual-item')) {
                    const produkSelect = row.find('.product-select-manual');
                    if (!produkSelect.val()) {
                        alertMessages.push(`Produk wajib dipilih untuk item manual ke-${itemNumber}.`);
                        overallFormIsValid = false;
                        row.find('.product-error-feedback').text('Produk wajib dipilih.');
                        if (!firstInvalidElement) firstInvalidElement = produkSelect;
                    } else {
                         row.find('.product-error-feedback').text('');
                    }
                }


                if (qtyDiterima < 0) {
                    alertMessages.push(`Jumlah diterima tidak boleh negatif pada item ke-${itemNumber}.`);
                    overallFormIsValid = false;
                    qtyInput.addClass('is-invalid');
                    if (!firstInvalidElement) firstInvalidElement = qtyInput;
                } else {
                    qtyInput.removeClass('is-invalid');
                }

                if ($('input[name="tipe_penerimaan"]').val() === 'PO' && !isNaN(maxQty) && qtyDiterima > maxQty) {
                    alertMessages.push(`Jumlah diterima pada item ke-${itemNumber} (${qtyDiterima}) melebihi sisa yang belum diterima (${maxQty}).`);
                    overallFormIsValid = false;
                    qtyInput.addClass('is-invalid');
                    if (!firstInvalidElement) firstInvalidElement = qtyInput;
                }

                if (!validateSerialNumbers(row)) {
                    // Pesan error sudah ditampilkan oleh validateSerialNumbers
                    overallFormIsValid = false;
                    if (!firstInvalidElement) firstInvalidElement = row.find('.nomor-seri-input:first, .item-jumlah-diterima');
                }
            });

            if (totalItemsToReceive === 0 && $('#detail-penerimaan-body .detail-item-row').length > 0) {
                 alertMessages.push('Minimal ada satu item yang diterima dengan jumlah lebih dari 0.');
                 overallFormIsValid = false;
            } else if ($('#detail-penerimaan-body .detail-item-row').length === 0 && $('input[name="tipe_penerimaan"]').val() === 'MANUAL') {
                alertMessages.push('Minimal tambahkan satu item untuk penerimaan manual.');
                overallFormIsValid = false;
            }


            if (!overallFormIsValid) {
                e.preventDefault();
                let messageText = 'Terdapat kesalahan pada input Anda:<br><ul class="text-start ps-4">';
                alertMessages.forEach(function(msg) { messageText += '<li>' + msg + '</li>'; });
                messageText += '</ul>';

                Swal.fire({
                    title: 'Validasi Gagal!',
                    html: messageText,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        htmlContainer: 'text-start'
                    }
                }).then(() => {
                     if (firstInvalidElement && $(firstInvalidElement).is(':visible')) {
                         $('html, body').animate({
                             scrollTop: $(firstInvalidElement).offset().top - 150
                         }, 500, function() {
                            $(firstInvalidElement).focus();
                         });
                     }
                });
            } else {
                $('#submit-penerimaan-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
            }
        });
    });
    </script>
@endpush