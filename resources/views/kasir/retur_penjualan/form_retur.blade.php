@extends('layouts.app')

@section('title', 'Form Retur Penjualan: ' . $penjualan->nomor_penjualan)

@push('styles')
    {{-- Jika perlu Select2 atau style khusus --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .item-retur-card { margin-bottom: 1.5rem; border-left: 5px solid #0dcaf0; }
        .serial-checkbox-list .form-check { margin-bottom: 0.25rem; }
        .required-label::after { content: " *"; color: red; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Form Retur Penjualan</h1>

    <form action="{{ route('kasir.retur_penjualan.store', $penjualan->id) }}" method="POST" id="form-retur-penjualan">
        @csrf
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Detail Transaksi Penjualan Asal</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><strong>No. Nota Asal:</strong> {{ $penjualan->nomor_penjualan }}</div>
                    <div class="col-md-4"><strong>Pelanggan:</strong> {{ $penjualan->pelanggan->nama ?? 'Umum' }}</div>
                    <div class="col-md-4"><strong>Tanggal Jual:</strong> {{ Carbon\Carbon::parse($penjualan->tanggal_penjualan)->isoFormat('D MMM YYYY, HH:mm') }}</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Item yang Akan Diretur</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                <div class="alert alert-danger pb-0">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tanggal_retur" class="form-label required-label">Tanggal Retur:</label>
                        <input type="datetime-local" class="form-control @error('tanggal_retur') is-invalid @enderror"
                               id="tanggal_retur" name="tanggal_retur"
                               value="{{ old('tanggal_retur', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('tanggal_retur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                @foreach ($detailItemsUntukForm as $index => $item)
                    <div class="card item-retur-card shadow-sm">
                        <div class="card-body">
                            <input type="hidden" name="items_retur[{{ $index }}][id_detail_penjualan]" value="{{ $item->id_detail_penjualan }}">
                            <h6 class="card-title">{{ $item->produk->nama }} ({{ $item->produk->kode_produk }})</h6>
                            <p class="card-text mb-1">
                                <small>Jumlah Beli: {{ $item->jumlah_beli_awal }} unit | Sisa Bisa Diretur: <span class="fw-bold text-success">{{ $item->sisa_qty_bisa_diretur_item }}</span> unit</small>
                            </p>

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="items_retur_{{ $index }}_jumlah_retur" class="form-label required-label">Jumlah Diretur:</label>
                                    <input type="number" class="form-control form-control-sm item-jumlah-retur @error('items_retur.'.$index.'.jumlah_retur') is-invalid @enderror"
                                           id="items_retur_{{ $index }}_jumlah_retur" name="items_retur[{{ $index }}][jumlah_retur]"
                                           value="{{ old('items_retur.'.$index.'.jumlah_retur', 0) }}" min="0" max="{{ $item->sisa_qty_bisa_diretur_item }}" required
                                           data-sisa-bisa-diretur="{{ $item->sisa_qty_bisa_diretur_item }}"
                                           data-memiliki-serial="{{ $item->produk->memiliki_serial ? 'true' : 'false' }}"
                                           data-index="{{ $index }}">
                                    @error('items_retur.'.$index.'.jumlah_retur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="items_retur_{{ $index }}_alasan_retur" class="form-label required-label">Alasan Retur:</label>
                                    <select class="form-select form-select-sm @error('items_retur.'.$index.'.alasan_retur') is-invalid @enderror"
                                            id="items_retur_{{ $index }}_alasan_retur" name="items_retur[{{ $index }}][alasan_retur]" required>
                                        <option value="">Pilih Alasan...</option>
                                        @foreach ($alasanReturOptions as $key => $value)
                                            <option value="{{ $key }}" {{ old('items_retur.'.$index.'.alasan_retur') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                    @error('items_retur.'.$index.'.alasan_retur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-5">
                                    <label for="items_retur_{{ $index }}_tindakan_lanjut" class="form-label required-label">Tindak Lanjut Barang Diretur:</label>
                                    <select class="form-select form-select-sm @error('items_retur.'.$index.'.tindakan_lanjut') is-invalid @enderror"
                                            id="items_retur_{{ $index }}_tindakan_lanjut" name="items_retur[{{ $index }}][tindakan_lanjut]" required>
                                        <option value="">Pilih Tindakan...</option>
                                        @foreach ($tindakanLanjutOptions as $key => $value)
                                            <option value="{{ $key }}" {{ old('items_retur.'.$index.'.tindakan_lanjut') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                    @error('items_retur.'.$index.'.tindakan_lanjut') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            @if ($item->produk->memiliki_serial)
                            <div class="mt-3 serial-selection-area" id="serial-selection-area-{{ $index }}" style="display:none;">
                                <label class="form-label">Pilih Nomor Seri yang Diretur (<span class="selected-serial-count">0</span> / <span class="max-serial-select">0</span>):</label>
                                <div class="serial-checkbox-list row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2">
                                    @foreach ($item->serials_yang_masih_bisa_diretur as $serial)
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input nomor-seri-retur-checkbox" type="checkbox"
                                                       name="items_retur[{{ $index }}][nomor_seri_diretur][]"
                                                       value="{{ $serial }}" id="serial_{{ $index }}_{{ str_replace(['/','.'], '-', $serial) }}"> {{-- ID unik untuk label --}}
                                                <label class="form-check-label" for="serial_{{ $index }}_{{ str_replace(['/','.'], '-', $serial) }}">
                                                    {{ $serial }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('items_retur.'.$index.'.nomor_seri_diretur') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                            </div>
                            @endif
                            <div class="mt-2">
                                <label for="items_retur_{{ $index }}_catatan_tambahan_item" class="form-label">Catatan Tambahan Item (Opsional):</label>
                                <textarea class="form-control form-control-sm" id="items_retur_{{ $index }}_catatan_tambahan_item" name="items_retur[{{ $index }}][catatan_tambahan_item]" rows="1">{{ old('items_retur.'.$index.'.catatan_tambahan_item') }}</textarea>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="mt-3">
                    <label for="catatan_global_retur" class="form-label">Catatan Global Retur (Opsional):</label>
                    <textarea class="form-control" id="catatan_global_retur" name="catatan_global_retur" rows="2">{{ old('catatan_global_retur') }}</textarea>
                </div>

            </div>
            <div class="card-footer text-end">
                <a href="{{ route('kasir.retur_penjualan.cari_transaksi') }}" class="btn btn-secondary">Kembali ke Pencarian Nota</a>
                <button type="submit" class="btn btn-danger" id="btn-simpan-retur">
                    <i class="bi bi-arrow-return-left me-1"></i> Proses Retur
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    {{-- jQuery, Bootstrap, Select2, SweetAlert2 sudah ada di layout  --}}
    <script>
    $(document).ready(function() {
        // Inisialisasi Select2 untuk dropdown (jika ada banyak opsi dan ingin fitur search)
        // $('.form-select').select2({ theme: "bootstrap-5", minimumResultsForSearch: Infinity }); // Contoh, sesuaikan

        // Fungsi untuk menangani tampilan area pemilihan serial
        function toggleSerialArea(jumlahInput) {
            const rowCard = $(jumlahInput).closest('.item-retur-card');
            const memilikiSerial = $(jumlahInput).data('memiliki-serial') === true || $(jumlahInput).data('memiliki-serial') === 'true';
            const jumlahRetur = parseInt($(jumlahInput).val()) || 0;
            const serialArea = rowCard.find('.serial-selection-area');
            const maxSerialSelectSpan = serialArea.find('.max-serial-select');

            if (memilikiSerial && jumlahRetur > 0) {
                serialArea.slideDown();
                maxSerialSelectSpan.text(jumlahRetur);
                // Reset pilihan serial jika jumlah retur berubah
                serialArea.find('.nomor-seri-retur-checkbox').prop('checked', false);
                updateSelectedSerialCount(serialArea);
            } else {
                serialArea.slideUp();
                serialArea.find('.nomor-seri-retur-checkbox').prop('checked', false); // Uncheck semua
                updateSelectedSerialCount(serialArea); // Update count jadi 0
            }
        }

        // Fungsi untuk update hitungan serial yang dipilih
        function updateSelectedSerialCount(serialArea) {
            const count = serialArea.find('.nomor-seri-retur-checkbox:checked').length;
            serialArea.find('.selected-serial-count').text(count);
        }

        // Event listener untuk input jumlah retur
        $('.item-jumlah-retur').on('input change', function() {
            const val = parseInt($(this).val()) || 0;
            const max = parseInt($(this).attr('max'));
            if (val > max) {
                $(this).val(max); // Batasi maksimal
                Swal.fire('Perhatian', 'Jumlah retur tidak boleh melebihi sisa yang bisa diretur.', 'warning');
            }
            if (val < 0) {
                $(this).val(0);
            }
            toggleSerialArea(this);
        });

        // Event listener untuk checkbox serial
        $('.serial-selection-area').on('change', '.nomor-seri-retur-checkbox', function() {
            const serialArea = $(this).closest('.serial-selection-area');
            const jumlahRetur = parseInt(serialArea.closest('.item-retur-card').find('.item-jumlah-retur').val()) || 0;
            const checkedCount = serialArea.find('.nomor-seri-retur-checkbox:checked').length;

            if (checkedCount > jumlahRetur) {
                $(this).prop('checked', false); // Batalkan check jika melebihi jumlah retur
                Swal.fire('Perhatian', 'Jumlah nomor seri yang dipilih tidak boleh melebihi jumlah item yang diretur.', 'warning');
            }
            updateSelectedSerialCount(serialArea);
        });

        // Panggil toggleSerialArea saat halaman dimuat untuk setiap item (jika ada old input)
        $('.item-jumlah-retur').each(function() {
            toggleSerialArea(this);
            updateSelectedSerialCount($(this).closest('.item-retur-card').find('.serial-selection-area'));
        });


        // Validasi form sebelum submit
        $('#form-retur-penjualan').on('submit', function(e) {
            let formIsValid = true;
            let errorMessages = [];
            let firstInvalidElement = null;

            // Validasi tanggal retur
            if (!$('#tanggal_retur').val()) {
                errorMessages.push('Tanggal retur wajib diisi.');
                formIsValid = false;
                if(!firstInvalidElement) firstInvalidElement = $('#tanggal_retur');
            }

            let totalItemDiretur = 0;
            $('.item-jumlah-retur').each(function(index) {
                const itemRowCard = $(this).closest('.item-retur-card');
                const jumlahRetur = parseInt($(this).val()) || 0;
                totalItemDiretur += jumlahRetur;

                if (jumlahRetur > 0) { // Hanya validasi detail jika item ini memang diretur
                    const alasanSelect = itemRowCard.find('select[name$="[alasan_retur]"]');
                    const tindakanSelect = itemRowCard.find('select[name$="[tindakan_lanjut]"]');

                    if (!alasanSelect.val()) {
                        errorMessages.push(`Alasan retur wajib dipilih untuk item "${itemRowCard.find('h6').text()}".`);
                        formIsValid = false;
                        if(!firstInvalidElement) firstInvalidElement = alasanSelect;
                    }
                    if (!tindakanSelect.val()) {
                        errorMessages.push(`Tindakan lanjut wajib dipilih untuk item "${itemRowCard.find('h6').text()}".`);
                        formIsValid = false;
                        if(!firstInvalidElement) firstInvalidElement = tindakanSelect;
                    }

                    const memilikiSerial = $(this).data('memiliki-serial') === true || $(this).data('memiliki-serial') === 'true';
                    if (memilikiSerial) {
                        const serialArea = itemRowCard.find('.serial-selection-area');
                        const checkedSerials = serialArea.find('.nomor-seri-retur-checkbox:checked').length;
                        if (checkedSerials !== jumlahRetur) {
                            errorMessages.push(`Jumlah nomor seri yang dipilih (${checkedSerials}) tidak sesuai dengan jumlah retur (${jumlahRetur}) untuk item "${itemRowCard.find('h6').text()}".`);
                            formIsValid = false;
                            if(!firstInvalidElement) firstInvalidElement = serialArea.find('.nomor-seri-retur-checkbox:first');
                        }
                    }
                }
            });

            if (totalItemDiretur === 0) {
                 errorMessages.push('Minimal ada satu item dengan jumlah retur lebih dari 0.');
                 formIsValid = false;
                 if(!firstInvalidElement) firstInvalidElement = $('.item-jumlah-retur:first');
            }


            if (!formIsValid) {
                e.preventDefault();
                let errorHtml = '<ul>';
                errorMessages.forEach(function(msg) { errorHtml += `<li>${msg}</li>`; });
                errorHtml += '</ul>';
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal!',
                    html: errorHtml,
                    confirmButtonText: 'OK Mengerti'
                }).then(() => {
                    if (firstInvalidElement && $(firstInvalidElement).is(':visible')) {
                         $('html, body').animate({
                             scrollTop: $(firstInvalidElement).offset().top - 150
                         }, 500, function() { $(firstInvalidElement).focus(); });
                     }
                });
            } else {
                 // Konfirmasi sebelum submit
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Proses Retur',
                    text: "Apakah Anda yakin ingin memproses retur ini? Data yang sudah disimpan tidak dapat diubah dengan mudah.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Proses Retur!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#btn-simpan-retur').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                        $(this).off('submit').submit(); // Hapus handler, lalu submit form asli
                    }
                });
            }
        });
    });
    </script>
@endpush