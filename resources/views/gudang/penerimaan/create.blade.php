@extends('layouts.app')

@section('title', $tipe_penerimaan === 'PO' && $selectedPembelian ? 'Proses Penerimaan dari PO: ' . $selectedPembelian->nomor_pembelian : 'Buat Penerimaan Barang Manual')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
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

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi Kesalahan Validasi!</h5>
        <ul class="mb-0">
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


    <form action="{{ route('gudang.penerimaan.store') }}" method="POST" id="form-penerimaan">
        @csrf
        <input type="hidden" name="tipe_penerimaan" value="{{ $tipe_penerimaan }}">
        @if($tipe_penerimaan === 'PO' && $selectedPembelian)
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
                        <label for="nomor_po_display" class="form-label">Nomor PO</label>
                        <input type="text" class="form-control" id="nomor_po_display" value="{{ $tipe_penerimaan === 'PO' && $selectedPembelian ? $selectedPembelian->nomor_pembelian : 'MANUAL' }}" readonly>
                    </div>

                    <div class="col-md-4">
                        <label for="id_supplier_display" class="form-label @if($tipe_penerimaan === 'MANUAL') /* required-label (opsional) */ @endif">Supplier</label>
                        @if($tipe_penerimaan === 'PO' && $selectedPembelian)
                            <input type="text" class="form-control" id="id_supplier_display" value="{{ $selectedPembelian->supplier->nama ?? 'N/A' }}" readonly>
                        @else {{-- Mode MANUAL --}}
                            <select class="form-select select2-supplier @error('id_supplier_manual') is-invalid @enderror" id="id_supplier_manual" name="id_supplier_manual" data-placeholder="Pilih Supplier (Opsional)">
                                <option value=""></option>
                                @foreach ($suppliers as $id => $nama)
                                    <option value="{{ $id }}" {{ old('id_supplier_manual') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                            @error('id_supplier_manual') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label for="diterima_at" class="form-label required-label">Tanggal Penerimaan Fisik</label>
                        <input type="datetime-local" class="form-control @error('diterima_at') is-invalid @enderror" id="diterima_at" name="diterima_at" value="{{ old('diterima_at', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('diterima_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="no_surat_jalan" class="form-label">No. Surat Jalan Supplier (Opsional)</label>
                        <input type="text" class="form-control @error('no_surat_jalan') is-invalid @enderror" id="no_surat_jalan" name="no_surat_jalan" value="{{ old('no_surat_jalan') }}">
                        @error('no_surat_jalan') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    <i class="bi bi-plus-circle"></i> Tambah Item Manual
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
                            @if($tipe_penerimaan === 'PO' && !empty($detailItems))
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
                                                   value="{{ old('items.'.$index.'.jumlah_diterima_sekarang', $item['jumlah_diterima_sekarang']) }}"
                                                   required min="0" max="{{ $item['jumlah_belum_diterima'] }}" step="1"
                                                   data-max-qty="{{ $item['jumlah_belum_diterima'] }}"
                                                   data-item-index="{{ $index }}"
                                                   data-has-serial="{{ $item['memiliki_serial'] ? 'true' : 'false' }}">
                                        </td>
                                        <td>
                                            <select class="form-select item-lokasi @error('items.'.$index.'.lokasi') is-invalid @enderror" name="items[{{ $index }}][lokasi]" required>
                                                @foreach($lokasiPenyimpanan as $val => $label)
                                                    <option value="{{ $val }}" {{ old('items.'.$index.'.lokasi', 'GUDANG') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select item-kondisi @error('items.'.$index.'.kondisi') is-invalid @enderror" name="items[{{ $index }}][kondisi]" required>
                                                @foreach($kondisiBarang as $val => $label)
                                                    <option value="{{ $val }}" {{ old('items.'.$index.'.kondisi', 'BAIK') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                             <select class="form-select item-tipe-garansi @error('items.'.$index.'.tipe_garansi') is-invalid @enderror" name="items[{{ $index }}][tipe_garansi]" required>
                                                @foreach($tipeGaransi as $val => $label)
                                                    <option value="{{ $val }}" {{ old('items.'.$index.'.tipe_garansi', 'NONE') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="serial-input-area-container" id="serial-input-area-container-{{ $index }}" style="{{ $item['memiliki_serial'] ? '' : 'display: none;' }}">
                                                <div class="serial-input-container">
                                                    {{-- Input serial akan ditambahkan oleh JS --}}
                                                </div>
                                                <small class="text-muted serial-count-feedback" id="serial-count-feedback-{{ $index }}">Jumlah No. Seri: 0</small>
                                                <div class="invalid-feedback d-block serial-error-feedback" id="serial-error-feedback-{{ $index }}"></div>
                                            </div>
                                            @if(!$item['memiliki_serial']) <span class="text-muted">-</span> @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @elseif($tipe_penerimaan === 'MANUAL' && !empty(old('items')))
                                {{-- Handle old input untuk mode manual jika ada error validasi --}}
                                @foreach(old('items') as $index => $oldItem)
                                    @php
                                        $produk = \App\Models\Produk::find($oldItem['id_produk']);
                                        $hasSerial = $produk ? $produk->memiliki_serial : false;
                                    @endphp
                                    <tr class="detail-item-row manual-item" data-index="{{ $index }}" data-product-id="{{ $oldItem['id_produk'] }}" data-has-serial="{{ $hasSerial ? 'true' : 'false' }}">
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-danger btn-sm delete-manual-item-btn" title="Hapus Item"><i class="bi bi-trash"></i></button>
                                        </td>
                                        <td>
                                            <select class="form-select product-select-manual" name="items[{{ $index }}][id_produk]" required data-placeholder="Cari Produk...">
                                                @if($produk)
                                                    <option value="{{ $produk->id }}" selected>{{ $produk->nama }} ({{ $produk->kode_produk }})</option>
                                                @endif
                                            </select>
                                            <input type="hidden" class="has-serial-flag" value="{{ $hasSerial ? 'true' : 'false' }}">
                                        </td>
                                        <td class="text-center">-</td>
                                        <td class="text-center">-</td>
                                        <td>
                                            <input type="number" class="form-control item-jumlah-diterima text-end" name="items[{{ $index }}][jumlah_diterima_sekarang]" value="{{ $oldItem['jumlah_diterima_sekarang'] ?? 1 }}" required min="1" step="1" data-item-index="{{ $index }}" data-has-serial="{{ $hasSerial ? 'true' : 'false' }}">
                                        </td>
                                        <td>
                                            <select class="form-select item-lokasi" name="items[{{ $index }}][lokasi]" required>
                                                @foreach($lokasiPenyimpanan as $val => $label)
                                                    <option value="{{ $val }}" {{ ($oldItem['lokasi'] ?? 'GUDANG') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select item-kondisi" name="items[{{ $index }}][kondisi]" required>
                                                @foreach($kondisiBarang as $val => $label)
                                                    <option value="{{ $val }}" {{ ($oldItem['kondisi'] ?? 'BAIK') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                             <select class="form-select item-tipe-garansi" name="items[{{ $index }}][tipe_garansi]" required>
                                                @foreach($tipeGaransi as $val => $label)
                                                    <option value="{{ $val }}" {{ ($oldItem['tipe_garansi'] ?? 'NONE') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="serial-input-area-container" id="serial-input-area-container-{{ $index }}" style="{{ $hasSerial ? '' : 'display: none;' }}">
                                                <div class="serial-input-container">
                                                    @if($hasSerial && isset($oldItem['nomor_seri']) && is_array($oldItem['nomor_seri']))
                                                        @foreach($oldItem['nomor_seri'] as $serial_idx => $serial_val)
                                                        <input type="text" name="items[{{ $index }}][nomor_seri][]" class="form-control mb-1 nomor-seri-input" placeholder="Nomor Seri {{ $serial_idx + 1 }}" value="{{ $serial_val }}" required>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                <small class="text-muted serial-count-feedback" id="serial-count-feedback-{{ $index }}">Jumlah No. Seri: {{ $hasSerial && isset($oldItem['nomor_seri']) ? count($oldItem['nomor_seri']) : 0 }}</small>
                                                <div class="invalid-feedback d-block serial-error-feedback" id="serial-error-feedback-{{ $index }}"></div>
                                            </div>
                                            @if(!$hasSerial) <span class="text-muted">-</span> @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                 @if($tipe_penerimaan === 'PO' && empty($detailItems))
                    <div class="alert alert-warning text-center">
                        Tidak ada item dari PO ini yang perlu diterima atau semua item sudah diterima sepenuhnya.
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-3 text-end">
            <a href="{{ route('gudang.penerimaan.index') }}" class="btn btn-secondary me-2">
                <i class="bi bi-x-circle me-1"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary" id="submit-penerimaan-btn">
                <i class="bi bi-save me-1"></i> Simpan Penerimaan
            </button>
        </div>
    </form>
</div>

{{-- Template untuk baris item manual (hidden) --}}
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
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        function initializeManualProductSelect(element, itemIndex) {
            $(element).select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: $(element).data('placeholder'),
                allowClear: true,
                ajax: {
                    url: "{{ route('admin.ajax.produk.search') }}", // Pastikan route ini ada dan mengembalikan format yang benar
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term, page: params.page || 1 }; },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.items.map(function(item) { return { id: item.id, text: item.text, has_serial: item.memiliki_serial }; }), // Kirim juga info serial
                            pagination: { more: (params.page * 15) < data.total_count } // Asumsi 15 item per page
                        };
                    },
                    cache: true
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

        function updateSerialInputs(jumlahInput) {
            const row = $(jumlahInput).closest('.detail-item-row');
            const itemIndex = $(jumlahInput).data('item-index');
            const hasSerial = $(jumlahInput).data('has-serial') === true || $(jumlahInput).data('has-serial') === 'true';
            const qtyDiterima = parseInt($(jumlahInput).val()) || 0;
            const serialContainer = row.find('.serial-input-container');

            serialContainer.empty(); // Selalu bersihkan dulu
            toggleSerialInputArea(row, hasSerial && qtyDiterima > 0);

            if (hasSerial && qtyDiterima > 0) {
                for (let i = 0; i < qtyDiterima; i++) {
                    serialContainer.append(
                        `<input type="text" name="items[${itemIndex}][nomor_seri][]" class="form-control mb-1 nomor-seri-input" placeholder="Nomor Seri ${i + 1}" required>`
                    );
                }
            }
            updateSerialCountFeedback(row, serialContainer.find('.nomor-seri-input').length);
            validateSerialNumbers(row); // Validasi setelah update
        }

        function updateSerialCountFeedback(row, count) {
            row.find('.serial-count-feedback').text('Jumlah No. Seri: ' + count);
        }

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