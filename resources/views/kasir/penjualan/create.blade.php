@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('title', 'Buat Transaksi Penjualan Baru')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
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
                                {{-- <input type="text" class="form-control form-control-sm" value="{{ $nomorInvoiceSementara ?? '(Otomatis)' }}" readonly> --}}
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
                                        <option value=""></option> {{-- Option kosong untuk placeholder --}}
                                        {{-- Opsi pelanggan akan dimuat via AJAX --}}
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
                                    {{-- Contoh satu baris (akan dihapus jika JS aktif) --}}
                                    {{--
                                    <tr class="item-penjualan-row" data-row-id="0">
                                        <td>
                                            <select class="form-select form-select-sm select2-produk-item" name="items[0][id_produk]" data-placeholder="Cari Produk..." required>
                                                <option value=""></option>
                                            </select>
                                            <small class="text-muted d-block mt-1">Stok Tersedia: <span class="stok-produk-info">-</span></small>
                                            <small class="text-muted d-block">Harga Standar: <span class="harga-standar-info">-</span></small>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][jumlah]" class="form-control form-control-sm item-jumlah text-center" value="1" min="1" required>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][harga_jual]" class="form-control form-control-sm item-harga-jual text-end" required data-inputmask="'alias': 'numeric', 'groupSeparator': '.', 'radixPoint': ',', 'digits': 0, 'autoGroup': true, 'prefix': 'Rp ', 'rightAlign': false, 'removeMaskOnSubmit': true">
                                        </td>
                                        <td class="text-end item-subtotal">Rp 0</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-pilih-batch-serial" title="Pilih Batch/Serial">
                                                <i class="bi bi-box-seam"></i> <span class="selected-batch-info">Pilih</span>
                                            </button>
                                            <input type="hidden" name="items[0][id_stok_barang]">
                                            <input type="hidden" name="items[0][nomor_seri_terjual]">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm btn-hapus-item" title="Hapus Item"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    --}}
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
                <div class="card shadow-sm sticky-top" style="top: 1rem;">
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

                        <div id="area-preorder" style="display:none;"> {{-- Muncul jika tipe PRE_ORDER --}}
                            <div class="mb-3">
                                <label for="uang_muka" class="form-label">Uang Muka (DP):</label>
                                <input type="text" class="form-control input-rupiah @error('uang_muka') is-invalid @enderror" id="uang_muka" name="uang_muka" data-inputmask-alias="numeric">
                                @error('uang_muka') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="estimasi_kirim_at" class="form-label">Estimasi Kirim:</label>
                                <input type="date" class="form-control @error('estimasi_kirim_at') is-invalid @enderror" id="estimasi_kirim_at" name="estimasi_kirim_at" min="{{ Carbon\Carbon::today()->toDateString() }}">
                                @error('estimasi_kirim_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                             <div class="mb-3">
                                <label class="form-label fw-bold">Sisa Pembayaran:</label>
                                <input type="text" class="form-control-plaintext total-display text-end text-danger" id="display_sisa_pembayaran_po" value="Rp 0" readonly>
                                <input type="hidden" name="sisa_pembayaran_po" id="sisa_pembayaran_po" value="0">
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

                        <div class="mb-3" id="area-uang-bayar"> {{-- Muncul jika bukan PRE_ORDER atau PRE_ORDER tapi mau bayar lunas --}}
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
                            {{-- <button type="button" class="btn btn-outline-secondary">Tunda Transaksi</button> --}}
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

{{-- Modal Pilih Batch/Serial (Struktur Awal) --}}
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
                <input type="hidden" id="modal_batch_qty_dibutuhkan">

                <div class="alert alert-info">
                    <p class="mb-1">Anda membutuhkan <strong id="qty-dibutuhkan-info-modal">X</strong> unit untuk produk: <strong id="nama-produk-modal-alert">Nama Produk</strong>.</p>
                    <p class="mb-0" id="info-total-stok-modal">Total stok tersedia: Y unit.</p>
                </div>

                <div id="batch-allocation-details">
                    {{-- Detail alokasi per batch akan ditampilkan di sini --}}
                </div>
                
                <div id="serial-selection-area-multi" class="mt-3">
                    {{-- Area pemilihan serial per batch akan ditambahkan di sini --}}
                </div>
                
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
    {{-- jQuery (jika belum ada di layout utama) --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- Bootstrap JS (pastikan ini dimuat setelah jQuery) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Select2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    {{-- Inputmask JS (untuk format Rupiah) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js"></script>
    {{-- SweetAlert2 --}}
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function formatRupiah(angka, prefix = 'Rp ') {
            if (isNaN(angka) || angka === null || angka === undefined) return prefix + '0';
            let number_string = angka.toString().replace(/[^,\d]/g, ''),
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
            if (!rupiahString) return 0;
            return parseInt(rupiahString.replace(/[^0-9]/g, ''), 10) || 0;
        }

        let itemRowCounter = 0; // Pindahkan ke scope global agar bisa diakses fungsi tambahItemProduk

        $(document).ready(function() {
            // Inisialisasi Inputmask untuk Rupiah
            $('.input-rupiah').inputmask({
                alias: 'numeric', groupSeparator: '.', radixPoint: ',', digits: 0, autoGroup: true,
                prefix: 'Rp ', rightAlign: false, removeMaskOnSubmit: true,
                oncleared: function () { $(this).val(''); }
            });

            // Inisialisasi Select2 untuk Pelanggan
            $('#id_pelanggan.select2-pelanggan').select2({ // Targetkan dengan ID dan class
                theme: "bootstrap-5",
                width: '100%',
                placeholder: $('#id_pelanggan.select2-pelanggan').data('placeholder'), // Ambil placeholder dari atribut data
                allowClear: true,
                ajax: {
                    url: "{{ route('kasir.ajax.pelanggan.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term, // search term
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.items, // data.items sudah dalam format {id: ..., text: ...}
                            pagination: {
                                more: (params.page * 15) < data.total_count // Asumsi limit 15
                            }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1 // Mulai cari setelah 1 karakter diketik
            }).on('select2:select', function (e) {
                var data = e.params.data;
                if (data.id !== 'PELANGGAN_BARU') {
                    $('#pelanggan_baru_nama').val('');
                    $('#pelanggan_baru_telepon').val('');
                    $('#pelanggan_baru_alamat').val('');
                }
            }).on('select2:clear', function (e) {
                 $('#pelanggan_baru_nama').val('');
                 $('#pelanggan_baru_telepon').val('');
                 $('#pelanggan_baru_alamat').val('');
            });


            // Tombol Tambah Pelanggan Cepat
            $('#btn-tambah-pelanggan-cepat').on('click', function() {
                $('#modal_pelanggan_nama').val('').removeClass('is-invalid');
                $('#modal_pelanggan_telepon').val('');
                $('#modal_pelanggan_alamat').val('');
                $('#modal_nama_error').text('');
                $('#modalTambahPelangganCepat').modal('show');
            });

            // Tombol Simpan Pelanggan dari Modal
            $('#btn-simpan-pelanggan-cepat').on('click', function() {
                const nama = $('#modal_pelanggan_nama').val().trim();
                const telepon = $('#modal_pelanggan_telepon').val().trim();
                const alamat = $('#modal_pelanggan_alamat').val().trim();

                // Validasi nama di modal
                if (!nama) {
                    $('#modal_pelanggan_nama').addClass('is-invalid');
                    $('#modal_nama_error').text('Nama pelanggan wajib diisi.');
                    return;
                }
                $('#modal_pelanggan_nama').removeClass('is-invalid');
                $('#modal_nama_error').text('');

                // Isi hidden input untuk dikirim ke backend
                $('#pelanggan_baru_nama').val(nama);
                $('#pelanggan_baru_telepon').val(telepon);
                $('#pelanggan_baru_alamat').val(alamat);

                // Kosongkan pilihan di Select2 utama agar backend tahu ini pelanggan baru
                $('#id_pelanggan.select2-pelanggan').val(null).trigger('change'); 
                $('#info-pelanggan-baru').text('isi Data Pelanggan Baru: ' + nama).show();

                // Tutup modal dan tampilkan notifikasi
                $('#modalTambahPelangganCepat').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Pelanggan Baru Siap Digunakan', // Ubah judul sedikit
                    text: 'Data pelanggan baru akan disimpan saat transaksi selesai.', // Ubah teks sedikit
                    timer: 2500, // Sedikit lebih lama mungkin?
                    showConfirmButton: false
                });
            });

            // Menmbahkan event listener untuk clear hidden input jika user memilih pelanggan lama lagi
            $('#id_pelanggan.select2-pelanggan').on('select2:select', function (e) {
                // Jika memilih pelanggan yang sudah ada (bukan hasil dari 'Tambah Cepat')
                 $('#pelanggan_baru_nama').val('');
                 $('#pelanggan_baru_telepon').val('');
                 $('#pelanggan_baru_alamat').val('');
                 $('#info-pelanggan-baru').hide().text(''); // Sembunyikan indikator visual
            }).on('select2:clear', function (e) {
                 // Juga clear saat pilihan dihapus
                 $('#pelanggan_baru_nama').val('');
                 $('#pelanggan_baru_telepon').val('');
                 $('#pelanggan_baru_alamat').val('');
                 $('#info-pelanggan-baru').hide().text('');
            });


            // Fungsi untuk menambah baris item produk
            function tambahItemProduk(produkData = null) {
                itemRowCounter++;
                const rowId = itemRowCounter;

                const newRowHtml = `
                    <tr class="item-penjualan-row" data-row-id="${rowId}">
                        <td>
                            <select class="form-select form-select-sm select2-produk-item" name="items[${rowId}][id_produk]" data-placeholder="Cari Produk..." required>
                                <option value=""></option>
                            </select>
                            <small class="text-muted d-block mt-1">Stok: <span class="stok-produk-info">-</span></small>
                            <small class="text-muted d-block">Harga Std: <span class="harga-standar-info">-</span></small>
                            <div class="invalid-feedback product-error-feedback"></div>
                        </td>
                        <td>
                            <input type="number" name="items[${rowId}][jumlah]" class="form-control form-control-sm item-jumlah text-center" value="1" min="1" required>
                        </td>
                        <td>
                            <input type="text" name="items[${rowId}][harga_jual]" class="form-control form-control-sm item-harga-jual text-end input-rupiah-item" required>
                        </td>
                        <td class="text-end item-subtotal fw-bold">Rp 0</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-pilih-batch-serial" title="Pilih Batch/Serial" disabled>
                                <i class="bi bi-box-seam"></i> <span class="selected-batch-info">Pilih</span>
                            </button>
                            <input type="hidden" name="items[${rowId}][id_stok_barang]">
                            <input type="hidden" name="items[${rowId}][nomor_seri_terjual]">
                            <small class="text-muted d-block mt-1 serial-info-display"></small>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm btn-hapus-item" title="Hapus Item"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `;
                $('#tabel-item-penjualan tbody').append(newRowHtml);
                const newRow = $(`#tabel-item-penjualan tbody tr[data-row-id="${rowId}"]`);

                // Inisialisasi Select2 untuk produk di baris baru
                newRow.find('.select2-produk-item').select2({
                    theme: "bootstrap-5",
                    width: '100%',
                    placeholder: newRow.find('.select2-produk-item').data('placeholder'),
                    // allowClear: true, // Bisa diaktifkan jika perlu
                    ajax: {
                        url: "{{ route('kasir.ajax.produk.search') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term, // search term
                                page: params.page || 1,
                                for_sale: true // Menandakan pencarian untuk penjualan
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.items.map(function(item) { // data.items sudah berisi field tambahan
                                    return {
                                        id: item.id,
                                        text: item.text,
                                        harga_jual_standar: item.harga_jual_standar,
                                        memiliki_serial: item.memiliki_serial
                                        // stok_tersedia: item.stok_tersedia // Akan di-handle saat pilih batch
                                    };
                                }),
                                pagination: {
                                    more: (params.page * 15) < data.total_count
                                }
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1
                }).on('select2:select', function(e) { // permasalahan di sini untuk data harga_jual_standar yang dibaca dari db contoh 120000,00 nah dibaca sama halaman ini menjadi 12.000.0 00 menambahkan 00 dibelakang. sudah fixed
                const data = e.params.data; // data.harga_jual_standar dari AJAX
                const currentRow = $(this).closest('.item-penjualan-row');
                const idProduk = data.id;

                // Ambil harga standar dari data AJAX
                let hargaStandarDariAjax = data.harga_jual_standar; // Ini bisa jadi float 120000.00 atau string "120000.00"

                // 1. Konversi ke Angka Float (jika masih string)
                let hargaFloat = parseFloat(hargaStandarDariAjax) || 0;

                // 2. Karena Inputmask kita (digits: 0) tidak menangani sen,
                //    kita bulatkan atau ambil bagian integernya saja.
                //    Math.round() akan menangani .50 ke atas, Math.floor() akan selalu ke bawah.
                //    Jika Anda yakin dari DB selalu .00, parseInt() juga bisa.
                let hargaUntukInputmask = Math.round(hargaFloat); // Misal: 120000.00 -> 120000

                // Debugging:
                // console.log("Harga dari AJAX:", hargaStandarDariAjax, typeof hargaStandarDariAjax);
                // console.log("Harga setelah parseFloat:", hargaFloat);
                // console.log("Harga untuk Inputmask (setelah pembulatan):", hargaUntukInputmask);

                // Tampilkan harga standar di info teks (menggunakan formatRupiah yang sudah ada)
                currentRow.find('.harga-standar-info').text(formatRupiah(hargaUntukInputmask));

                // Set nilai ANGKA MURNI (yang sudah dibulatkan) ke input field.
                // Inputmask akan mengambil angka ini dan memformatnya sesuai aturan (prefix Rp, separator ribuan).
                currentRow.find('.item-harga-jual').val(hargaUntukInputmask);
                // Penting: Trigger 'input' agar Inputmask memproses nilai baru yang diset secara programatik.
                currentRow.find('.item-harga-jual').trigger('input');


                currentRow.find('.btn-pilih-batch-serial').prop('disabled', false);
                currentRow.find('.stok-produk-info').text('?'); 

                if(data.memiliki_serial){
                    currentRow.find('.serial-info-display').text('Wajib Serial').addClass('text-danger');
                } else {
                    currentRow.find('.serial-info-display').text('').removeClass('text-danger');
                }
                currentRow.find('.product-error-feedback').text('');
                hitungSubtotal(currentRow); // Pastikan hitungSubtotal menggunakan parseRupiah yang benar
                // Otomatis fokus ke input jumlah setelah produk dipilih
                currentRow.find('.item-jumlah').focus().select();
            }).on('select2:unselect', function (e) {
                    const currentRow = $(this).closest('.item-penjualan-row');
                    currentRow.find('.harga-standar-info').text('-');
                    currentRow.find('.item-harga-jual').val('Rp 0').trigger('input');
                    currentRow.find('.stok-produk-info').text('-');
                    currentRow.find('.btn-pilih-batch-serial').prop('disabled', true);
                    currentRow.find('.selected-batch-info').text('Pilih');
                    currentRow.find('input[name$="[id_stok_barang]"]').val('');
                    currentRow.find('input[name$="[nomor_seri_terjual]"]').val('');
                    currentRow.find('.serial-info-display').text('').removeClass('text-danger');
                    hitungSubtotal(currentRow);
                });

                // Inisialisasi Inputmask untuk harga jual di baris baru
                newRow.find('.input-rupiah-item').inputmask({
                    alias: 'numeric', groupSeparator: '.', radixPoint: ',', digits: 0, autoGroup: true,
                    prefix: 'Rp ', rightAlign: false, removeMaskOnSubmit: true,
                    oncleared: function () { $(this).val(''); }
                });

                // Jika ada produkData awal (misal dari scan barcode nanti)
                if(produkData){
                    var option = new Option(produkData.text, produkData.id, true, true);
                    newRow.find('.select2-produk-item').append(option).trigger('change');
                    // Trigger select event untuk mengisi data lain
                    newRow.find('.select2-produk-item').trigger({
                        type: 'select2:select',
                        params: { data: produkData }
                    });
                } else {
                    // Fokus ke select produk baru jika tidak ada data awal
                    newRow.find('.select2-produk-item').select2('open');
                }
            }

            // Tombol Tambah Item
            $('#btn-tambah-item').on('click', function() {
                tambahItemProduk();
            });

            // Fungsi hitung subtotal per baris
            function hitungSubtotal(row) {
                const jumlah = parseInt(row.find('.item-jumlah').val()) || 0;
                const hargaJual = parseRupiah(row.find('.item-harga-jual').val());
                const subtotal = jumlah * hargaJual;
                row.find('.item-subtotal').text(formatRupiah(subtotal));
                hitungTotalBelanja();
            }

            // Event listener untuk input jumlah dan harga jual
            $('#tabel-item-penjualan').on('input change', '.item-jumlah, .item-harga-jual', function() {
                const row = $(this).closest('.item-penjualan-row');
                hitungSubtotal(row);
            });

            // Fungsi hitung total belanja keseluruhan
            function hitungTotalBelanja() {
                let totalBelanja = 0;
                $('.item-penjualan-row').each(function() {
                    totalBelanja += parseRupiah($(this).find('.item-subtotal').text());
                });
                $('#display_total_belanja').val(formatRupiah(totalBelanja));
                $('#total_harga').val(totalBelanja);
                hitungKembalian();
                hitungSisaPembayaranPO();
            }

            // Tombol Hapus Item
            $('#tabel-item-penjualan').on('click', '.btn-hapus-item', function() {
                $(this).closest('.item-penjualan-row').remove();
                hitungTotalBelanja();
            });

            // Logika untuk Pre-Order
            $('#tipe_transaksi').on('change', function() {
                const tipe = $(this).val();
                if (tipe === 'PRE_ORDER') {
                    $('#area-preorder').slideDown();
                    $('#uang_muka').prop('required', true);
                    // $('#estimasi_kirim_at').prop('required', true); // Dibuat opsional dulu, validasi di backend jika perlu
                    $('#area-uang-bayar label[for="uang_bayar"]').text('Uang Bayar (Pelunasan):');
                    $('#uang_bayar').prop('required', false); // Tidak wajib saat DP
                } else {
                    $('#area-preorder').slideUp();
                    $('#uang_muka').prop('required', false).val('');
                    $('#estimasi_kirim_at').prop('required', false).val('');
                    $('#area-uang-bayar label[for="uang_bayar"]').text('Uang Bayar:');
                    $('#uang_bayar').prop('required', true);
                }
                hitungSisaPembayaranPO();
                hitungKembalian();
            }).trigger('change');

            $('#uang_muka').on('input', function() {
                hitungSisaPembayaranPO();
            });

            function hitungSisaPembayaranPO() {
                if ($('#tipe_transaksi').val() === 'PRE_ORDER') {
                    const totalBelanja = parseRupiah($('#display_total_belanja').val());
                    const uangMuka = parseRupiah($('#uang_muka').val());
                    const sisa = totalBelanja - uangMuka;
                    $('#display_sisa_pembayaran_po').val(formatRupiah(Math.max(0, sisa)));
                    $('#sisa_pembayaran_po').val(Math.max(0, sisa));
                } else {
                    $('#display_sisa_pembayaran_po').val(formatRupiah(0));
                    $('#sisa_pembayaran_po').val(0);
                }
            }

            $('#uang_bayar').on('input', function() {
                hitungKembalian();
            });

            function hitungKembalian() {
                const totalBelanja = parseRupiah($('#display_total_belanja').val());
                const uangBayar = parseRupiah($('#uang_bayar').val());
                let kembalian = 0;

                if ($('#tipe_transaksi').val() === 'PRE_ORDER') {
                    const uangMuka = parseRupiah($('#uang_muka').val());
                    const sisaPembayaranPO = Math.max(0, totalBelanja - uangMuka);
                    if (uangBayar > 0 && sisaPembayaranPO > 0) { // Jika ada sisa PO dan ada uang bayar pelunasan
                        kembalian = uangBayar - sisaPembayaranPO;
                    } else if (uangBayar > 0 && sisaPembayaranPO === 0 && uangMuka === totalBelanja) { // Jika DP sudah lunas dan ada uang bayar (seharusnya tidak terjadi jika alur benar)
                        kembalian = uangBayar; // Kembalikan semua uang bayar jika total sudah lunas via DP
                    } else {
                        kembalian = 0;
                    }
                } else {
                    kembalian = uangBayar - totalBelanja;
                }
                $('#display_kembalian').val(formatRupiah(Math.max(0, kembalian)));
            }

            // --- MODIFIKASI UNTUK PEMILIHAN BATCH ---
            let currentRowIdForBatchModal = null; // Untuk menyimpan rowId item yang sedang diproses modalnya
            let currentProdukInfoForModal = {}; // Untuk menyimpan info produk (memiliki_serial, dll)
            let currentQtyDibutuhkanTotalModal = 0; 

            $('#tabel-item-penjualan').on('click', '.btn-pilih-batch-serial', function() {
                const row = $(this).closest('.item-penjualan-row');
                currentRowIdForBatchModal = row.data('row-id');
                const produkSelect = row.find('.select2-produk-item');
                const idProduk = produkSelect.val();
                const produkDataArray = produkSelect.select2('data');
                currentProdukInfoForModal = produkDataArray && produkDataArray.length > 0 ? produkDataArray[0] : {};
                const namaProduk = currentProdukInfoForModal.text || 'Tidak Diketahui';
                currentQtyDibutuhkanTotalModal = parseInt(row.find('.item-jumlah').val()) || 0; // Ambil jumlah dari input

                if (!idProduk || currentQtyDibutuhkanTotalModal <= 0) {
                    Swal.fire('Oops!', 'Pilih produk dan masukkan jumlah yang valid terlebih dahulu.', 'warning');
                    return;
                }

                // Reset modal
                $('#modal_batch_item_row_id').val(currentRowIdForBatchModal);
                $('#modal_batch_id_produk').val(idProduk);
                $('#modal_batch_qty_dibutuhkan_total').val(currentQtyDibutuhkanTotalModal);
                $('#nama-produk-modal-batch').text(namaProduk);
                $('#nama-produk-modal-alert').text(namaProduk);
                $('#qty-dibutuhkan-info-modal').text(currentQtyDibutuhkanTotalModal);
                $('#batch-allocation-details').html('<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat batch tersedia...</p></div>');
                $('#serial-selection-area-multi').empty().hide(); // Serial area utama disembunyikan dulu
                $('#btn-simpan-pilihan-batch').prop('disabled', true);

                $.ajax({
                    url: '{{ route("kasir.ajax.stok.available") }}',
                    type: 'GET',
                    data: { id_produk: idProduk, qty_dibutuhkan: currentQtyDibutuhkanTotalModal }, // qty_dibutuhkan dikirim untuk info saja
                    success: function(response) {
                        if (response.success) {
                            $('#info-total-stok-modal').text(`Total stok tersedia: ${response.total_stok_tersedia} unit.`);
                            currentProdukInfoForModal.memiliki_serial = response.memiliki_serial;

                            if (response.total_stok_tersedia < currentQtyDibutuhkanTotalModal && response.batches_data.length > 0) {
                                 $('#batch-allocation-details').html(`<p class="text-danger text-center">Stok keseluruhan (${response.total_stok_tersedia} unit) tidak mencukupi kebutuhan (${currentQtyDibutuhkanTotalModal} unit).</p>`);
                                 // Tetap tampilkan batch jika ada, agar user tahu sisa stoknya
                                 displayAvailableBatchesForSelection(response.batches_data, currentQtyDibutuhkanTotalModal, response.memiliki_serial);
                                return;
                            }
                            if (!response.batches_data || response.batches_data.length === 0) {
                                $('#batch-allocation-details').html('<p class="text-danger text-center">Tidak ada batch stok yang tersedia untuk produk ini.</p>');
                                return;
                            }
                            displayAvailableBatchesForSelection(response.batches_data, currentQtyDibutuhkanTotalModal, response.memiliki_serial);
                        } else {
                            $('#batch-allocation-details').html(`<p class="text-danger text-center">${response.message || 'Gagal memuat data batch.'}</p>`);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                         $('#batch-allocation-details').html(`<p class="text-danger text-center">Error: Gagal mengambil data batch. (${errorThrown})</p>`);
                    }
                });
                $('#modalPilihBatchSerial').modal('show');
            });

            function displayAvailableBatchesForSelection(batchesData, qtyDibutuhkanTotal, produkMemilikiSerial) {
                let batchListHtml = `<h5 class="mb-2">Pilih Kuantitas dari Batch Tersedia:</h5>
                                     <p class="mb-1">Total dibutuhkan: <strong id="modal-qty-dibutuhkan-display">${qtyDibutuhkanTotal}</strong> unit.</p>
                                     <p class="mb-3">Total dipilih: <strong id="modal-qty-terpilih-display" class="text-primary">0</strong> unit.</p>
                                     <div class="list-group mb-3">`;

                if (batchesData.length === 0) {
                     $('#batch-allocation-details').html('<p class="text-info text-center">Tidak ada batch yang bisa ditampilkan.</p>');
                     return;
                }

                batchesData.forEach(batch => {
                    batchListHtml += `
                        <div class="list-group-item batch-selection-item p-2" data-id-stok-barang="${batch.id}" data-max-stok-batch="${batch.jumlah_tersedia}">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <strong>Batch ID: ${batch.id}</strong>
                                    <small class="d-block">Terima: ${batch.diterima_at_formatted} | Sisa: ${batch.jumlah_tersedia}</small>
                                    <small class="d-block">Lok: ${batch.lokasi} | Garansi: ${batch.tipe_garansi}</small>
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Qty:</span>
                                        <input type="number" class="form-control qty-from-batch-input text-center" value="0"
                                               min="0" max="${batch.jumlah_tersedia}" data-id-stok-barang="${batch.id}">
                                    </div>
                                </div>
                            </div>
                            ${produkMemilikiSerial ? `<div class="mt-2 serial-container-for-batch" id="serials-for-batch-${batch.id}" style="display:none;"></div>` : ''}
                        </div>
                    `;
                });
                batchListHtml += '</div>';
                $('#batch-allocation-details').html(batchListHtml);
                $('#serial-selection-area-multi').hide().empty(); // Area serial utama tidak dipakai lagi dengan cara ini

                attachQtyInputBatchEvents(produkMemilikiSerial);
                updateTotalQtyTerpilihDisplay(); // Panggil sekali untuk set 0
            }

            function attachQtyInputBatchEvents(produkMemilikiSerial) {
                $('.qty-from-batch-input').off('input change').on('input change', function() {
                    let val = parseInt($(this).val()) || 0;
                    const max = parseInt($(this).attr('max'));
                    const idStokBarang = $(this).data('id-stok-barang');
                    const serialContainer = $(`#serials-for-batch-${idStokBarang}`);

                    if (val < 0) val = 0;
                    if (val > max) val = max;
                    $(this).val(val); // Koreksi input jika di luar range

                    updateTotalQtyTerpilihDisplay();

                    if (produkMemilikiSerial) {
                        if (val > 0) {
                            serialContainer.show();
                            loadNomorSeriUntukAlokasiBatch(idStokBarang, val); // val adalah qty yg dibutuhkan dari batch ini
                        } else {
                            serialContainer.hide().empty();
                            // Panggil checkOverallSerialSelectionValidity untuk re-evaluasi tombol simpan jika qty jadi 0
                            checkOverallModalValidity();
                        }
                    } else {
                        // Jika tidak berserial, langsung cek validitas keseluruhan
                        checkOverallModalValidity();
                    }
                });
            }

            function updateTotalQtyTerpilihDisplay() {
                let totalQtyTerpilih = 0;
                $('.qty-from-batch-input').each(function() {
                    totalQtyTerpilih += parseInt($(this).val()) || 0;
                });
                $('#modal-qty-terpilih-display').text(totalQtyTerpilih);
                // Ubah warna jika sudah pas atau lebih
                if (totalQtyTerpilih === currentQtyDibutuhkanTotalModal) {
                    $('#modal-qty-terpilih-display').removeClass('text-danger').addClass('text-success');
                } else if (totalQtyTerpilih > currentQtyDibutuhkanTotalModal) {
                    $('#modal-qty-terpilih-display').removeClass('text-success').addClass('text-danger');
                } else {
                     $('#modal-qty-terpilih-display').removeClass('text-success text-danger');
                }
                checkOverallModalValidity(); // Cek validitas setiap kali total berubah
            }

            function loadNomorSeriUntukAlokasiBatch(idStokBarang, qtyDibutuhkanDariBatchIni) {
                // Fungsi ini sama seperti sebelumnya, tapi pastikan ia memanggil checkOverallModalValidity() di akhir success/error
                const serialContainerTarget = $(`#serials-for-batch-${idStokBarang}`);
                serialContainerTarget.html('<div class="text-center p-1"><small><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat serial...</small></div>');

                $.ajax({
                    url: '{{ route("kasir.ajax.stok.serials") }}',
                    type: 'GET',
                    data: { id_stok_barang: idStokBarang },
                    success: function(response) {
                        if (response.success && response.serials) {
                            if (response.serials.length < qtyDibutuhkanDariBatchIni) {
                                serialContainerTarget.html(`<p class="text-danger mb-1"><small>Stok serial di batch ${idStokBarang} (${response.serials.length}) kurang dari yg dibutuhkan (${qtyDibutuhkanDariBatchIni}).</small></p>`);
                            } else {
                                let serialHtml = `<p class="mb-1"><small>Pilih ${qtyDibutuhkanDariBatchIni} serial untuk Batch ID: ${idStokBarang}</small></p><div class="row row-cols-1 row-cols-sm-2 g-1">`; // Lebih sedikit kolom untuk ruang
                                response.serials.forEach(function(serial, index) {
                                    serialHtml += `
                                        <div class="col">
                                            <div class="form-check form-check-sm">
                                                <input class="form-check-input nomor-seri-checkbox-multi" type="checkbox" value="${serial}"
                                                       id="serial-batch-${idStokBarang}-${index}" data-id-stok-barang="${idStokBarang}" data-qty-needed-for-this-batch="${qtyDibutuhkanDariBatchIni}">
                                                <label class="form-check-label" for="serial-batch-${idStokBarang}-${index}">
                                                    <small>${serial}</small>
                                                </label>
                                            </div>
                                        </div>
                                    `;
                                });
                                serialHtml += '</div>';
                                serialContainerTarget.html(serialHtml);
                                attachMultiBatchSerialCheckboxEvents();
                            }
                        } else {
                            serialContainerTarget.html(`<p class="text-danger mb-1"><small>${response.message || `Gagal memuat serial untuk Batch ID: ${idStokBarang}.`}</small></p>`);
                        }
                        checkOverallModalValidity(); // Panggil setelah update HTML
                    },
                    error: function() {
                        serialContainerTarget.html(`<p class="text-danger mb-1"><small>Error AJAX memuat serial untuk Batch ID: ${idStokBarang}.</small></p>`);
                        checkOverallModalValidity(); // Panggil setelah update HTML
                    }
                });
            }

            function attachMultiBatchSerialCheckboxEvents() {
                $('.nomor-seri-checkbox-multi').off('change').on('change', function() {
                    const idStokBarang = $(this).data('id-stok-barang');
                    const qtyNeededForThisBatch = parseInt($(`.qty-from-batch-input[data-id-stok-barang="${idStokBarang}"]`).val()) || 0; // Ambil dari input qty batch
                    const checkedInThisGroup = $(`.nomor-seri-checkbox-multi[data-id-stok-barang="${idStokBarang}"]:checked`).length;

                    if (checkedInThisGroup > qtyNeededForThisBatch) {
                        $(this).prop('checked', false);
                        Swal.fire('Perhatian', `Anda hanya boleh memilih ${qtyNeededForThisBatch} serial untuk batch ini.`, 'warning');
                    }
                    checkOverallModalValidity();
                });
                checkOverallModalValidity();
            }

            function checkOverallModalValidity() {
                let totalQtyTerpilih = 0;
                $('.qty-from-batch-input').each(function() {
                    totalQtyTerpilih += parseInt($(this).val()) || 0;
                });

                if (totalQtyTerpilih !== currentQtyDibutuhkanTotalModal) {
                    $('#btn-simpan-pilihan-batch').prop('disabled', true);
                    return;
                }

                let allSerialsValid = true;
                if (currentProdukInfoForModal.memiliki_serial) {
                    $('.qty-from-batch-input').each(function() {
                        const qtyDariBatchIni = parseInt($(this).val()) || 0;
                        if (qtyDariBatchIni > 0) {
                            const idStokBarang = $(this).data('id-stok-barang');
                            const serialsCheckedForThisBatch = $(`.nomor-seri-checkbox-multi[data-id-stok-barang="${idStokBarang}"]:checked`).length;
                            if (serialsCheckedForThisBatch !== qtyDariBatchIni) {
                                allSerialsValid = false;
                                return false; // break .each loop
                            }
                            // Cek jika ada pesan error di container serial (misal stok serial kurang)
                            if ($(`#serials-for-batch-${idStokBarang}`).find('p.text-danger').length > 0) {
                                allSerialsValid = false;
                                return false;
                            }
                        }
                    });
                }
                $('#btn-simpan-pilihan-batch').prop('disabled', !allSerialsValid);
            }

            $('#btn-simpan-pilihan-batch').on('click', function() {
                // ... (logika untuk mengumpulkan data dari input .qty-from-batch-input dan serial yang dipilih) ...
                // ... (mirip dengan #btn-simpan-pilihan-batch sebelumnya, tapi sumber datanya beda) ...
                if (!currentRowIdForBatchModal) return;
                const targetRow = $(`#tabel-item-penjualan tbody tr[data-row-id="${currentRowIdForBatchModal}"]`);
                let stokAllocationsForSubmit = [];
                let displayBatchInfo = [];
                let displaySerialInfo = [];
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
                                Swal.fire('Validasi Gagal', `Jumlah serial (${serialsSelectedForThisBatch.length}) untuk Batch ID ${idStokBarang} tidak sesuai dengan kuantitas (${qtyAllocated}).`, 'error');
                                stokAllocationsForSubmit = []; // Kosongkan agar tidak submit
                                return false; // Hentikan .each
                            }
                        }
                        stokAllocationsForSubmit.push({
                            id_stok_barang: idStokBarang,
                            qty_allocated: qtyAllocated,
                            serials_selected: serialsSelectedForThisBatch
                        });
                        displayBatchInfo.push(`Batch ${idStokBarang} (${qtyAllocated})`);
                        if (serialsSelectedForThisBatch.length > 0) {
                            displaySerialInfo.push(...serialsSelectedForThisBatch);
                        }
                    }
                });

                if (totalQtyFinalTerpilih !== currentQtyDibutuhkanTotalModal) {
                    Swal.fire('Validasi Gagal', `Total kuantitas yang dipilih (${totalQtyFinalTerpilih}) tidak sesuai dengan yang dibutuhkan (${currentQtyDibutuhkanTotalModal}).`, 'error');
                    return;
                }
                if (stokAllocationsForSubmit.length === 0 && totalQtyFinalTerpilih > 0) { // Jika ada qty tapi array kosong karena error serial
                     Swal.fire('Validasi Gagal', `Terjadi kesalahan dalam pengumpulan data serial. Harap periksa kembali pilihan serial Anda.`, 'error');
                     return;
                }
                 if (stokAllocationsForSubmit.length === 0 && totalQtyFinalTerpilih === 0 && currentQtyDibutuhkanTotalModal > 0) {
                    Swal.fire('Informasi', `Anda belum memilih kuantitas dari batch manapun.`, 'info');
                    return; // Jangan tutup modal jika tidak ada yang dipilih
                }


                // Simpan sebagai JSON string
                targetRow.find('input[name$="[id_stok_barang]"]').remove(); // Hapus input id_stok_barang yang lama
                targetRow.find('input[name$="[nomor_seri_terjual]"]').remove(); // Hapus input nomor_seri_terjual yang lama
                let hiddenInputName = targetRow.find('.select2-produk-item').attr('name').replace('[id_produk]', '[stok_allocations]');
                targetRow.find('.btn-pilih-batch-serial').parent().find('input.stok-allocations-json').remove();
                targetRow.find('.btn-pilih-batch-serial').parent().append(
                    `<input type="hidden" class="stok-allocations-json" name="${hiddenInputName}" value='${JSON.stringify(stokAllocationsForSubmit)}'>`
                );

                targetRow.find('.selected-batch-info').text(displayBatchInfo.join(', ')).removeClass('text-danger').addClass('text-success');
                targetRow.find('.btn-pilih-batch-serial').removeClass('btn-outline-danger btn-outline-secondary').addClass('btn-outline-success');

                if (displaySerialInfo.length > 0) {
                    targetRow.find('.serial-info-display').text(`Seri: ${displaySerialInfo.join(', ')}`).removeClass('text-danger');
                } else if (currentProdukInfoForModal.memiliki_serial && totalQtyFinalTerpilih > 0) { // Jika berserial dan ada qty, tapi tidak ada serial terpilih
                    targetRow.find('.serial-info-display').text('Pilih Serial!').addClass('text-danger');
                } else {
                     targetRow.find('.serial-info-display').text('-');
                }
                // Update info stok tersedia di baris item (opsional)
                // targetRow.find('.stok-produk-info').text('Terpilih');

                $('#modalPilihBatchSerial').modal('hide');
            });


            // ... (Validasi Form Sebelum Submit tetap sama) ...
             $('#form-penjualan').on('submit', function(e) {
                let isValid = true;
                let errorMessages = [];
                let firstInvalidElement = null;

                if ($('#tabel-item-penjualan tbody tr').length === 0) {
                    errorMessages.push('Minimal harus ada 1 item produk dalam transaksi.');
                    isValid = false;
                }

                $('.item-penjualan-row').each(function(index) {
                    const row = $(this);
                    const itemNum = index + 1;
                    const produkSelect = row.find('.select2-produk-item');
                    const jumlahInput = row.find('.item-jumlah');
                    const hargaJualInput = row.find('.item-harga-jual');
                    const idStokBarangInput = row.find('input[name$="[id_stok_barang]"]');
                    // Validasi baru untuk alokasi stok
                    const stokAllocationsInput = row.find('input.stok-allocations-json');
                    const produkDataArray = row.find('.select2-produk-item').select2('data');
                    const produkData = produkDataArray && produkDataArray.length > 0 ? produkDataArray[0] : null;

                    if (!stokAllocationsInput.length || !stokAllocationsInput.val()) {
                        errorMessages.push(`Alokasi batch/stok belum dipilih untuk item ke-${itemNum} (${produkData ? produkData.text : 'produk'}).`);
                        isValid = false;
                        row.find('.btn-pilih-batch-serial').addClass('btn-outline-danger').removeClass('btn-outline-success btn-outline-secondary');
                        if (!firstInvalidElement) firstInvalidElement = row.find('.btn-pilih-batch-serial');
                    } else {
                        try {
                            const allocations = JSON.parse(stokAllocationsInput.val());
                            let totalAllocatedQty = 0;
                            let serialsValid = true;
                            allocations.forEach(alloc => {
                                totalAllocatedQty += alloc.qty_allocated;
                                if (produkData && produkData.memiliki_serial) {
                                    if (alloc.serials_selected.length !== alloc.qty_allocated) {
                                        serialsValid = false;
                                    }
                                }
                            });
                            const qtyDiForm = parseInt(row.find('.item-jumlah').val()) || 0;
                            if (totalAllocatedQty !== qtyDiForm) {
                                errorMessages.push(`Total kuantitas dari alokasi batch (${totalAllocatedQty}) tidak cocok dengan jumlah item (${qtyDiForm}) untuk item ke-${itemNum}.`);
                                isValid = false;
                                row.find('.btn-pilih-batch-serial').addClass('btn-outline-danger');
                                if (!firstInvalidElement) firstInvalidElement = row.find('.btn-pilih-batch-serial');
                            }
                            if (!serialsValid && produkData && produkData.memiliki_serial) {
                                errorMessages.push(`Jumlah nomor seri tidak sesuai dengan kuantitas yang dialokasikan per batch untuk item ke-${itemNum}.`);
                                isValid = false;
                                row.find('.serial-info-display').addClass('text-danger fw-bold').text('Periksa Serial!');
                                if (!firstInvalidElement) firstInvalidElement = row.find('.btn-pilih-batch-serial');
                            }

                        } catch (jsonError) {
                            errorMessages.push(`Data alokasi stok tidak valid untuk item ke-${itemNum}.`);
                            isValid = false;
                            if (!firstInvalidElement) firstInvalidElement = row.find('.btn-pilih-batch-serial');
                        }
                    }
                });

                const tipeTransaksi = $('#tipe_transaksi').val();
                const totalHarga = parseRupiah($('#total_harga').val());
                const uangBayar = parseRupiah($('#uang_bayar').val());
                const uangMuka = parseRupiah($('#uang_muka').val());

                if (tipeTransaksi === 'BIASA') {
                    if (!uangBayar && totalHarga > 0) { // Jika ada total belanja, uang bayar wajib
                        errorMessages.push('Uang bayar wajib diisi.');
                        isValid = false;
                        $('#uang_bayar').addClass('is-invalid');
                        if (!firstInvalidElement) firstInvalidElement = $('#uang_bayar');
                    } else if (uangBayar < totalHarga) {
                        errorMessages.push('Uang bayar kurang dari total belanja.');
                        isValid = false;
                        $('#uang_bayar').addClass('is-invalid');
                        if (!firstInvalidElement) firstInvalidElement = $('#uang_bayar');
                    } else {
                         $('#uang_bayar').removeClass('is-invalid');
                    }
                } else if (tipeTransaksi === 'PRE_ORDER') {
                    if (totalHarga > 0 && (!uangMuka || uangMuka <= 0)) {
                        errorMessages.push('Uang muka (DP) wajib diisi dan lebih dari 0 untuk Pre-Order.');
                        isValid = false;
                        $('#uang_muka').addClass('is-invalid');
                        if (!firstInvalidElement) firstInvalidElement = $('#uang_muka');
                    } else if (uangMuka > totalHarga) {
                        errorMessages.push('Uang muka (DP) tidak boleh melebihi total belanja.');
                        isValid = false;
                        $('#uang_muka').addClass('is-invalid');
                        if (!firstInvalidElement) firstInvalidElement = $('#uang_muka');
                    } else {
                         $('#uang_muka').removeClass('is-invalid');
                    }

                    if (!$('#estimasi_kirim_at').val() && totalHarga > 0) { // Wajib jika ada item
                        errorMessages.push('Estimasi kirim wajib diisi untuk Pre-Order.');
                        isValid = false;
                        $('#estimasi_kirim_at').addClass('is-invalid');
                        if (!firstInvalidElement) firstInvalidElement = $('#estimasi_kirim_at');
                    } else {
                         $('#estimasi_kirim_at').removeClass('is-invalid');
                    }

                    const sisaPO = Math.max(0, totalHarga - uangMuka);
                    if (sisaPO > 0 && uangBayar > 0 && uangBayar < sisaPO) {
                        errorMessages.push('Uang bayar pelunasan kurang dari sisa pembayaran Pre-Order.');
                        isValid = false;
                        $('#uang_bayar').addClass('is-invalid');
                        if (!firstInvalidElement) firstInvalidElement = $('#uang_bayar');
                    } else {
                         $('#uang_bayar').removeClass('is-invalid');
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                    let msgHtml = 'Terdapat kesalahan pada input Anda:<br><ul class="text-start ps-4">';
                    errorMessages.forEach(function(msg) { msgHtml += '<li>' + msg + '</li>'; });
                    msgHtml += '</ul>';

                    Swal.fire({
                        title: 'Validasi Gagal!', html: msgHtml, icon: 'error',
                        confirmButtonText: 'OK', customClass: { htmlContainer: 'text-start' }
                    }).then(() => {
                        if (firstInvalidElement && $(firstInvalidElement).is(':visible')) {
                            $('html, body').animate({ scrollTop: $(firstInvalidElement).offset().top - 150 }, 500, function() {
                                $(firstInvalidElement).focus();
                                if ($(firstInvalidElement).hasClass('select2-hidden-accessible')) {
                                    $(firstInvalidElement).select2('open');
                                }
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
        });
    </script>
@endpush