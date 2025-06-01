@extends('layouts.app') 

@section('title', 'Alokasi Stok untuk Pesanan: ' . $penjualan->nomor_penjualan)

@push('styles')
    <style>
        /* Style tambahan jika perlu untuk form alokasi */
        .item-alokasi { border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem; }
        .item-alokasi:last-child { border-bottom: none; }
        .batch-pilihan-area { margin-top: 0.5rem; padding-left: 1.5rem; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <form action="{{ route('admin.pesan_barang_alokasi.store', $penjualan->id) }}" method="POST" id="form-alokasi-stok">
        @csrf
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Alokasi Stok untuk Pesanan: {{ $penjualan->nomor_penjualan }}</h5>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>No. Pesanan:</strong> {{ $penjualan->nomor_penjualan }}
                    </div>
                    <div class="col-md-4">
                        <strong>Pelanggan:</strong> {{ $penjualan->pelanggan->nama ?? 'Umum' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Tanggal Pesan:</strong> {{ $penjualan->tanggal_penjualan->isoFormat('D MMM YYYY') }}
                    </div>
                </div>
                <hr>

                <h6 class="mb-3">Detail Item Pesanan:</h6>
                @foreach ($penjualan->detailPenjualan as $index => $detail)
                    <div class="item-alokasi" data-id-detail-penjualan="{{ $detail->id }}" data-id-produk="{{ $detail->produk->id }}" data-memiliki-serial="{{ $detail->produk->memiliki_serial ? 'true' : 'false' }}">
                        <input type="hidden" name="alokasi_items[{{ $index }}][id_detail_penjualan]" value="{{ $detail->id }}">
                        <div class="row">
                            <div class="col-md-5">
                                <strong>Produk:</strong> {{ $detail->produk->nama }} <br>
                                <small class="text-muted">Kode: {{ $detail->produk->kode_produk }}</small>
                            </div>
                            <div class="col-md-2 text-center">
                                <strong>Dipesan:</strong> <span class="qty-dipesan">{{ $detail->jumlah }}</span> unit
                            </div>
                            <div class="col-md-3 text-center">
                                <strong>Kurang Alokasi:</strong>
                                <span class="qty-kurang-alokasi fw-bold {{ $detail->jumlah_kurang_dialokasikan > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $detail->jumlah_kurang_dialokasikan }}
                                </span> unit
                            </div>
                            <div class="col-md-2 text-center">
                                @if ($detail->jumlah_kurang_dialokasikan > 0)
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-pilih-batch-admin"
                                            data-bs-toggle="modal" data-bs-target="#modalPilihBatchAdmin"
                                            data-detail-id="{{ $detail->id }}"
                                            data-produk-id="{{ $detail->produk->id }}"
                                            data-produk-nama="{{ $detail->produk->nama }}"
                                            data-qty-dibutuhkan="{{ $detail->jumlah_kurang_dialokasikan }}"
                                            data-memiliki-serial="{{ $detail->produk->memiliki_serial ? 'true' : 'false' }}">
                                        Pilih Batch
                                    </button>
                                @else
                                    <span class="badge bg-success">Sudah Dialokasikan</span>
                                @endif
                            </div>
                        </div>
                        {{-- Area untuk menampilkan batch & serial yang sudah dipilih untuk item ini --}}
                        <div class="selected-allocations-display mt-2 ps-3" id="display-alokasi-detail-{{ $detail->id }}">
                            {{-- Contoh tampilan jika sudah ada alokasi (dari load data, atau setelah dipilih dari modal) --}}
                            @if($detail->stokAlokasi->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')->isNotEmpty())
                                @foreach($detail->stokAlokasi->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN') as $alok)
                                    <div class="alert alert-secondary alert-sm p-1 mb-1 existing-allocation"
                                         data-id-stok-barang="{{ $alok->id_stok_barang }}"
                                         data-qty-allocated="{{ $alok->jumlah_diambil }}"
                                         data-serials-selected="{{ $alok->nomor_seri_terkait }}">
                                        <small>
                                            Batch ID: {{ $alok->id_stok_barang }} (Qty: {{ $alok->jumlah_diambil }})
                                            @if($alok->nomor_seri_terkait) <br>SN: {{ str_replace(',', ', ', $alok->nomor_seri_terkait) }} @endif
                                        </small>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        {{-- Hidden input untuk menyimpan data alokasi batch & serial per item --}}
                        <input type="hidden" class="item-alokasi-batch-json" name="alokasi_items[{{ $index }}][alokasi_batch]" value="[]">
                    </div>
                @endforeach
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.pesan_barang_alokasi.index') }}" class="btn btn-secondary">Kembali ke Daftar</a>
                <button type="submit" class="btn btn-primary" id="btn-simpan-alokasi">
                    <i class="bi bi-save me-1"></i> Simpan Alokasi
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Modal Pilih Batch & Serial untuk Admin --}}
<div class="modal fade" id="modalPilihBatchAdmin" tabindex="-1" aria-labelledby="modalPilihBatchAdminLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"> {{-- Modal lebih besar --}}
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPilihBatchAdminLabel">Alokasikan Stok untuk: <span id="nama-produk-modal-admin"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_admin_detail_id">
                <input type="hidden" id="modal_admin_produk_id">
                <input type="hidden" id="modal_admin_memiliki_serial">
                <input type="hidden" id="modal_admin_qty_dibutuhkan_total_item">

                <div class="alert alert-info">
                    Produk: <strong id="nama-produk-modal-admin-alert"></strong><br>
                    Kebutuhan untuk item ini: <strong id="qty-dibutuhkan-info-modal-admin">X</strong> unit.<br>
                    Total dipilih dari batch: <strong id="modal-admin-qty-terpilih-display" class="text-primary">0</strong> unit.
                </div>
                <div id="admin-batch-allocation-details" style="max-height: 400px; overflow-y: auto;">
                    {{-- Konten dinamis untuk pilihan batch & serial --}}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-terapkan-pilihan-batch-admin">
                    <i class="bi bi-check-circle me-1"></i> Terapkan Alokasi untuk Item Ini
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        // Variabel global untuk modal admin
        let currentDetailIdForAdminModal = null;
        let currentProdukIdForAdminModal = null;
        let currentMemilikiSerialForAdminModal = false;
        let currentQtyDibutuhkanTotalForItemAdminModal = 0;
        let currentAlokasiBatchUntukItem = []; // Untuk menyimpan [{id_stok_barang, qty_dialokasikan, serials_selected:[]}]

        $(document).ready(function() {
            // Event handler untuk tombol "Pilih Batch" per item
            $('.btn-pilih-batch-admin').on('click', function() {
                currentDetailIdForAdminModal = $(this).data('detail-id');
                currentProdukIdForAdminModal = $(this).data('produk-id');
                const produkNama = $(this).data('produk-nama');
                currentQtyDibutuhkanTotalForItemAdminModal = parseInt($(this).data('qty-dibutuhkan'));
                currentMemilikiSerialForAdminModal = $(this).data('memiliki-serial') === true || $(this).data('memiliki-serial') === 'true';

                $('#modal_admin_detail_id').val(currentDetailIdForAdminModal);
                $('#modal_admin_produk_id').val(currentProdukIdForAdminModal);
                $('#modal_admin_memiliki_serial').val(currentMemilikiSerialForAdminModal.toString());
                $('#modal_admin_qty_dibutuhkan_total_item').val(currentQtyDibutuhkanTotalForItemAdminModal);

                $('#nama-produk-modal-admin').text(produkNama);
                $('#nama-produk-modal-admin-alert').text(produkNama);
                $('#qty-dibutuhkan-info-modal-admin').text(currentQtyDibutuhkanTotalForItemAdminModal);
                $('#modal-admin-qty-terpilih-display').text('0').removeClass('text-success text-danger').addClass('text-primary');
                $('#admin-batch-allocation-details').html('<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat batch tersedia...</p></div>');
                $('#btn-terapkan-pilihan-batch-admin').prop('disabled', true);

                // Muat alokasi yang sudah ada untuk item ini (jika ada)
                currentAlokasiBatchUntukItem = []; // Reset
                const existingAlokasiJson = $(`.item-alokasi[data-id-detail-penjualan="${currentDetailIdForAdminModal}"] .item-alokasi-batch-json`).val();
                if (existingAlokasiJson && existingAlokasiJson !== '[]') {
                    try {
                        currentAlokasiBatchUntukItem = JSON.parse(existingAlokasiJson);
                    } catch (e) { console.error("Error parsing existing allocation JSON:", e); }
                }


                $.ajax({
                    url: "{{ route('admin.pesan_barang_alokasi.ajax.available_batches') }}",
                    type: 'GET',
                    data: {
                        id_produk: currentProdukIdForAdminModal,
                        id_penjualan_current: "{{ $penjualan->id }}" // Kirim ID penjualan saat ini
                    },
                    success: function(response) {
                        if (response.success) {
                            if (!response.batches_data || response.batches_data.length === 0) {
                                $('#admin-batch-allocation-details').html('<p class="text-danger text-center">Tidak ada batch stok yang tersedia untuk produk ini.</p>');
                                return;
                            }
                            displayAdminAvailableBatches(response.batches_data, currentQtyDibutuhkanTotalForItemAdminModal, currentMemilikiSerialForAdminModal);
                        } else {
                            $('#admin-batch-allocation-details').html(`<p class="text-danger text-center">${response.message || 'Gagal memuat data batch.'}</p>`);
                        }
                    },
                    error: function(jqXHR) {
                        $('#admin-batch-allocation-details').html(`<p class="text-danger text-center">Error AJAX: Gagal mengambil data batch. ${jqXHR.responseJSON?.message || jqXHR.statusText}</p>`);
                    }
                });
                // Modal akan ditampilkan oleh atribut data-bs-toggle
            });

            function displayAdminAvailableBatches(batchesData, qtyDibutuhkanItem, produkMemilikiSerial) {
                let batchListHtml = `<p class="mb-2"><small>Pilih kuantitas dari batch yang tersedia. Sistem akan mencoba mengalokasikan secara FIFO.</small></p>
                                     <div class="list-group">`;

                const totalStokEfektifDariSemuaBatch = batchesData.reduce((sum, batch) => sum + batch.jumlah_tersedia, 0);
                if (totalStokEfektifDariSemuaBatch < qtyDibutuhkanItem) {
                     batchListHtml += `<div class="alert alert-warning p-2 mb-2"><small>Stok efektif (${totalStokEfektifDariSemuaBatch}) kurang dari kebutuhan item (${qtyDibutuhkanItem}).</small></div>`;
                }


                // Pre-fill qty dari alokasi yang sudah ada (currentAlokasiBatchUntukItem)
                // Dan implementasi FIFO untuk sisa kebutuhan
                let sisaKebutuhanItem = qtyDibutuhkanItem;
                let tempAlokasiDariExisting = {};
                currentAlokasiBatchUntukItem.forEach(alok => {
                    tempAlokasiDariExisting[alok.id_stok_barang] = {
                        qty: alok.qty_dialokasikan,
                        serials: alok.serials_selected || []
                    };
                    sisaKebutuhanItem -= alok.qty_dialokasikan;
                });
                sisaKebutuhanItem = Math.max(0, sisaKebutuhanItem); // Pastikan tidak negatif


                batchesData.forEach((batch, index) => {
                    let qtyUntukBatchIni = 0;
                    let serialsUntukBatchIni = [];

                    // Cek apakah batch ini sudah ada di alokasi yang ada
                    if (tempAlokasiDariExisting[batch.id]) {
                        qtyUntukBatchIni = tempAlokasiDariExisting[batch.id].qty;
                        serialsUntukBatchIni = tempAlokasiDariExisting[batch.id].serials;
                    } else if (sisaKebutuhanItem > 0) {
                        // Jika belum ada di alokasi, coba penuhi dari sisa kebutuhan (FIFO)
                        qtyUntukBatchIni = Math.min(batch.jumlah_tersedia, sisaKebutuhanItem);
                        sisaKebutuhanItem -= qtyUntukBatchIni;
                    }

                    const showSerialContainerInitially = produkMemilikiSerial && qtyUntukBatchIni > 0;

                    batchListHtml += `
                        <div class="list-group-item admin-batch-selection-item p-2 mb-2 shadow-sm rounded"
                             data-id-stok-barang="${batch.id}"
                             data-max-stok-efektif-batch="${batch.jumlah_tersedia}">
                            <div class="row align-items-center g-2">
                                <div class="col-md-7">
                                    <strong class="d-block">Batch ID: ${batch.id}</strong>
                                    <small class="d-block">Terima: ${batch.diterima_at_formatted} | Stok Efektif: ${batch.jumlah_tersedia} (Fisik: ${batch.jumlah_fisik_batch})</small>
                                    <small class="d-block">Lok: ${batch.lokasi} | Gar: ${batch.tipe_garansi_display} | Tipe: ${batch.tipe_stok_display}</small>
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Alokasi:</span>
                                        <input type="number" class="form-control admin-qty-from-batch-input text-center"
                                               value="${qtyUntukBatchIni}" min="0" max="${batch.jumlah_tersedia}"
                                               data-id-stok-barang="${batch.id}">
                                    </div>
                                    <div class="invalid-feedback admin-qty-error-feedback d-block text-end"></div>
                                </div>
                            </div>
                            ${produkMemilikiSerial ? `<div class="mt-2 admin-serial-container-for-batch" id="admin-serials-for-batch-${batch.id}" style="display:${showSerialContainerInitially ? 'block':'none'}; data-existing-serials='${JSON.stringify(serialsUntukBatchIni)}'"></div>` : ''}
                        </div>`;
                });
                batchListHtml += '</div>';
                $('#admin-batch-allocation-details').html(batchListHtml);

                attachAdminQtyInputBatchEvents(produkMemilikiSerial, totalStokEfektifDariSemuaBatch);
                updateAdminTotalQtyTerpilihDisplay(); // Panggil setelah render batch

                if (produkMemilikiSerial) {
                    $('.admin-batch-selection-item').each(function() {
                        const qtyInput = $(this).find('.admin-qty-from-batch-input');
                        if (parseInt(qtyInput.val()) > 0) {
                            const idStokBarang = $(this).data('id-stok-barang');
                            const existingSerialsForThisBatch = JSON.parse($(`#admin-serials-for-batch-${idStokBarang}`).attr('data-existing-serials') || '[]');
                            loadAdminNomorSeriUntukAlokasi(idStokBarang, parseInt(qtyInput.val()), existingSerialsForThisBatch);
                        }
                    });
                }
            }

            function attachAdminQtyInputBatchEvents(produkMemilikiSerial, totalStokEfektifAllBatches) {
                $('.admin-qty-from-batch-input').off('input change').on('input change', function() {
                    let val = parseInt($(this).val()) || 0;
                    const maxInThisBatch = parseInt($(this).closest('.admin-batch-selection-item').data('max-stok-efektif-batch'));
                    const idStokBarang = $(this).data('id-stok-barang');
                    const serialContainer = $(`#admin-serials-for-batch-${idStokBarang}`);
                    const errorFeedback = $(this).parent().siblings('.admin-qty-error-feedback');

                    $(this).removeClass('is-invalid'); errorFeedback.text('');
                    if (val < 0) val = 0;
                    if (val > maxInThisBatch) {
                        val = maxInThisBatch;
                        $(this).val(val).addClass('is-invalid');
                        errorFeedback.text(`Maks. ${maxInThisBatch} unit.`);
                    }

                    let totalQtyCurrentlySelected = 0;
                    $('.admin-qty-from-batch-input').each(function() { totalQtyCurrentlySelected += parseInt($(this).val()) || 0; });

                    const maxAllowedToSelectItem = Math.min(currentQtyDibutuhkanTotalForItemAdminModal, totalStokEfektifAllBatches);

                    if (totalQtyCurrentlySelected > maxAllowedToSelectItem) {
                        const diff = totalQtyCurrentlySelected - maxAllowedToSelectItem;
                        val = Math.max(0, val - diff);
                        $(this).val(val);
                        $(this).addClass('is-invalid');
                        errorFeedback.text(`Total pilihan melebihi kebutuhan/stok (${maxAllowedToSelectItem}).`);
                        totalQtyCurrentlySelected = 0; // Recalculate
                        $('.admin-qty-from-batch-input').each(function() { totalQtyCurrentlySelected += parseInt($(this).val()) || 0; });
                    }
                    updateAdminTotalQtyTerpilihDisplay(totalQtyCurrentlySelected);

                    if (produkMemilikiSerial) {
                        if (val > 0) {
                            serialContainer.show();
                            // Saat qty berubah, kita perlu memuat ulang serial dengan existing serial yang mungkin sudah ada jika user edit
                            const existingSerials = JSON.parse(serialContainer.attr('data-existing-serials') || '[]');
                            loadAdminNomorSeriUntukAlokasi(idStokBarang, val, existingSerials);
                        } else {
                            serialContainer.hide().empty().attr('data-existing-serials', '[]'); // Reset existing serials
                            checkAdminOverallModalValidity();
                        }
                    } else {
                        checkAdminOverallModalValidity();
                    }
                });
            }

            function updateAdminTotalQtyTerpilihDisplay(currentTotalSelected = null) {
                let totalQtyTerpilih;
                if (currentTotalSelected !== null) {
                    totalQtyTerpilih = currentTotalSelected;
                } else {
                    totalQtyTerpilih = 0;
                    $('.admin-qty-from-batch-input').each(function() { totalQtyTerpilih += parseInt($(this).val()) || 0; });
                }

                $('#modal-admin-qty-terpilih-display').text(totalQtyTerpilih);
                const qtyDibutuhkan = currentQtyDibutuhkanTotalForItemAdminModal;

                if (totalQtyTerpilih === qtyDibutuhkan) {
                    $('#modal-admin-qty-terpilih-display').removeClass('text-danger text-primary').addClass('text-success');
                } else if (totalQtyTerpilih > qtyDibutuhkan) {
                    $('#modal-admin-qty-terpilih-display').removeClass('text-success text-primary').addClass('text-danger');
                } else {
                    $('#modal-admin-qty-terpilih-display').removeClass('text-success text-danger').addClass('text-primary');
                }
                checkAdminOverallModalValidity();
            }

            function loadAdminNomorSeriUntukAlokasi(idStokBarang, qtyDibutuhkanDariBatchIni, preSelectedSerials = []) {
                const serialContainerTarget = $(`#admin-serials-for-batch-${idStokBarang}`);
                serialContainerTarget.html('<div class="text-center p-1"><small><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat serial...</small></div>');

                $.ajax({
                    url: "{{ route('admin.pesan_barang_alokasi.ajax.available_serials') }}", type: 'GET',
                    data: {
                        id_stok_barang: idStokBarang,
                        id_penjualan_current: "{{ $penjualan->id }}" // Kirim ID penjualan saat ini
                    },
                    success: function(response) {
                        serialContainerTarget.empty();
                        if (response.success && response.serials) {
                            // Gabungkan serial tersedia dari DB dengan serial yang sudah dipilih sebelumnya (jika ada, untuk kasus edit)
                            let allPossibleSerials = [...new Set([...response.serials, ...preSelectedSerials])].sort();

                            if (allPossibleSerials.length < qtyDibutuhkanDariBatchIni && preSelectedSerials.length < qtyDibutuhkanDariBatchIni) {
                                 serialContainerTarget.append(`<p class="text-danger mb-1"><small>Stok serial (${allPossibleSerials.length}) kurang dari kebutuhan (${qtyDibutuhkanDariBatchIni}) untuk Batch ID ${idStokBarang}.</small></p>`);
                            }

                            let serialHtml = `<p class="mb-1"><small>Pilih ${qtyDibutuhkanDariBatchIni} serial untuk Batch ID: ${idStokBarang}</small></p>
                                              <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-1 admin-serial-checkbox-group">`; // 3 kolom di md
                            let checkedCount = 0;
                            allPossibleSerials.forEach(function(serial, index) {
                                let isChecked = false;
                                if(preSelectedSerials.includes(serial) && checkedCount < qtyDibutuhkanDariBatchIni){
                                    isChecked = true;
                                    checkedCount++;
                                } else if (response.serials.includes(serial) && checkedCount < qtyDibutuhkanDariBatchIni && !preSelectedSerials.includes(serial)){
                                    // Jika belum ada di preSelected, dan masih ada di response.serials, dan belum cukup
                                    isChecked = true;
                                    checkedCount++;
                                }

                                serialHtml += `
                                    <div class="col">
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input admin-nomor-seri-checkbox" type="checkbox" value="${serial}"
                                                   id="admin-serial-batch-${idStokBarang}-${index.toString().replace(/\s+/g, '-')}"
                                                   data-id-stok-barang="${idStokBarang}" ${isChecked ? 'checked' : ''}>
                                            <label class="form-check-label" for="admin-serial-batch-${idStokBarang}-${index.toString().replace(/\s+/g, '-')}"><small>${serial}</small></label>
                                        </div>
                                    </div>`;
                            });
                            serialHtml += '</div>';
                            serialContainerTarget.append(serialHtml);
                            attachAdminMultiBatchSerialCheckboxEvents();
                        } else {
                            serialContainerTarget.append(`<p class="text-warning mb-1"><small>${response.message || `Tidak ada serial bisa dipilih untuk Batch ID: ${idStokBarang}.`}</small></p>`);
                        }
                        checkAdminOverallModalValidity();
                    },
                    error: function() {
                        serialContainerTarget.html(`<p class="text-danger mb-1"><small>Error AJAX memuat serial untuk Batch ID: ${idStokBarang}.</small></p>`);
                        checkAdminOverallModalValidity();
                    }
                });
            }

            function attachAdminMultiBatchSerialCheckboxEvents() {
                $('.admin-nomor-seri-checkbox').off('change').on('change', function() {
                    const idStokBarang = $(this).data('id-stok-barang');
                    const qtyNeededForThisBatch = parseInt($(`.admin-qty-from-batch-input[data-id-stok-barang="${idStokBarang}"]`).val()) || 0;
                    const checkedInThisGroup = $(`.admin-nomor-seri-checkbox[data-id-stok-barang="${idStokBarang}"]:checked`).length;

                    if (checkedInThisGroup > qtyNeededForThisBatch) {
                        $(this).prop('checked', false);
                        Swal.fire('Perhatian', `Anda hanya boleh memilih ${qtyNeededForThisBatch} serial untuk batch ini.`, 'warning');
                    }
                    checkAdminOverallModalValidity();
                });
                checkAdminOverallModalValidity();
            }

            function checkAdminOverallModalValidity() {
                let totalQtyTerpilihDariBatchInputs = 0;
                $('.admin-qty-from-batch-input').each(function() { totalQtyTerpilihDariBatchInputs += parseInt($(this).val()) || 0; });
                const qtyDibutuhkanGlobalItem = currentQtyDibutuhkanTotalForItemAdminModal;

                if (totalQtyTerpilihDariBatchInputs !== qtyDibutuhkanGlobalItem) {
                    $('#btn-terapkan-pilihan-batch-admin').prop('disabled', true).html('<i class="bi bi-exclamation-triangle me-1"></i> Qty Belum Sesuai');
                    return;
                }

                let allSerialsValid = true;
                if (currentMemilikiSerialForAdminModal) {
                    $('.admin-qty-from-batch-input').each(function() {
                        const qtyDariBatchIni = parseInt($(this).val()) || 0;
                        if (qtyDariBatchIni > 0) {
                            const idStokBarang = $(this).data('id-stok-barang');
                            const serialsCheckedForThisBatch = $(`.admin-nomor-seri-checkbox[data-id-stok-barang="${idStokBarang}"]:checked`).length;
                            const serialContainerForThisBatch = $(`#admin-serials-for-batch-${idStokBarang}`);
                            if (serialsCheckedForThisBatch !== qtyDariBatchIni) {
                                allSerialsValid = false; return false;
                            }
                            if (serialContainerForThisBatch.find('p.text-danger').length > 0) { // Cek jika ada error load serial
                                allSerialsValid = false; return false;
                            }
                        }
                    });
                }
                $('#btn-terapkan-pilihan-batch-admin').prop('disabled', !allSerialsValid)
                    .html(allSerialsValid ? '<i class="bi bi-check-circle me-1"></i> Terapkan Alokasi' : '<i class="bi bi-x-circle me-1"></i> Periksa Serial');
            }

            // Tombol Terapkan Alokasi dari Modal Admin
            $('#btn-terapkan-pilihan-batch-admin').on('click', function() {
                if (!currentDetailIdForAdminModal) return;

                let alokasiUntukItemIni = [];
                let totalQtyDialokasikanDiModal = 0;
                let validModal = true;

                $('.admin-batch-selection-item').each(function() {
                    const idStokBarang = $(this).data('id-stok-barang');
                    const qtyAllocated = parseInt($(this).find('.admin-qty-from-batch-input').val()) || 0;
                    totalQtyDialokasikanDiModal += qtyAllocated;

                    if (qtyAllocated > 0) {
                        let serialsSelectedForThisBatch = [];
                        if (currentMemilikiSerialForAdminModal) {
                            $(`.admin-nomor-seri-checkbox[data-id-stok-barang="${idStokBarang}"]:checked`).each(function() {
                                serialsSelectedForThisBatch.push($(this).val());
                            });
                            if (serialsSelectedForThisBatch.length !== qtyAllocated) {
                                Swal.fire('Validasi Gagal', `Jumlah serial (${serialsSelectedForThisBatch.length}) untuk Batch ID ${idStokBarang} tidak sesuai dengan kuantitas (${qtyAllocated}).`, 'error');
                                validModal = false; return false;
                            }
                        }
                        alokasiUntukItemIni.push({
                            id_stok_barang: idStokBarang,
                            qty_dialokasikan: qtyAllocated,
                            serials_selected: serialsSelectedForThisBatch
                        });
                    }
                });

                if (!validModal) return;

                if (totalQtyDialokasikanDiModal !== currentQtyDibutuhkanTotalForItemAdminModal) {
                    Swal.fire('Validasi Gagal', `Total kuantitas terpilih (${totalQtyDialokasikanDiModal}) tidak sesuai kebutuhan item (${currentQtyDibutuhkanTotalForItemAdminModal}).`, 'error');
                    return;
                }

                // Update hidden input di form utama
                const targetItemAlokasiDiv = $(`.item-alokasi[data-id-detail-penjualan="${currentDetailIdForAdminModal}"]`);
                targetItemAlokasiDiv.find('.item-alokasi-batch-json').val(JSON.stringify(alokasiUntukItemIni));

                // Update tampilan ringkasan alokasi di form utama
                let displayHtml = '';
                if (alokasiUntukItemIni.length > 0) {
                    alokasiUntukItemIni.forEach(alok => {
                        displayHtml += `<div class="alert alert-secondary alert-sm p-1 mb-1">
                                            <small>Batch ID: ${alok.id_stok_barang} (Qty: ${alok.qty_dialokasikan})
                                            ${alok.serials_selected.length > 0 ? '<br>SN: ' + alok.serials_selected.join(', ') : ''}
                                            </small>
                                        </div>`;
                    });
                } else {
                     displayHtml = '<small class="text-muted"><em>Belum ada batch dialokasikan.</em></small>';
                }
                $(`#display-alokasi-detail-${currentDetailIdForAdminModal}`).html(displayHtml);

                // Update tampilan jumlah kurang dialokasikan di form utama
                const qtyKurangUpdate = currentQtyDibutuhkanTotalForItemAdminModal - totalQtyDialokasikanDiModal;
                targetItemAlokasiDiv.find('.qty-kurang-alokasi').text(qtyKurangUpdate)
                    .toggleClass('text-danger', qtyKurangUpdate > 0)
                    .toggleClass('text-success', qtyKurangUpdate === 0);
                targetItemAlokasiDiv.find('.btn-pilih-batch-admin').prop('disabled', qtyKurangUpdate === 0);


                $('#modalPilihBatchAdmin').modal('hide');
                Swal.fire('Berhasil', 'Alokasi untuk item ini telah diterapkan. Jangan lupa simpan alokasi keseluruhan.', 'success');
            });

            // Validasi Form Submit Utama Admin
            $('#form-alokasi-stok').on('submit', function(e){
                let semuaItemSudahDialokasikanPenuh = true;
                let adaAlokasiYangDibuat = false;

                $('.item-alokasi').each(function(){
                    const qtyDipesan = parseInt($(this).find('.qty-dipesan').text()) || 0;
                    const alokasiJsonString = $(this).find('.item-alokasi-batch-json').val();
                    let totalQtyDialokasikanUntukItemIni = 0;
                    if(alokasiJsonString && alokasiJsonString !== '[]'){
                        try {
                            const alokasiBatchPerItem = JSON.parse(alokasiJsonString);
                            alokasiBatchPerItem.forEach(alok => {
                                totalQtyDialokasikanUntukItemIni += alok.qty_dialokasikan;
                                adaAlokasiYangDibuat = true; // Setidaknya ada satu alokasi
                            });
                        } catch(err) {
                            // Biarkan backend handle JSON tidak valid jika lolos dari modal
                        }
                    }
                    if(totalQtyDialokasikanUntukItemIni < qtyDipesan){
                        semuaItemSudahDialokasikanPenuh = false;
                    }
                });

                if(!adaAlokasiYangDibuat){
                     e.preventDefault();
                     Swal.fire('Perhatian!', 'Anda belum melakukan alokasi stok untuk item manapun dalam pesanan ini.', 'warning');
                     return;
                }

                if(!semuaItemSudahDialokasikanPenuh){
                    e.preventDefault();
                    Swal.fire({
                        title: 'Alokasi Belum Lengkap',
                        text: "Beberapa item dalam pesanan ini belum dialokasikan sepenuhnya. Apakah Anda yakin ingin menyimpan alokasi parsial? Status pesanan akan tetap 'Menunggu Barang'.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Simpan Parsial!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#btn-simpan-alokasi').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...');
                            $(this).off('submit').submit(); // Hapus event handler submit agar tidak loop, lalu submit
                        }
                    });
                } else {
                    $('#btn-simpan-alokasi').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...');
                    // Lanjutkan submit normal
                }
            });


        }); // End document ready
    </script>
@endpush