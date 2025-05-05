@extends('layouts.app')

@section('title', 'Buat Pembelian Baru')

@push('styles')
    {{-- Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    {{-- Optional: Select2 Bootstrap 5 Theme --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        /* Style untuk tombol hapus baris */
        .delete-item-btn { cursor: pointer; }
        /* Pastikan select2 tampil benar di dalam tabel */
        .select2-container--bootstrap-5 .select2-selection--single { height: calc(1.5em + 0.75rem + 2px); }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding: 0.375rem 0.75rem;}
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow { height: calc(1.5em + 0.75rem); }
        /* Align number input ke kanan */
        input[type=number].text-end { text-align: right; }
    </style>
@endpush

@section('content')
<div class="container">
    <h1 class="mb-4">Buat Pembelian Baru</h1>

    <form action="{{ route('admin.pembelian.store') }}" method="POST" id="form-pembelian">
        @csrf
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Informasi Pembelian</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Supplier --}}
                    <div class="col-md-6">
                        <label for="id_supplier" class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select class="form-select @error('id_supplier') is-invalid @enderror" id="id_supplier" name="id_supplier" required data-placeholder="Cari Supplier...">
                            <option value=""></option>
                            @if(old('id_supplier'))
                                @php
                                    $oldSupplier = \App\Models\Supplier::find(old('id_supplier'));
                                @endphp
                                @if($oldSupplier)
                                <option value="{{ $oldSupplier->id }}" selected>{{ $oldSupplier->nama}} {{ $oldSupplier->telepon ? '('.$oldSupplier->telepon.')' :'' }}></option>
                                @endif
                            @endif
                        </select>
                        @error('id_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Tanggal Pembelian --}}
                    <div class="col-md-6">
                        <label for="tanggal_pembelian" class="form-label">Tanggal Pembelian <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal_pembelian') is-invalid @enderror" id="tanggal_pembelian" name="tanggal_pembelian" value="{{ old('tanggal_pembelian', date('Y-m-d')) }}" required>
                        @error('tanggal_pembelian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Nomor Pembelian Internal --}}
                    <div class="col-md-6">
                        <label for="nomor_pembelian" class="form-label">Nomor Pembelian</label>
                        <input type="text" class="form-control @error('nomor_pembelian') is-invalid @enderror"
                            id="nomor_pembelian" name="nomor_pembelian"
                            value="{{ old('nomor_pembelian') }}"
                            placeholder="Akan digenerate otomatis..."
                            @if(Auth::user()->role !== 'ADMIN') readonly @endif> {{-- Kondisi readonly --}}
                        <div class="form-text">
                            @if(Auth::user()->role === 'ADMIN')
                                Kosongkan untuk nomor otomatis atau isi manual (Format: PO-{{ config('app.branch_code', 'XXX') }}-ddmmyy-XXX).
                            @else
                                Nomor akan dibuat otomatis oleh sistem.
                            @endif
                        </div>
                        @error('nomor_pembelian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Nomor Faktur Supplier --}}
                    <div class="col-md-6">
                        <label for="nomor_faktur_supplier" class="form-label">Nomor Faktur Supplier</label>
                        <input type="text" class="form-control @error('nomor_faktur_supplier') is-invalid @enderror" id="nomor_faktur_supplier" name="nomor_faktur_supplier" value="{{ old('nomor_faktur_supplier') }}">
                        @error('nomor_faktur_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                     {{-- Status Pembelian --}}
                    <div class="col-md-6">
                        <label for="status_pembelian" class="form-label">Status Pembelian <span class="text-danger">*</span></label>
                        <select class="form-select @error('status_pembelian') is-invalid @enderror" id="status_pembelian" name="status_pembelian" required>
                            <option value="DRAFT" {{ old('status_pembelian', 'DRAFT') == 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                            <option value="DIPESAN" {{ old('status_pembelian') == 'DIPESAN' ? 'selected' : '' }}>DIPESAN</option>
                            {{-- Status lain mungkin tidak relevan saat create --}}
                        </select>
                        @error('status_pembelian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Status Pembayaran --}}
                    <div class="col-md-6">
                        <label for="status_pembayaran" class="form-label">Status Pembayaran <span class="text-danger">*</span></label>
                        <select class="form-select @error('status_pembayaran') is-invalid @enderror" id="status_pembayaran" name="status_pembayaran" required>
                            <option value="BELUM_LUNAS" {{ old('status_pembayaran', 'BELUM_LUNAS') == 'BELUM_LUNAS' ? 'selected' : '' }}>BELUM LUNAS</option>
                            <option value="LUNAS" {{ old('status_pembayaran') == 'LUNAS' ? 'selected' : '' }}>LUNAS</option>
                            <option value="JATUH_TEMPO" {{ old('status_pembayaran') == 'JATUH_TEMPO' ? 'selected' : '' }}>JATUH TEMPO</option>
                        </select>
                        @error('status_pembayaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                     {{-- Tanggal Bayar (Opsional, mungkin muncul jika LUNAS) --}}
                     <div class="col-md-6" id="tanggal-bayar-group" style="{{ old('status_pembayaran') == 'LUNAS' ? '' : 'display: none;' }}">
                        <label for="dibayar_at" class="form-label">Tanggal Bayar</label>
                        <input type="date" class="form-control @error('dibayar_at') is-invalid @enderror" id="dibayar_at" name="dibayar_at" value="{{ old('dibayar_at') }}">
                        @error('dibayar_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Detail Item Pembelian</h5>
            </div>
            <div class="card-body">
                 @if ($errors->has('details') || $errors->has('details.*'))
                    <div class="alert alert-danger">
                        Terdapat kesalahan pada input detail item. Mohon periksa kembali.
                        <ul>
                             @foreach ($errors->get('details.*') as $key => $messages)
                                @foreach($messages as $message)
                                    <li>{{ $message }} (Baris: {{ (int) filter_var($key, FILTER_SANITIZE_NUMBER_INT) + 1 }})</li>
                                @endforeach
                             @endforeach
                        </ul>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="detail-pembelian-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Produk <span class="text-danger">*</span></th>
                                <th style="width: 15%;">Jumlah <span class="text-danger">*</span></th>
                                <th style="width: 20%;">Harga Beli Satuan <span class="text-danger">*</span></th>
                                <th style="width: 20%;">Subtotal</th>
                                <th style="width: 5%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detail-pembelian-body">
                            {{-- Baris detail akan ditambahkan oleh JS --}}
                            {{-- Jika ada old input, render baris lama --}}
                            @if(old('details'))
                                @foreach(old('details') as $index => $detail)
                                    <tr class="detail-item-row">
                                        <td>
                                            <select class="form-select product-select @error('details.'.$index.'.id_produk') is-invalid @enderror" name="details[{{ $index }}][id_produk]" required data-placeholder="Cari Produk...">
                                                {{-- Opsi produk lama akan diisi oleh JS atau perlu logic tambahan di sini --}}
                                                @if(isset($detail['id_produk']))
                                                    <option value="{{ $detail['id_produk'] }}" selected>{{ \App\Models\Produk::find($detail['id_produk'])->nama ?? 'Produk tidak ditemukan' }}</option>
                                                @endif
                                            </select>
                                            {{-- @error('details.'.$index.'.id_produk') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror --}}
                                        </td>
                                        <td>
                                            <input type="number" class="form-control item-jumlah text-end @error('details.'.$index.'.jumlah') is-invalid @enderror" name="details[{{ $index }}][jumlah]" value="{{ $detail['jumlah'] ?? 1 }}" required min="1" step="1">
                                            {{-- @error('details.'.$index.'.jumlah') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror --}}
                                        </td>
                                        <td>
                                            <input type="number" class="form-control item-harga text-end @error('details.'.$index.'.harga_beli') is-invalid @enderror" name="details[{{ $index }}][harga_beli]" value="{{ $detail['harga_beli'] ?? 0 }}" required min="0" step="0.01">
                                            {{-- @error('details.'.$index.'.harga_beli') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror --}}
                                        </td>
                                        <td>
                                            <span class="item-subtotal fw-bold">Rp 0</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm delete-item-btn" title="Hapus Item">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold border-0">Grand Total</td>
                                <td colspan="2" class="fw-bold border-0"><span id="grand-total">Rp 0</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button type="button" class="btn btn-success btn-sm mt-2" id="add-item-btn">
                    <i class="bi bi-plus-circle"></i> Tambah Item
                </button>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
             <div class="card-header bg-light">
                <h5 class="mb-0">Informasi Tambahan</h5>
            </div>
            <div class="card-body">
                 {{-- Catatan --}}
                <div class="mb-3">
                    <label for="catatan" class="form-label">Catatan</label>
                    <textarea class="form-control @error('catatan') is-invalid @enderror" id="catatan" name="catatan" rows="3">{{ old('catatan') }}</textarea>
                    @error('catatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>


        <div class="mt-3 text-end">
            <a href="{{ route('admin.pembelian.index') }}" class="btn btn-secondary me-2">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan Pembelian
            </button>
        </div>

    </form>

    {{-- Template untuk baris detail item (hidden) --}}
    <template id="detail-item-template">
        <tr class="detail-item-row">
            <td>
                <select class="form-select product-select" name="details[__INDEX__][id_produk]" required data-placeholder="Cari Produk...">
                    <option value=""></option> {{-- Option kosong untuk Select2 placeholder --}}
                </select>
            </td>
            <td>
                <input type="number" class="form-control item-jumlah text-end" name="details[__INDEX__][jumlah]" value="1" required min="1" step="1">
            </td>
            <td>
                <input type="number" class="form-control item-harga text-end" name="details[__INDEX__][harga_beli]" value="0" required min="0" step="0.01">
            </td>
            <td>
                <span class="item-subtotal fw-bold">Rp 0</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm delete-item-btn" title="Hapus Item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </template>

</div>
@endsection

@push('scripts')

    {{-- InputMask or AutoNumeric (Optional, for number formatting) --}}
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.6.0/autoNumeric.min.js"></script> --}}

    <script>
        $(document).ready(function() {
            // Fungsi untuk inisialisasi Select2 Supplier (dengan AJAX) sama seperti produk
            $('#id_supplier').select2({
                theme: "bootstrap-5",
                width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
                placeholder: $(this).data('placeholder'),
                allowClear: true,
                ajax: {
                    url: "{{ route('admin.ajax.supplier.search') }}", // Route baru untuk supplier
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term, // query pencarian
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.items,
                            pagination: {
                                more: (params.page * 15) < data.total_count // Sesuaikan limit jika perlu
                            }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0, // Bisa 0 jika ingin langsung menampilkan list saat diklik
            });

            function fetchAndSetPoNumber() {
                const selectedDate = $('#tanggal_pembelian').val();
                const nomorInput = $('#nomor_pembelian');

                // Hanya fetch jika input readonly (bukan admin yang mungkin sedang input manual)
                if (nomorInput.is('[readonly]')) {
                    nomorInput.val('Memuat...'); // Tampilkan loading
                    $.ajax({
                        url: "{{ route('admin.ajax.pembelian.generate_number') }}",
                        type: 'GET',
                        data: { tanggal: selectedDate }, // Kirim tanggal yang dipilih
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                nomorInput.val(response.nomor_pembelian);
                            } else {
                                nomorInput.val('Error!');
                                // Tampilkan pesan error jika perlu
                                console.error(response.message || 'Gagal mengambil nomor PO');
                            }
                        },
                        error: function(xhr, status, error) {
                            nomorInput.val('Error!');
                            console.error('AJAX Error:', status, error);
                        }
                    });
                } else {
                    // Jika admin bisa edit, mungkin set placeholder saja
                    nomorInput.attr('placeholder', 'Format: PO-{{ config('app.branch_code', 'XXX') }}-' + formatDatePlaceholder(selectedDate) + '-XXX');
                }
            }

            // Helper untuk format tanggal placeholder ddmmyy
            function formatDatePlaceholder(dateString) {
                if (!dateString) return 'ddmmyy';
                try {
                    const date = new Date(dateString);
                    const d = String(date.getDate()).padStart(2, '0');
                    const m = String(date.getMonth() + 1).padStart(2, '0'); // Month is 0-indexed
                    const y = String(date.getFullYear()).slice(-2);
                    return d + m + y;
                } catch (e) {
                    return 'ddmmyy';
                }
            }

            // Panggil saat halaman dimuat
            fetchAndSetPoNumber();

            // Panggil saat tanggal pembelian diubah
            $('#tanggal_pembelian').on('change', function() {
                fetchAndSetPoNumber();
            });
            // Fungsi untuk inisialisasi Select2 Produk (dengan AJAX)
            function initializeProductSelect2(element) {
                $(element).select2({
                    theme: "bootstrap-5",
                    width: '100%',
                    placeholder: $(element).data('placeholder'),
                    allowClear: true,
                    ajax: {
                        // Ganti URL ini dengan route endpoint pencarian produk Anda
                        url: "{{ route('admin.ajax.produk.search') }}", // Contoh nama route
                        dataType: 'json',
                        delay: 250, // Jeda sebelum request
                        data: function(params) {
                            return {
                                q: params.term, // query pencarian
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.items, // data.items harus berisi array [{id: x, text: 'Nama Produk (Kode)'}, ...]
                                pagination: {
                                    more: (params.page * 10) < data.total_count // data.total_count dari response
                                }
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1, // Minimal karakter sebelum mencari
                    // Optional: template untuk hasil pencarian & seleksi
                    // templateResult: formatRepo,
                    // templateSelection: formatRepoSelection
                });
            }

            // Fungsi untuk menghitung subtotal dan grand total
            function calculateTotals() {
                let grandTotal = 0;
                $('#detail-pembelian-body .detail-item-row').each(function() {
                    let row = $(this);
                    let jumlah = parseFloat(row.find('.item-jumlah').val()) || 0;
                    let harga = parseFloat(row.find('.item-harga').val()) || 0;
                    let subtotal = jumlah * harga;

                    // Format subtotal sebagai Rupiah
                    row.find('.item-subtotal').text('Rp ' + subtotal.toLocaleString('id-ID'));
                    grandTotal += subtotal;
                });
                // Format grand total sebagai Rupiah
                $('#grand-total').text('Rp ' + grandTotal.toLocaleString('id-ID'));
            }

            // Tambah Item
            let itemIndex = {{ old('details') ? count(old('details')) : 0 }}; // Mulai index dari jumlah item lama
            $('#add-item-btn').on('click', function() {
                let template = $('#detail-item-template').html();
                // Ganti placeholder index dengan index unik
                let newRowHtml = template.replace(/__INDEX__/g, itemIndex);
                $('#detail-pembelian-body').append(newRowHtml);

                // Inisialisasi Select2 untuk baris baru
                initializeProductSelect2($('#detail-pembelian-body tr:last .product-select'));

                itemIndex++; // Increment index untuk baris berikutnya
                calculateTotals(); // Hitung ulang total
            });

            // Hapus Item
            $('#detail-pembelian-body').on('click', '.delete-item-btn', function() {
                $(this).closest('.detail-item-row').remove();
                calculateTotals(); // Hitung ulang total
            });

            // Hitung Total saat Jumlah atau Harga Berubah
            $('#detail-pembelian-body').on('input change', '.item-jumlah, .item-harga', function() {
                calculateTotals();
            });

            // Inisialisasi Select2 untuk baris yang sudah ada (jika ada dari old input)
             $('.product-select').each(function() {
                 initializeProductSelect2(this);
             });

            // Hitung total awal saat halaman dimuat (jika ada old input)
            calculateTotals();

            // Tampilkan/sembunyikan tanggal bayar berdasarkan status pembayaran
             $('#status_pembayaran').on('change', function() {
                if ($(this).val() === 'LUNAS') {
                    $('#tanggal-bayar-group').slideDown();
                } else {
                    $('#tanggal-bayar-group').slideUp();
                    $('#dibayar_at').val(''); // Kosongkan tanggal jika tidak lunas
                }
            }).trigger('change'); // Trigger change saat load untuk set state awal

        });
    </script>
@endpush