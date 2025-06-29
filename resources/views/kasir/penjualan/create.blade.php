@extends('layouts.app') 

@section('title', 'Buat Transaksi Penjualan Baru')

@push('styles')
  
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(1.5em + .75rem + 2px); /* Default BS5 input height */
            padding: .375rem .75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            border-radius: .25rem; /* Default BS5 border radius */
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .table-items th, .table-items td {
            vertical-align: middle;
        }
        .input-group-sm .form-control-plaintext {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
            min-height: calc(1.5em + 0.5rem + 2px);
        }
        .form-control-plaintext.total-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd; /* Bootstrap primary color */
        }
        .required-label::after { content: " *"; color: red; }
        .navbar { z-index: 1030 !important; }
        .navbar .dropdown-menu { z-index: 1031 !important; }
        .card.shadow-sm.sticky-top { z-index: 1020 !important; top: 5rem !important; } /* Tambah jarak dari navbar */

        .input-group .select2-container--bootstrap-5 {
            flex: 1 1 auto;
            width: 1% !important;
        }
        .input-group .select2-selection--single {
            height: 100% !important;
            min-height: calc(1.5em + .75rem + 2px);
            display: flex;
            align-items: center;
        }
        .input-group > .select2-container--bootstrap-5 .select2-selection {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .input-group > .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            /* height: 100%; -> dihapus agar tidak memaksa tinggi tombol */
            min-height: calc(1.5em + .75rem + 2px); /* Sesuaikan dengan tinggi input */

        }
        @media (max-width: 575.98px) {
            .input-group .select2-container--bootstrap-5,
            .input-group > .btn {
                min-width: 0;
                width: auto;
            }
        }
        .btn-pilih-batch-serial.btn-outline-danger,
        .btn-pilih-batch-serial.btn-outline-danger:hover,
        .btn-pilih-batch-serial.btn-outline-danger:focus {
            color: #dc3545;
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important; /* Shadow merah */
        }
        .btn-pilih-batch-serial.btn-outline-success,
        .btn-pilih-batch-serial.btn-outline-success:hover,
        .btn-pilih-batch-serial.btn-outline-success:focus {
            color: #198754;
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important; /* Shadow hijau */
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <form action="{{ route('kasir.penjualan.store') }}" method="POST" id="form-penjualan">
        @csrf
        <div class="row">
            {{-- Kolom Kiri - Detail Transaksi & Item --}}
            <div class="col-lg-8 col-md-7 mb-3">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-receipt-cutoff me-2"></i>Detail Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">No. Invoice:</label>
                                <input type="text" class="form-control form-control-sm" value="(Otomatis)" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Transaksi:</label>
                                <input type="text" class="form-control form-control-sm" value="{{ $tanggalSekarang->isoFormat('D MMMM YYYY, HH:mm') }}" readonly>
                                <input type="hidden" name="tanggal_penjualan" value="{{ $tanggalSekarang->toDateTimeString() }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kasir:</label>
                                <input type="text" class="form-control form-control-sm" value="{{ $namaKasir }}" readonly>
                                <input type="hidden" name="id_pengguna" value="{{ Auth::id() }}">
                            </div>
                        </div>
                        <hr>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="id_pelanggan" class="form-label">Pelanggan:</label>
                                <div class="input-group">
                                    <select class="form-select select2-pelanggan" id="id_pelanggan" name="id_pelanggan" data-placeholder="Cari atau Pilih Pelanggan (Opsional)">
                                        <option value=""></option>
                                    </select>
                                    <button class="btn btn-outline-success" type="button" id="btn-tambah-pelanggan-cepat" title="Tambah Pelanggan Baru">
                                        <i class="bi bi-person-plus-fill"></i>
                                    </button>
                                </div>
                                <span id="info-pelanggan-baru" class="text-success fw-bold mt-1" style="display: none;"></span>
                                <input type="hidden" name="pelanggan_baru_nama" id="pelanggan_baru_nama">
                                <input type="hidden" name="pelanggan_baru_telepon" id="pelanggan_baru_telepon">
                                <input type="hidden" name="pelanggan_baru_alamat" id="pelanggan_baru_alamat">
                            </div>
                             <div class="col-md-6">
                                <label for="kanal_transaksi" class="form-label required-label">Kanal Transaksi:</label>
                                <select class="form-select @error('kanal_transaksi') is-invalid @enderror" id="kanal_transaksi" name="kanal_transaksi" required>
                                    @foreach($kanalTransaksi as $value => $label)
                                        <option value="{{ $value }}" {{ old('kanal_transaksi', 'TOKO') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('kanal_transaksi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-cart3 me-2"></i>Item Penjualan</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-items table-striped table-hover mb-0" id="tabel-item-penjualan">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 35%;">Produk</th>
                                        <th style="width: 10%;" class="text-center">Qty</th>
                                        <th style="width: 20%;">Harga Jual Satuan</th>
                                        <th style="width: 20%;">Subtotal</th>
                                        <th style="width: 15%;" class="text-center">Batch/Serial</th>
                                        <th style="width: 5%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Baris item akan ditambahkan oleh JavaScript --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <button type="button" class="btn btn-success btn-sm" id="btn-tambah-item">
                            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Item Produk
                        </button>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan - Pembayaran & Aksi --}}
            <div class="col-lg-4 col-md-5">
                <div class="card shadow-sm sticky-top">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Total Belanja:</label>
                            <input type="text" class="form-control-plaintext total-display text-end" id="display_total_belanja" value="Rp 0" readonly>
                            <input type="hidden" name="total_harga" id="total_harga" value="0">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="tipe_transaksi" class="form-label required-label">Tipe Transaksi:</label>
                                <select class="form-select @error('tipe_transaksi') is-invalid @enderror" id="tipe_transaksi" name="tipe_transaksi" required>
                                    @foreach($tipeTransaksi as $value => $label)
                                        <option value="{{ $value }}" {{ old('tipe_transaksi', 'BIASA') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('tipe_transaksi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div id="area-pesan-barang" style="display:none;">
                            <div class="mb-3">
                                <label for="uang_muka" class="form-label">Uang Muka (DP):</label>
                                <input type="text" class="form-control input-rupiah @error('uang_muka') is-invalid @enderror" id="uang_muka" name="uang_muka" data-inputmask-alias="numeric">
                                @error('uang_muka') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="estimasi_kirim_at" class="form-label">Estimasi Barang Tiba:</label>
                                <input type="date" class="form-control @error('estimasi_kirim_at') is-invalid @enderror" id="estimasi_kirim_at" name="estimasi_kirim_at" min="{{ Carbon\Carbon::today()->toDateString() }}">
                                @error('estimasi_kirim_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                             <div class="mb-3">
                                <label class="form-label fw-bold">Sisa Pembayaran Pesanan:</label>
                                <input type="text" class="form-control-plaintext total-display text-end text-danger" id="display_sisa_pembayaran_po" value="Rp 0" readonly>
                                <input type="hidden" name="sisa_pembayaran_po_hidden" id="sisa_pembayaran_po_hidden" value="0"> {{-- Hidden input untuk sisa PO --}}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="metode_pembayaran" class="form-label required-label">Metode Pembayaran:</label>
                            <select class="form-select @error('metode_pembayaran') is-invalid @enderror" id="metode_pembayaran" name="metode_pembayaran" required>
                                @foreach($metodePembayaran as $value => $label)
                                    <option value="{{ $value }}" {{ old('metode_pembayaran', 'TUNAI') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('metode_pembayaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3" id="area-uang-bayar">
                            <label for="uang_bayar" class="form-label required-label">Uang Bayar:</label>
                            <input type="text" class="form-control input-rupiah @error('uang_bayar') is-invalid @enderror" id="uang_bayar" name="uang_bayar" data-inputmask-alias="numeric" required>
                             @error('uang_bayar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Kembalian:</label>
                            <input type="text" class="form-control-plaintext total-display text-end text-success" id="display_kembalian" value="Rp 0" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="catatan_penjualan" class="form-label">Catatan Transaksi (Opsional):</label>
                            <textarea class="form-control" id="catatan_penjualan" name="catatan" rows="2"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-lg btn-primary" id="btn-simpan-penjualan">
                                <i class="bi bi-save-fill me-2"></i> Simpan & Cetak Nota
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Modal Tambah Pelanggan Cepat --}}
<div class="modal fade" id="modalTambahPelangganCepat" tabindex="-1" aria-labelledby="modalTambahPelangganCepatLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahPelangganCepatLabel"><i class="bi bi-person-plus-fill me-2"></i>Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modal_pelanggan_nama" class="form-label required-label">Nama Pelanggan:</label>
                    <input type="text" class="form-control" id="modal_pelanggan_nama" required>
                    <div class="invalid-feedback" id="modal_nama_error"></div>
                </div>
                <div class="mb-3">
                    <label for="modal_pelanggan_telepon" class="form-label">No. Telepon (Opsional):</label>
                    <input type="text" class="form-control" id="modal_pelanggan_telepon">
                </div>
                <div class="mb-3">
                    <label for="modal_pelanggan_alamat" class="form-label">Alamat (Opsional):</label>
                    <textarea class="form-control" id="modal_pelanggan_alamat" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-pelanggan-cepat"><i class="bi bi-check-circle me-1"></i> Simpan Pelanggan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Pilih Batch/Serial --}}
<div class="modal fade" id="modalPilihBatchSerial" tabindex="-1" aria-labelledby="modalPilihBatchSerialLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPilihBatchSerialLabel">Pilih Batch dan Nomor Seri untuk: <span id="nama-produk-modal-batch"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_batch_item_row_id">
                <input type="hidden" id="modal_batch_id_produk">
                <input type="hidden" id="modal_batch_qty_dibutuhkan_total">
                <div class="alert alert-info">
                    <p class="mb-1">Anda membutuhkan <strong id="qty-dibutuhkan-info-modal">X</strong> unit untuk produk: <strong id="nama-produk-modal-alert">Nama Produk</strong>.</p>
                    <p class="mb-0" id="info-total-stok-modal">Total stok tersedia: Y unit.</p>
                </div>
                <div id="batch-allocation-details">
                    {{-- Konten dinamis untuk pilihan batch --}}
                </div>
                {{-- Area pemilihan serial tidak lagi di sini, tapi di dalam batch-allocation-details per batch --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-pilihan-batch">
                    <i class="bi bi-check-circle me-1"></i> Terapkan Pilihan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        // Fungsi helper global
        function formatRupiah(angka, prefix = 'Rp ') {
            if (isNaN(angka) || angka === null || angka === undefined) return prefix + '0';
            let number_string = Math.round(angka).toString().replace(/[^,\d]/g, ''), // Bulatkan dulu
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);
            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
            return prefix + rupiah;
        }

        function parseRupiah(rupiahString) {
            if (typeof rupiahString !== 'string') return 0;
            return parseInt(rupiahString.replace(/[^0-9]/g, ''), 10) || 0;
        }

        let itemRowCounter = 0;
        let currentProdukInfoForModal = {};
        let currentQtyDibutuhkanTotalModal = 0;
        let currentRowIdForBatchModal = null;

        $(document).ready(function() {
            // Inisialisasi Inputmask untuk Rupiah (global untuk yang sudah ada di HTML)
            $('.input-rupiah').inputmask({
                alias: 'numeric', groupSeparator: '.', radixPoint: ',', digits: 0, autoGroup: true,
                prefix: 'Rp ', rightAlign: false, removeMaskOnSubmit: true,
                oncleared: function () { $(this).val(''); }
            });

            // Inisialisasi Select2 untuk Pelanggan
            $('#id_pelanggan.select2-pelanggan').select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: $('#id_pelanggan.select2-pelanggan').data('placeholder'),
                allowClear: true,
                ajax: {
                    url: "{{ route('kasir.ajax.pelanggan.search') }}",
                    dataType: 'json', delay: 250,
                    data: function(params) { return { q: params.term, page: params.page || 1 }; },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.items,
                            pagination: { more: (params.page * 15) < data.total_count }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            }).on('select2:select', function (e) {
                $('#pelanggan_baru_nama, #pelanggan_baru_telepon, #pelanggan_baru_alamat').val('');
                $('#info-pelanggan-baru').hide().text('');
            }).on('select2:clear', function (e) {
                $('#pelanggan_baru_nama, #pelanggan_baru_telepon, #pelanggan_baru_alamat').val('');
                $('#info-pelanggan-baru').hide().text('');
            });

            // Tombol Tambah Pelanggan Cepat
            $('#btn-tambah-pelanggan-cepat').on('click', function() {
                $('#modal_pelanggan_nama').val('').removeClass('is-invalid');
                $('#modal_pelanggan_telepon, #modal_pelanggan_alamat').val('');
                $('#modal_nama_error').text('');
                $('#modalTambahPelangganCepat').modal('show');
            });

            $('#btn-simpan-pelanggan-cepat').on('click', function() {
                const nama = $('#modal_pelanggan_nama').val().trim();
                if (!nama) {
                    $('#modal_pelanggan_nama').addClass('is-invalid');
                    $('#modal_nama_error').text('Nama pelanggan wajib diisi.');
                    return;
                }
                $('#modal_pelanggan_nama').removeClass('is-invalid');
                $('#modal_nama_error').text('');
                $('#pelanggan_baru_nama').val(nama);
                $('#pelanggan_baru_telepon').val($('#modal_pelanggan_telepon').val().trim());
                $('#pelanggan_baru_alamat').val($('#modal_pelanggan_alamat').val().trim());
                $('#id_pelanggan.select2-pelanggan').val(null).trigger('change');
                $('#info-pelanggan-baru').text('Pelanggan Baru: ' + nama).show();
                $('#modalTambahPelangganCepat').modal('hide');
                Swal.fire({ icon: 'success', title: 'Pelanggan Baru Siap', text: 'Data akan disimpan saat transaksi selesai.', timer: 2000, showConfirmButton: false });
            });

            // Fungsi tambah item produk
            function tambahItemProduk(produkData = null) {
                itemRowCounter++;
                const rowId = itemRowCounter;
                const currentTipeTransaksi = $('#tipe_transaksi').val();
                const isPesanBarang = currentTipeTransaksi === 'PESAN_BARANG';

                // --- PENTING: Penanganan input stok_allocations ---
                let stokAllocationsInputHtml = '';
                if (!isPesanBarang) {
                    // Hanya tambahkan input ini jika bukan PESAN_BARANG
                    // Value awal adalah string JSON array kosong
                    stokAllocationsInputHtml = `<input type="hidden" class="stok-allocations-json" name="items[${rowId}][stok_allocations]" value='[]'>`;
                }

                const newRowHtml = `
                    <tr class="item-penjualan-row" data-row-id="${rowId}">
                        <td>
                            <select class="form-select form-select-sm select2-produk-item" name="items[${rowId}][id_produk]" data-placeholder="Cari Produk..." required></select>
                            <small class="text-muted d-block">Harga Std: <span class="harga-standar-info">-</span></small>
                            <div class="invalid-feedback product-error-feedback"></div>
                        </td>
                        <td><input type="number" name="items[${rowId}][jumlah]" class="form-control form-control-sm item-jumlah text-center" value="1" min="1" required></td>
                        <td><input type="text" name="items[${rowId}][harga_jual]" class="form-control form-control-sm item-harga-jual text-end input-rupiah-item" required></td>
                        <td class="text-end item-subtotal fw-bold">Rp 0</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-pilih-batch-serial" title="Pilih Batch/Serial" ${isPesanBarang ? 'disabled' : ''}>
                                <i class="bi bi-box-seam"></i> <span class="selected-batch-info">${isPesanBarang ? 'Dialokasikan Nanti' : 'Pilih'}</span>
                            </button>
                            ${stokAllocationsInputHtml} {{-- Input stok_allocations ditambahkan di sini --}}
                            <small class="text-muted d-block mt-1 serial-info-display"></small>
                        </td>
                        <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-hapus-item" title="Hapus Item"><i class="bi bi-trash"></i></button></td>
                    </tr>
                `;
                $('#tabel-item-penjualan tbody').append(newRowHtml);
                const newRow = $(`#tabel-item-penjualan tbody tr[data-row-id="${rowId}"]`);

                newRow.find('.input-rupiah-item').inputmask({
                    alias: 'numeric', groupSeparator: '.', radixPoint: ',', digits: 0, autoGroup: true,
                    prefix: 'Rp ', rightAlign: false, removeMaskOnSubmit: true,
                    oncleared: function () { $(this).val(''); }
                });

                newRow.find('.select2-produk-item').select2({
                    theme: "bootstrap-5", width: '100%',
                    placeholder: "Cari Produk...",
                    ajax: {
                        url: "{{ route('kasir.ajax.produk.search') }}",
                        dataType: 'json', delay: 250,
                        data: function(params) { return { q: params.term, page: params.page || 1, for_sale: true }; },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.items.map(item => ({
                                    id: item.id, text: item.text,
                                    harga_jual_standar: item.harga_jual_standar,
                                    memiliki_serial: item.memiliki_serial,
                                    durasi_garansi_standar_bulan: item.durasi_garansi_standar_bulan
                                })),
                                pagination: { more: (params.page * 15) < data.total_count }
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1
                }).on('select2:select', function(e) {
                    const data = e.params.data;
                    const currentRow = $(this).closest('.item-penjualan-row');
                    let harga = parseFloat(data.harga_jual_standar) || 0;
                    currentRow.find('.harga-standar-info').text(formatRupiah(harga));
                    currentRow.find('.item-harga-jual').val(Math.round(harga)).trigger('input'); // Harga jual di-bulatkan
                    
                    const currentTipe = $('#tipe_transaksi').val();
                    if (currentTipe !== 'PESAN_BARANG') {
                         currentRow.find('.btn-pilih-batch-serial').prop('disabled', false);
                         currentRow.find('.selected-batch-info').text('Pilih');
                    } else {
                         currentRow.find('.btn-pilih-batch-serial').prop('disabled', true);
                         currentRow.find('.selected-batch-info').text('Dialokasikan Nanti');
                    }
                    currentRow.find('.btn-pilih-batch-serial').removeClass('btn-outline-success btn-outline-danger').addClass('btn-outline-secondary'); // Reset warna tombol

                    currentRow.find('.stok-produk-info').text('?');
                    if(data.memiliki_serial){
                        currentRow.find('.serial-info-display').text('Wajib Serial').addClass('text-danger fw-bold');
                    } else {
                        currentRow.find('.serial-info-display').text('').removeClass('text-danger fw-bold');
                    }
                    currentRow.data('produk-info', data); // Simpan info produk di baris
                    hitungSubtotal(currentRow);
                    currentRow.find('.item-jumlah').focus().select();
                }).on('select2:unselect', function (e) {
                    const currentRow = $(this).closest('.item-penjualan-row');
                    currentRow.find('.harga-standar-info, .stok-produk-info, .serial-info-display').text('-');
                    currentRow.find('.item-harga-jual').val('Rp 0').trigger('input');
                    currentRow.find('.btn-pilih-batch-serial').prop('disabled', true).removeClass('btn-outline-success btn-outline-danger').addClass('btn-outline-secondary');
                    currentRow.find('.selected-batch-info').text('Pilih');
                    // Saat unselect, HAPUS input stok_allocations
                    currentRow.find('input.stok-allocations-json').remove();
                    currentRow.removeData('produk-info');
                    hitungSubtotal(currentRow);
                });

                if(produkData){ // Jika dari scan barcode
                    var option = new Option(produkData.text, produkData.id, true, true);
                    newRow.find('.select2-produk-item').append(option).trigger('change');
                    newRow.find('.select2-produk-item').trigger({ type: 'select2:select', params: { data: produkData } });
                } else {
                    newRow.find('.select2-produk-item').select2('open');
                }
            }

            $('#btn-tambah-item').on('click', function() { tambahItemProduk(); });

            function hitungSubtotal(row) {
                const jumlah = parseInt(row.find('.item-jumlah').val()) || 0;
                const hargaJual = parseRupiah(row.find('.item-harga-jual').val());
                const subtotal = jumlah * hargaJual;
                row.find('.item-subtotal').text(formatRupiah(subtotal));
                hitungTotalBelanja();
            }

            $('#tabel-item-penjualan').on('input change', '.item-jumlah, .item-harga-jual', function() {
                const row = $(this).closest('.item-penjualan-row');
                hitungSubtotal(row);
            });

            function hitungTotalBelanja() {
                let totalBelanja = 0;
                $('.item-penjualan-row').each(function() { totalBelanja += parseRupiah($(this).find('.item-subtotal').text()); });
                $('#display_total_belanja').val(formatRupiah(totalBelanja));
                $('#total_harga').val(totalBelanja);
                hitungKembalian();
                hitungSisaPembayaranPO();
            }

            $('#tabel-item-penjualan').on('click', '.btn-hapus-item', function() {
                $(this).closest('.item-penjualan-row').remove();
                hitungTotalBelanja();
                if ($('#tabel-item-penjualan tbody tr').length === 0) { // Tambah item baru jika semua dihapus
                    tambahItemProduk();
                }
            });

            $('#tipe_transaksi').on('change', function() {
                const tipe = $(this).val();
                const isPesanBarang = tipe === 'PESAN_BARANG';

                $('#area-pesan-barang').toggle(isPesanBarang);
                $('#uang_muka').prop('required', isPesanBarang);
                // $('#estimasi_kirim_at').prop('required', isPesanBarang); // Opsional

                $('#area-uang-bayar label[for="uang_bayar"]').text(isPesanBarang ? 'Uang Bayar (Pelunasan):' : 'Uang Bayar:');
                $('#uang_bayar').prop('required', !isPesanBarang); // Wajib jika bukan Pesan Barang

                $('.item-penjualan-row').each(function() {
                    const row = $(this);
                    const produkSelect = row.find('.select2-produk-item');
                    row.find('.btn-pilih-batch-serial').prop('disabled', isPesanBarang || !produkSelect.val());
                    row.find('.selected-batch-info').text(isPesanBarang ? 'Dialokasikan Nanti' : (produkSelect.val() ? 'Pilih' : '-'));
                    row.find('.btn-pilih-batch-serial').removeClass('btn-outline-success btn-outline-danger').addClass('btn-outline-secondary');

                    // Hapus atau tambahkan input stok_allocations
                    const allocationsInput = row.find('input.stok-allocations-json');
                    if (isPesanBarang) {
                        allocationsInput.remove(); // Hapus jika PESAN_BARANG
                    } else {
                        if (allocationsInput.length === 0) { // Tambahkan jika BIASA dan belum ada
                            let hiddenInputName = produkSelect.attr('name').replace('[id_produk]', '[stok_allocations]');
                            if(hiddenInputName){ // pastikan nama ada
                                row.find('.btn-pilih-batch-serial').parent().append(
                                    `<input type="hidden" class="stok-allocations-json" name="${hiddenInputName}" value='[]'>`
                                );
                            }
                        }
                    }
                    row.find('.serial-info-display').text(isPesanBarang ? '' : (row.data('produk-info')?.memiliki_serial ? 'Wajib Serial' : '')).toggleClass('text-danger fw-bold', !isPesanBarang && row.data('produk-info')?.memiliki_serial);
                });

                if (!isPesanBarang) $('#uang_muka, #estimasi_kirim_at').val('');
                hitungSisaPembayaranPO();
                hitungKembalian();
            }).trigger('change'); // Trigger saat halaman dimuat

            $('#uang_muka').on('input', hitungSisaPembayaranPO);
            $('#uang_bayar').on('input', hitungKembalian);

            function hitungSisaPembayaranPO() {
                let sisa = 0;
                if ($('#tipe_transaksi').val() === 'PESAN_BARANG') {
                    const totalBelanja = parseRupiah($('#display_total_belanja').val());
                    const uangMuka = parseRupiah($('#uang_muka').val());
                    sisa = Math.max(0, totalBelanja - uangMuka);
                }
                $('#display_sisa_pembayaran_po').val(formatRupiah(sisa));
                $('#sisa_pembayaran_po_hidden').val(sisa);
            }

            function hitungKembalian() {
                const totalBelanja = parseRupiah($('#display_total_belanja').val());
                const uangBayar = parseRupiah($('#uang_bayar').val());
                let kembalian = 0;

                if ($('#tipe_transaksi').val() === 'PESAN_BARANG') {
                    const uangMuka = parseRupiah($('#uang_muka').val());
                    const sisaPembayaranPO = Math.max(0, totalBelanja - uangMuka);
                    // Jika ada uang bayar (untuk pelunasan) dan masih ada sisa PO
                    if (uangBayar > 0 && sisaPembayaranPO > 0) {
                        kembalian = uangBayar - sisaPembayaranPO;
                    } else if (uangBayar > 0 && sisaPembayaranPO === 0 && uangMuka === totalBelanja){
                        // Jika DP sudah lunas penuh, dan ada uang bayar (seharusnya tidak terjadi jika uang muka pas)
                        // Ini bisa jadi skenario dimana user bayar lebih dari sisa, kembalian dihitung dari uang bayar
                        kembalian = uangBayar;
                    }
                } else { // Transaksi BIASA
                    kembalian = uangBayar - totalBelanja;
                }
                $('#display_kembalian').val(formatRupiah(Math.max(0, kembalian)));
            }

            // --- Logika Modal Pilih Batch/Serial ---
            $('#tabel-item-penjualan').on('click', '.btn-pilih-batch-serial', function() {
                const row = $(this).closest('.item-penjualan-row');
                currentRowIdForBatchModal = row.data('row-id');
                const produkSelect = row.find('.select2-produk-item');
                const idProduk = produkSelect.val();
                currentProdukInfoForModal = row.data('produk-info') || {}; // Ambil dari data baris
                const namaProduk = currentProdukInfoForModal.text || 'Tidak Diketahui';
                currentQtyDibutuhkanTotalModal = parseInt(row.find('.item-jumlah').val()) || 0;

                if (!idProduk || currentQtyDibutuhkanTotalModal <= 0) {
                    Swal.fire('Oops!', 'Pilih produk dan masukkan jumlah yang valid terlebih dahulu.', 'warning');
                    return;
                }

                $('#modal_batch_item_row_id').val(currentRowIdForBatchModal);
                $('#modal_batch_id_produk').val(idProduk);
                $('#modal_batch_qty_dibutuhkan_total').val(currentQtyDibutuhkanTotalModal);
                $('#nama-produk-modal-batch, #nama-produk-modal-alert').text(namaProduk);
                $('#qty-dibutuhkan-info-modal').text(currentQtyDibutuhkanTotalModal);
                $('#batch-allocation-details').html('<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat batch...</p></div>');
                $('#btn-simpan-pilihan-batch').prop('disabled', true);

                $.ajax({
                    url: '{{ route("kasir.ajax.stok.available") }}', type: 'GET',
                    data: { id_produk: idProduk, qty_dibutuhkan: currentQtyDibutuhkanTotalModal },
                    success: function(response) {
                        if (response.success) {
                            $('#info-total-stok-modal').text(`Total stok tersedia: ${response.total_stok_tersedia} unit.`);
                            currentProdukInfoForModal.memiliki_serial = response.memiliki_serial; // Update info serial
                            currentProdukInfoForModal.durasi_garansi_standar_bulan_produk = response.durasi_garansi_standar_bulan_produk;

                            if (!response.batches_data || response.batches_data.length === 0) {
                                $('#batch-allocation-details').html('<p class="text-danger text-center">Tidak ada batch stok yang tersedia (atau semua sudah teralokasi/tidak di lokasi TOKO).</p>');
                                return;
                            }
                            displayAvailableBatchesForSelection(response.batches_data, currentQtyDibutuhkanTotalModal, response.memiliki_serial);
                        } else {
                            $('#batch-allocation-details').html(`<p class="text-danger text-center">${response.message || 'Gagal memuat data batch.'}</p>`);
                        }
                    },
                    error: function(jqXHR) {
                        $('#batch-allocation-details').html(`<p class="text-danger text-center">Error: Gagal mengambil data batch. ${jqXHR.responseJSON?.message || jqXHR.statusText}</p>`);
                    }
                });
                $('#modalPilihBatchSerial').modal('show');
            });

            function displayAvailableBatchesForSelection(batchesData, qtyDibutuhkanTotal, produkMemilikiSerial) {
                let batchListHtml = `<h5 class="mb-2">Pilih Kuantitas dari Batch Tersedia (FIFO):</h5>
                                     <p class="mb-1">Total dibutuhkan: <strong id="modal-qty-dibutuhkan-display">${qtyDibutuhkanTotal}</strong> unit.</p>
                                     <p class="mb-3">Total dipilih: <strong id="modal-qty-terpilih-display" class="text-primary">0</strong> unit.</p>
                                     <div class="list-group mb-3">`;
                const totalAvailableStockFromBatches = batchesData.reduce((sum, batch) => sum + batch.jumlah_tersedia, 0);

                if (totalAvailableStockFromBatches < qtyDibutuhkanTotal && batchesData.length > 0) {
                    batchListHtml += `<div class="alert alert-warning mb-3">Stok keseluruhan (${totalAvailableStockFromBatches}) kurang dari kebutuhan (${qtyDibutuhkanTotal}).</div>`;
                }

                let remainingQtyToAllocate = qtyDibutuhkanTotal;
                batchesData.forEach((batch, index) => {
                    let qtyAllocatedForThisBatch = 0;
                    if (remainingQtyToAllocate > 0) {
                        qtyAllocatedForThisBatch = Math.min(batch.jumlah_tersedia, remainingQtyToAllocate);
                        remainingQtyToAllocate -= qtyAllocatedForThisBatch;
                    }
                    const showSerialContainerInitially = produkMemilikiSerial && qtyAllocatedForThisBatch > 0;
                    batchListHtml += `
                        <div class="list-group-item batch-selection-item p-2 ${qtyAllocatedForThisBatch > 0 ? 'border-start border-4 border-primary' : ''}"
                             data-id-stok-barang="${batch.id}" data-max-stok-batch="${batch.jumlah_tersedia}"
                             data-tipe-garansi-batch="${batch.tipe_garansi}">
                            <div class="row align-items-center g-2">
                                <div class="col-md-6">
                                    <strong class="d-block">Batch ID: ${batch.id} ${qtyAllocatedForThisBatch > 0 ? '<span class="badge bg-primary ms-1">FIFO Rec.</span>' : ''}</strong>
                                    <small class="d-block">Terima: ${batch.diterima_at_formatted} | Sisa: ${batch.jumlah_tersedia}</small>
                                    <small class="d-block">Lok: ${batch.lokasi} | Gar: ${batch.tipe_garansi_display} | Stok: ${batch.tipe_stok_display}</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Qty:</span>
                                        <input type="number" class="form-control qty-from-batch-input text-center" value="${qtyAllocatedForThisBatch}"
                                               min="0" max="${batch.jumlah_tersedia}" data-id-stok-barang="${batch.id}">
                                    </div>
                                    <div class="invalid-feedback qty-error-feedback d-block text-end"></div>
                                </div>
                            </div>
                            ${produkMemilikiSerial ? `<div class="mt-2 serial-container-for-batch" id="serials-for-batch-${batch.id}" style="display:${showSerialContainerInitially ? 'block':'none'};"></div>` : ''}
                        </div>`;
                });
                batchListHtml += '</div>';
                $('#batch-allocation-details').html(batchListHtml);
                attachQtyInputBatchEvents(produkMemilikiSerial, totalAvailableStockFromBatches);
                updateTotalQtyTerpilihDisplay();

                if (produkMemilikiSerial) {
                    batchesData.forEach(batch => {
                        const qtyInput = $(`.qty-from-batch-input[data-id-stok-barang="${batch.id}"]`);
                        if (parseInt(qtyInput.val()) > 0) {
                            loadNomorSeriUntukAlokasiBatch(batch.id, parseInt(qtyInput.val()));
                        }
                    });
                }
            }

            function attachQtyInputBatchEvents(produkMemilikiSerial, totalAvailableStockInAllBatches) {
                $('.qty-from-batch-input').off('input change').on('input change', function() {
                    let val = parseInt($(this).val()) || 0;
                    const maxInThisBatch = parseInt($(this).attr('max'));
                    const idStokBarang = $(this).data('id-stok-barang');
                    const serialContainer = $(`#serials-for-batch-${idStokBarang}`);
                    const errorFeedback = $(this).parent().siblings('.qty-error-feedback');

                    $(this).removeClass('is-invalid'); errorFeedback.text('');
                    if (val < 0) val = 0;
                    if (val > maxInThisBatch) {
                        val = maxInThisBatch;
                        $(this).val(val).addClass('is-invalid');
                        errorFeedback.text(`Maks. ${maxInThisBatch} unit.`);
                    }

                    // Validasi total qty yang dipilih tidak melebihi total stok tersedia dari semua batch
                    let totalQtyCurrentlySelected = 0;
                    $('.qty-from-batch-input').each(function() { totalQtyCurrentlySelected += parseInt($(this).val()) || 0; });
                    
                    // Perbaikan: Validasi agar total yang dipilih tidak melebihi total kebutuhan awal jika total stok mencukupi
                    // atau tidak melebihi total stok tersedia jika stok kurang
                    const maxAllowedToSelect = Math.min(currentQtyDibutuhkanTotalModal, totalAvailableStockInAllBatches);

                    if (totalQtyCurrentlySelected > maxAllowedToSelect) {
                        // Kurangi nilai input ini agar total kembali ke maxAllowedToSelect
                        const diff = totalQtyCurrentlySelected - maxAllowedToSelect;
                        val = Math.max(0, val - diff);
                        $(this).val(val);
                        $(this).addClass('is-invalid');
                        errorFeedback.text(`Total pilihan melebihi kebutuhan/stok (${maxAllowedToSelect}).`);
                        // Recalculate totalQtyCurrentlySelected
                        totalQtyCurrentlySelected = 0;
                        $('.qty-from-batch-input').each(function() { totalQtyCurrentlySelected += parseInt($(this).val()) || 0; });
                    }
                    
                    updateTotalQtyTerpilihDisplay(totalQtyCurrentlySelected); // Kirim total terpilih

                    if (produkMemilikiSerial) {
                        if (val > 0) {
                            serialContainer.show();
                            loadNomorSeriUntukAlokasiBatch(idStokBarang, val);
                        } else {
                            serialContainer.hide().empty();
                            checkOverallModalValidity();
                        }
                    } else {
                        checkOverallModalValidity();
                    }
                });
            }

            function updateTotalQtyTerpilihDisplay(currentTotalSelected = null) {
                let totalQtyTerpilih;
                if (currentTotalSelected !== null) {
                    totalQtyTerpilih = currentTotalSelected;
                } else {
                    totalQtyTerpilih = 0;
                    $('.qty-from-batch-input').each(function() { totalQtyTerpilih += parseInt($(this).val()) || 0; });
                }
                
                $('#modal-qty-terpilih-display').text(totalQtyTerpilih);
                const qtyDibutuhkan = currentQtyDibutuhkanTotalModal;

                if (totalQtyTerpilih === qtyDibutuhkan) {
                    $('#modal-qty-terpilih-display').removeClass('text-danger text-primary').addClass('text-success');
                } else if (totalQtyTerpilih > qtyDibutuhkan) {
                    $('#modal-qty-terpilih-display').removeClass('text-success text-primary').addClass('text-danger');
                } else {
                    $('#modal-qty-terpilih-display').removeClass('text-success text-danger').addClass('text-primary');
                }
                checkOverallModalValidity();
            }

            function loadNomorSeriUntukAlokasiBatch(idStokBarang, qtyDibutuhkanDariBatchIni) {
                const serialContainerTarget = $(`#serials-for-batch-${idStokBarang}`);
                serialContainerTarget.html('<div class="text-center p-1"><small><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat serial...</small></div>');
                $.ajax({
                    url: '{{ route("kasir.ajax.stok.serials") }}', type: 'GET',
                    data: { id_stok_barang: idStokBarang },
                    success: function(response) {
                        serialContainerTarget.empty(); // Bersihkan spinner
                        if (response.success && response.serials && response.serials.length > 0) {
                            if (response.serials.length < qtyDibutuhkanDariBatchIni) {
                                serialContainerTarget.append(`<p class="text-danger mb-1"><small>Stok serial (${response.serials.length}) kurang dari kebutuhan (${qtyDibutuhkanDariBatchIni}) untuk Batch ID ${idStokBarang}.</small></p>`);
                            }
                            let serialHtml = `<p class="mb-1"><small>Pilih ${qtyDibutuhkanDariBatchIni} serial untuk Batch ID: ${idStokBarang}</small></p>
                                              <div class="row row-cols-1 row-cols-sm-2 g-1 serial-checkbox-group">`;
                            response.serials.slice(0, Math.max(qtyDibutuhkanDariBatchIni, response.serials.length)).forEach(function(serial, index) { // Tampilkan semua serial yang ada, auto check N pertama
                                const isChecked = index < qtyDibutuhkanDariBatchIni;
                                serialHtml += `
                                    <div class="col">
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input nomor-seri-checkbox-multi" type="checkbox" value="${serial}"
                                                   id="serial-batch-${idStokBarang}-${index.toString().replace(/\s+/g, '-')}" data-id-stok-barang="${idStokBarang}" ${isChecked ? 'checked' : ''}>
                                            <label class="form-check-label" for="serial-batch-${idStokBarang}-${index.toString().replace(/\s+/g, '-')}"><small>${serial}</small></label>
                                        </div>
                                    </div>`;
                            });
                            serialHtml += '</div>';
                            serialContainerTarget.append(serialHtml);
                            attachMultiBatchSerialCheckboxEvents(qtyDibutuhkanDariBatchIni);
                        } else {
                            serialContainerTarget.append(`<p class="text-danger mb-1"><small>${response.message || `Tidak ada serial tersedia untuk Batch ID: ${idStokBarang}.`}</small></p>`);
                        }
                        checkOverallModalValidity();
                    },
                    error: function() {
                        serialContainerTarget.html(`<p class="text-danger mb-1"><small>Error AJAX memuat serial untuk Batch ID: ${idStokBarang}.</small></p>`);
                        checkOverallModalValidity();
                    }
                });
            }

            // --- FIX: Event delegation & validasi serial lebih aman ---
            function attachMultiBatchSerialCheckboxEvents() {
                $('#batch-allocation-details').off('change.serial').on('change.serial', '.nomor-seri-checkbox-multi', function() {
                    const idStokBarang = $(this).data('id-stok-barang');
                    const qtyNeededForThisBatch = parseInt($(`.qty-from-batch-input[data-id-stok-barang="${idStokBarang}"]`).val()) || 0;
                    const checkedInThisGroup = $(`.nomor-seri-checkbox-multi[data-id-stok-barang="${idStokBarang}"]:checked`).length;
                    if (checkedInThisGroup > qtyNeededForThisBatch) {
                        $(this).prop('checked', false);
                        Swal.fire('Perhatian', `Anda hanya boleh memilih ${qtyNeededForThisBatch} serial untuk batch ini.`, 'warning');
                    }
                    checkOverallModalValidity();
                });
            }

            function checkOverallModalValidity() {
                let totalQtyTerpilih = 0;
                $('.qty-from-batch-input').each(function() { totalQtyTerpilih += parseInt($(this).val()) || 0; });
                if (totalQtyTerpilih !== currentQtyDibutuhkanTotalModal) {
                    $('#btn-simpan-pilihan-batch').prop('disabled', true);
                    return;
                }
                let allSerialsAreValid = true;
                if (currentProdukInfoForModal.memiliki_serial) {
                    $('.batch-selection-item').each(function() {
                        const qtyDariBatchIni = parseInt($(this).find('.qty-from-batch-input').val()) || 0;
                        if (qtyDariBatchIni > 0) {
                            const idStokBarang = $(this).data('id-stok-barang');
                            const serialsCheckedCount = $(`.nomor-seri-checkbox-multi[data-id-stok-barang="${idStokBarang}"]:checked`).length;
                            if (serialsCheckedCount !== qtyDariBatchIni) {
                                allSerialsAreValid = false;
                                return false;
                            }
                        }
                    });
                }
                $('#btn-simpan-pilihan-batch').prop('disabled', !allSerialsAreValid);
            }

            $('#btn-simpan-pilihan-batch').off('click').on('click', function() {
                if (!currentRowIdForBatchModal) return;
                let totalQtyTerpilih = 0;
                $('.qty-from-batch-input').each(function() { totalQtyTerpilih += parseInt($(this).val()) || 0; });
                if (totalQtyTerpilih !== currentQtyDibutuhkanTotalModal) {
                    Swal.fire('Validasi Gagal', `Total kuantitas terpilih (${totalQtyTerpilih}) tidak sesuai kebutuhan (${currentQtyDibutuhkanTotalModal}).`, 'error');
                    return;
                }
                const targetRow = $(`#tabel-item-penjualan tbody tr[data-row-id="${currentRowIdForBatchModal}"]`);
                let stokAllocationsForSubmit = [];
                let displayBatchInfo = [];
                let displaySerialInfo = [];
                let formIsValid = true;
                let totalQtyFinalTerpilih = 0;
                $('.batch-selection-item').each(function() {
                    const idStokBarang = $(this).data('id-stok-barang');
                    const qtyAllocated = parseInt($(this).find('.qty-from-batch-input').val()) || 0;
                    totalQtyFinalTerpilih += qtyAllocated;
                    if (qtyAllocated > 0) {
                        let serialsSelectedForThisBatch = [];
                        if (currentProdukInfoForModal.memiliki_serial) {
                            $(`.nomor-seri-checkbox-multi[data-id-stok-barang="${idStokBarang}"]:checked`).each(function() {
                                serialsSelectedForThisBatch.push($(this).val());
                            });
                            if (serialsSelectedForThisBatch.length !== qtyAllocated) {
                                Swal.fire('Validasi Gagal', `Jumlah serial (${serialsSelectedForThisBatch.length}) untuk Batch ID ${idStokBarang} tidak sesuai dengan kuantitas (${qtyAllocated}). Harap periksa kembali.`, 'error');
                                formIsValid = false;
                                return false;
                            }
                        }
                        stokAllocationsForSubmit.push({
                            id_stok_barang: idStokBarang,
                            qty_allocated: qtyAllocated,
                            serials_selected: serialsSelectedForThisBatch
                        });
                        displayBatchInfo.push(`B${idStokBarang}(${qtyAllocated})`);
                        if (serialsSelectedForThisBatch.length > 0) displaySerialInfo.push(...serialsSelectedForThisBatch);
                    }
                });
                if (!formIsValid) return;
                if (totalQtyFinalTerpilih !== currentQtyDibutuhkanTotalModal) {
                    Swal.fire('Validasi Gagal', `Total kuantitas terpilih (${totalQtyFinalTerpilih}) tidak sesuai kebutuhan (${currentQtyDibutuhkanTotalModal}).`, 'error');
                    return;
                }
                if (stokAllocationsForSubmit.length === 0 && totalQtyFinalTerpilih > 0) {
                    Swal.fire('Validasi Gagal', `Terjadi kesalahan pengumpulan data. Harap coba lagi.`, 'error'); return;
                }
                targetRow.find('input.stok-allocations-json').remove();
                let hiddenInputName = targetRow.find('.select2-produk-item').attr('name').replace('[id_produk]', '[stok_allocations]');
                targetRow.find('.btn-pilih-batch-serial').parent().append(
                    `<input type="hidden" class="stok-allocations-json" name="${hiddenInputName}" value='${JSON.stringify(stokAllocationsForSubmit)}'>`
                );
                targetRow.find('.selected-batch-info').text(displayBatchInfo.length > 0 ? displayBatchInfo.join(', ') : 'Pilih').toggleClass('text-success', displayBatchInfo.length > 0).removeClass('text-danger');
                targetRow.find('.btn-pilih-batch-serial').removeClass('btn-outline-danger btn-outline-secondary').addClass(displayBatchInfo.length > 0 ? 'btn-outline-success' : 'btn-outline-secondary');
                if (currentProdukInfoForModal.memiliki_serial) {
                     targetRow.find('.serial-info-display').text(displaySerialInfo.length > 0 ? `Seri: ${displaySerialInfo.join(', ')}` : 'Pilih Serial!').toggleClass('text-danger fw-bold', displaySerialInfo.length === 0 && totalQtyFinalTerpilih > 0);
                } else {
                     targetRow.find('.serial-info-display').text('-');
                }
                $('#modalPilihBatchSerial').modal('hide');
            });

            // Validasi Form Submit Utama
            $('#form-penjualan').on('submit', function(e) {
                let isValid = true;
                let errorMessages = [];
                let firstInvalidElement = null;

                // --- VALIDASI PELANGGAN WAJIB DIISI (BARU ATAU PILIHAN) ---
                const pelangganBaruNama = $('#pelanggan_baru_nama').val().trim();
                const pelangganId = $('#id_pelanggan').val();
                const pelangganSelect2 = $('#id_pelanggan.select2-pelanggan');
                // Reset feedback
                pelangganSelect2.next('.select2-container').find('.select2-selection').removeClass('is-invalid');
                $('#pelanggan_baru_nama').removeClass('is-invalid');
                // Jika pelanggan baru diisi, nama wajib
                if (pelangganBaruNama && !pelangganId) {
                    if (!pelangganBaruNama) {
                        errorMessages.push('Nama pelanggan baru wajib diisi.');
                        isValid = false;
                        $('#pelanggan_baru_nama').addClass('is-invalid');
                        if (!firstInvalidElement) firstInvalidElement = $('#pelanggan_baru_nama');
                    }
                } else if (!pelangganId && !pelangganBaruNama) {
                    // Tidak ada pelanggan dipilih/diisi
                    errorMessages.push('Pelanggan wajib dipilih atau diisi.');
                    isValid = false;
                    pelangganSelect2.next('.select2-container').find('.select2-selection').addClass('is-invalid');
                    if (!firstInvalidElement) firstInvalidElement = pelangganSelect2.next('.select2-container').find('.select2-selection');
                }

                if (!$('#kanal_transaksi').val()) { errorMessages.push("Kanal transaksi wajib dipilih."); isValid = false; if(!firstInvalidElement) firstInvalidElement = $('#kanal_transaksi');}
                const tipeTransaksi = $('#tipe_transaksi').val();
                if (!tipeTransaksi) { errorMessages.push("Tipe transaksi wajib dipilih."); isValid = false; if(!firstInvalidElement) firstInvalidElement = $('#tipe_transaksi');}
                if (!$('#metode_pembayaran').val()) { errorMessages.push("Metode pembayaran wajib dipilih."); isValid = false; if(!firstInvalidElement) firstInvalidElement = $('#metode_pembayaran');}

                if (tipeTransaksi === 'PESAN_BARANG') {
                    if (parseRupiah($('#uang_muka').val()) <= 0) {
                        errorMessages.push("Uang muka (DP) wajib diisi dan lebih dari 0 untuk Pesan Barang.");
                        isValid = false; if(!firstInvalidElement) firstInvalidElement = $('#uang_muka');
                    }
                } else {
                    if (parseRupiah($('#uang_bayar').val()) <= 0 && parseRupiah($('#total_harga').val()) > 0) {
                        errorMessages.push("Uang bayar wajib diisi untuk transaksi biasa.");
                        isValid = false; if(!firstInvalidElement) firstInvalidElement = $('#uang_bayar');
                    } else if (parseRupiah($('#uang_bayar').val()) < parseRupiah($('#total_harga').val())) {
                        errorMessages.push("Uang bayar kurang dari total belanja.");
                        isValid = false; if(!firstInvalidElement) firstInvalidElement = $('#uang_bayar');
                    }
                }

                // 2. Validasi Item Minimal
                if ($('.item-penjualan-row').length === 0) {
                    errorMessages.push("Minimal harus ada satu item produk dalam transaksi.");
                    isValid = false; if(!firstInvalidElement) firstInvalidElement = $('#btn-tambah-item');
                }

                // 3. Validasi Setiap Item
                $('.item-penjualan-row').each(function(index) {
                    const row = $(this);
                    const itemNum = index + 1;
                    const produkSelect = row.find('.select2-produk-item');
                    const jumlahInput = row.find('.item-jumlah');
                    const hargaJualInput = row.find('.item-harga-jual');
                    const produkInfo = row.data('produk-info');
                    if (!produkSelect.val()) {
                        errorMessages.push(`Produk wajib dipilih untuk item ke-${itemNum}.`);
                        isValid = false; if(!firstInvalidElement) firstInvalidElement = produkSelect;
                    }
                    if ((parseInt(jumlahInput.val()) || 0) <= 0) {
                        errorMessages.push(`Jumlah untuk item ke-${itemNum} harus lebih dari 0.`);
                        isValid = false; if(!firstInvalidElement) firstInvalidElement = jumlahInput;
                    }
                    if (parseRupiah(hargaJualInput.val()) < 0) {
                        errorMessages.push(`Harga jual untuk item ke-${itemNum} tidak boleh negatif.`);
                        isValid = false; if(!firstInvalidElement) firstInvalidElement = hargaJualInput;
                    }
                    const stokAllocationsInput = row.find('input.stok-allocations-json');
                    if (tipeTransaksi === 'BIASA') {
                        if (!stokAllocationsInput.length || !stokAllocationsInput.val() || stokAllocationsInput.val() === '[]') {
                            errorMessages.push(`Alokasi batch/stok wajib dipilih untuk item ke-${itemNum}.`);
                            isValid = false;
                            row.find('.btn-pilih-batch-serial').removeClass('btn-outline-success btn-outline-secondary').addClass('btn-outline-danger');
                            if(!firstInvalidElement) firstInvalidElement = row.find('.btn-pilih-batch-serial');
                        } else {
                            try {
                                const allocations = JSON.parse(stokAllocationsInput.val());
                                let totalAllocatedQty = 0;
                                let serialsValidForItem = true;
                                if (!Array.isArray(allocations) || allocations.length === 0) {
                                    errorMessages.push(`Alokasi batch tidak boleh kosong untuk item ke-${itemNum}.`);
                                    isValid = false; serialsValidForItem = false;
                                } else {
                                    allocations.forEach(alloc => {
                                        totalAllocatedQty += alloc.qty_allocated;
                                        if (produkInfo && produkInfo.memiliki_serial) {
                                            if (!alloc.serials_selected || alloc.serials_selected.length !== alloc.qty_allocated) {
                                                serialsValidForItem = false;
                                            }
                                        }
                                    });
                                }
                                const qtyDiForm = parseInt(jumlahInput.val()) || 0;
                                if (totalAllocatedQty !== qtyDiForm) {
                                    errorMessages.push(`Total alokasi batch (${totalAllocatedQty}) tidak cocok dengan jumlah (${qtyDiForm}) untuk item ke-${itemNum}.`);
                                    isValid = false;
                                }
                                if (!serialsValidForItem && produkInfo && produkInfo.memiliki_serial) {
                                    errorMessages.push(`Pemilihan nomor seri tidak sesuai atau tidak lengkap untuk item ke-${itemNum}.`);
                                    isValid = false;
                                }
                                if (isValid) {
                                    row.find('.btn-pilih-batch-serial').removeClass('btn-outline-danger btn-outline-secondary').addClass('btn-outline-success');
                                } else {
                                    row.find('.btn-pilih-batch-serial').removeClass('btn-outline-success btn-outline-secondary').addClass('btn-outline-danger');
                                    if(!firstInvalidElement) firstInvalidElement = row.find('.btn-pilih-batch-serial');
                                }
                            } catch (jsonError) {
                                errorMessages.push(`Data alokasi stok tidak valid untuk item ke-${itemNum}.`);
                                isValid = false;
                                if(!firstInvalidElement) firstInvalidElement = row.find('.btn-pilih-batch-serial');
                            }
                        }
                    } else {
                        if (stokAllocationsInput.length > 0) {
                            errorMessages.push(`Item 'Pesan Barang' ke-${itemNum} tidak memerlukan alokasi batch/stok saat ini.`);
                            isValid = false;
                            if(!firstInvalidElement) firstInvalidElement = produkSelect;
                        }
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    let errorHtml = '<ul>';
                    errorMessages.forEach(function(msg) { errorHtml += `<li>${msg}</li>`; });
                    errorHtml += '</ul>';
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal!',
                        html: errorHtml,
                        confirmButtonText: 'OK, Saya Mengerti'
                    }).then(() => {
                        if (firstInvalidElement) {
                            $('html, body').animate({
                                scrollTop: $(firstInvalidElement).offset().top - 100
                            }, 500, function() {
                                $(firstInvalidElement).focus();
                            });
                        }
                    });
                } else {
                    $('#btn-simpan-penjualan').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...');
                }
            });

            // Tambah item pertama saat halaman dimuat
            if ($('#tabel-item-penjualan tbody tr').length === 0) {
                 tambahItemProduk();
            }

            // Fix untuk dropdown navbar agar tidak tertutup modal
            $('.navbar .dropdown-toggle').on('click', function (e) {
                var $el = $(this);
                var $parent = $(this).offsetParent(".dropdown-menu");
                $(this).next().toggleClass('show');
                // return false; // Mencegah event lain
            });
            $(document).on('click', function(e){
                if (!$('.navbar .dropdown').is(e.target) && $('.navbar .dropdown').has(e.target).length === 0 && $('.show').has(e.target).length === 0 ) {
                    $('.navbar .dropdown-menu').removeClass('show');
                }
            });

            // --- Tambahkan auto-open nota jika ada session last_penjualan_id_for_nota ---
            @if(session('last_penjualan_id_for_nota'))
                var penjualanId = "{{ session('last_penjualan_id_for_nota') }}";
                var urlNota = "{{ route('kasir.penjualan.nota', ['id' => ':id']) }}".replace(':id', penjualanId);
                // Buka di tab baru
                var notaWindow = window.open(urlNota, '_blank');
                if (notaWindow) {
                    notaWindow.focus(); // Fokus ke tab baru
                } else {
                    // Jika pop-up blocker aktif
                    Swal.fire(
                        'Nota Siap!',
                        'Nota untuk transaksi {{ session("last_penjualan_nomor") }} siap. Klik <a href="'+urlNota+'" target="_blank" class="btn btn-sm btn-info">di sini</a> untuk membuka.',
                        'info'
                    );
                }
            @endif
        });
    </script>
@endpush