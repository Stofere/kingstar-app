@extends('layouts.app')

@section('title', isset($dataFromRetur) ? 'Buat PO Barang Pengganti' : 'Buat Pembelian Baru')

@push('styles')
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
    {{-- Judul dinamis --}}
    <h1 class="mb-4">@yield('title')</h1>

    {{-- Alert info jika ini adalah PO dari retur --}}
    @if(isset($dataFromRetur))
    <div class="alert alert-success mb-4">
        <h5 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i>Informasi PO Pengganti</h5>
        <p class="mb-0">Anda sedang membuat Purchase Order untuk barang pengganti dari nota retur <strong>{{ $dataFromRetur['nomor_retur_asal'] }}</strong>. Supplier dan item produk telah dikunci. Harga Beli akan di-set ke <strong>Rp 0</strong>.</p>
    </div>
    @endif

    <form action="{{ route('admin.pembelian.store') }}" method="POST" id="form-pembelian">
        @csrf
        {{-- Hidden input untuk melacak ID retur asal, jika ada --}}
        @if(isset($dataFromRetur))
            <input type="hidden" name="id_retur_asal" value="{{ $dataFromRetur['id_retur_asal'] }}">
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Informasi Pembelian</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Supplier --}}
                    <div class="col-md-6">
                        <label for="id_supplier" class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select class="form-select @error('id_supplier') is-invalid @enderror" id="id_supplier" name="id_supplier" required data-placeholder="Cari Supplier..."
                            {{ isset($dataFromRetur) ? 'disabled' : '' }}>
                            {{-- Option akan terisi otomatis --}}
                            @if(isset($dataFromRetur))
                                <option value="{{ $dataFromRetur['supplier_id'] }}" selected>{{ $dataFromRetur['supplier_text'] }}</option>
                            @elseif(old('id_supplier'))
                                 @php $oldSupplier = \App\Models\Supplier::find(old('id_supplier')); @endphp
                                 @if($oldSupplier)
                                 <option value="{{ $oldSupplier->id }}" selected>{{ $oldSupplier->nama}} {{ $oldSupplier->telepon ? '('.$oldSupplier->telepon.')' :'' }}></option>
                                 @else
                                 <option value=""></option>
                                 @endif
                            @else
                                <option value=""></option>
                            @endif
                        </select>
                        {{-- Jika disabled, kirim value via hidden input --}}
                        @if(isset($dataFromRetur))
                            <input type="hidden" name="id_supplier" value="{{ $dataFromRetur['supplier_id'] }}">
                        @endif
                        @error('id_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Tanggal Pembelian --}}
                    <div class="col-md-6">
                        <label for="tanggal_pembelian" class="form-label">Tanggal Pembelian <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal_pembelian') is-invalid @enderror" id="tanggal_pembelian" name="tanggal_pembelian" value="{{ old('tanggal_pembelian', date('Y-m-d')) }}" required>
                        @error('tanggal_pembelian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Nomor Pembelian Internal (dibuat khusus untuk retur) --}}
                    <div class="col-md-6">
                        <label for="nomor_pembelian" class="form-label">Nomor Pembelian</label>
                        <input type="text" class="form-control @error('nomor_pembelian') is-invalid @enderror"
                            id="nomor_pembelian" name="nomor_pembelian"
                            placeholder="Akan digenerate otomatis..."
                            readonly>
                        <div class="form-text">
                            Nomor akan dibuat otomatis oleh sistem.
                        </div>
                        @error('nomor_pembelian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Nomor Faktur Supplier --}}
                    <div class="col-md-6">
                        <label for="nomor_faktur_supplier" class="form-label">Nomor Faktur Supplier</label>
                        <input type="text" class="form-control @error('nomor_faktur_supplier') is-invalid @enderror" id="nomor_faktur_supplier" name="nomor_faktur_supplier" value="{{ old('nomor_faktur_supplier') }}" {{ isset($dataFromRetur) ? 'readonly' : '' }}>
                        @error('nomor_faktur_supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                     {{-- Status Pembelian (dikunci untuk retur) --}}
                    <div class="col-md-6">
                        <label for="status_pembelian" class="form-label">Status Pembelian <span class="text-danger">*</span></label>
                        <select class="form-select @error('status_pembelian') is-invalid @enderror" id="status_pembelian" name="status_pembelian" required {{ isset($dataFromRetur) ? 'disabled' : '' }}>
                            @if(isset($dataFromRetur))
                                <option value="BARANG_PENGGANTI_RETUR" selected>BARANG PENGGANTI RETUR</option>
                            @else
                                <option value="DRAFT" {{ old('status_pembelian', 'DRAFT') == 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                                <option value="DIPESAN" {{ old('status_pembelian') == 'DIPESAN' ? 'selected' : '' }}>DIPESAN</option>
                            @endif
                        </select>
                         @if(isset($dataFromRetur))
                            <input type="hidden" name="status_pembelian" value="BARANG_PENGGANTI_RETUR">
                        @endif
                        @error('status_pembelian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Status Pembayaran --}}
                    <div class="col-md-6">
                        <label for="status_pembayaran" class="form-label">Status Pembayaran <span class="text-danger">*</span></label>
                        <select class="form-select @error('status_pembayaran') is-invalid @enderror" id="status_pembayaran" name="status_pembayaran" required {{ isset($dataFromRetur) ? 'disabled' : '' }}>
                            @if(isset($dataFromRetur))
                                <option value="LUNAS" selected>LUNAS (Barang Pengganti)</option>
                            @else
                                <option value="BELUM_LUNAS" {{ old('status_pembayaran', 'BELUM_LUNAS') == 'BELUM_LUNAS' ? 'selected' : '' }}>BELUM LUNAS</option>
                                <option value="LUNAS" {{ old('status_pembayaran') == 'LUNAS' ? 'selected' : '' }}>LUNAS</option>
                                <option value="JATUH_TEMPO" {{ old('status_pembayaran') == 'JATUH_TEMPO' ? 'selected' : '' }}>JATUH TEMPO</option>
                            @endif
                        </select>
                        @if(isset($dataFromRetur))
                            <input type="hidden" name="status_pembayaran" value="LUNAS">
                        @endif
                        @error('status_pembayaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                     {{-- Tanggal Bayar (Opsional, mungkin muncul jika LUNAS) --}}
                     <div class="col-md-6" id="tanggal-bayar-group" style="{{ (isset($dataFromRetur) || old('status_pembayaran') == 'LUNAS') ? '' : 'display: none;' }}">
                        <label for="dibayar_at" class="form-label">Tanggal Bayar</label>
                        <input type="date" class="form-control @error('dibayar_at') is-invalid @enderror" id="dibayar_at" name="dibayar_at" value="{{ old('dibayar_at', (isset($dataFromRetur) ? date('Y-m-d') : null)) }}" {{ isset($dataFromRetur) ? 'readonly' : '' }}>
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
                    <table class="table table-bordered" id="detail-pembelian-table">
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
                            {{-- Baris akan diisi oleh JS --}}
                            {{-- Handle old input if not from retur --}}
                            @if(!isset($dataFromRetur) && old('details'))
                                 @foreach(old('details') as $index => $detail)
                                     <tr class="detail-item-row">
                                         <td>
                                             <select class="form-select product-select @error('details.'.$index.'.id_produk') is-invalid @enderror" name="details[{{ $index }}][id_produk]" required data-placeholder="Cari Produk...">
                                                 @if(isset($detail['id_produk']))
                                                     @php $oldProduct = \App\Models\Produk::find($detail['id_produk']); @endphp
                                                     @if($oldProduct)
                                                     <option value="{{ $oldProduct->id }}" selected>{{ $oldProduct->nama }} ({{ $oldProduct->kode_produk }})</option>
                                                     @endif
                                                 @endif
                                             </select>
                                         </td>
                                         <td>
                                             <input type="number" class="form-control item-jumlah text-end @error('details.'.$index.'.jumlah') is-invalid @enderror" name="details[{{ $index }}][jumlah]" value="{{ $detail['jumlah'] ?? 1 }}" required min="1" step="1">
                                         </td>
                                         <td>
                                             <input type="number" class="form-control item-harga text-end @error('details.'.$index.'.harga_beli') is-invalid @enderror" name="details[{{ $index }}][harga_beli]" value="{{ $detail['harga_beli'] ?? 0 }}" required min="0" step="0.01">
                                         </td>
                                         <td>
                                             <span class="item-subtotal fw-bold">Rp 0</span>
                                         </td>
                                         <td class="text-center">
                                             <button type="button" class="btn btn-danger btn-sm delete-item-btn" title="Hapus Item"><i class="bi bi-trash"></i></button>
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
                {{-- Tombol Tambah Item di-disable jika dari retur --}}
                <button type="button" class="btn btn-success btn-sm mt-2" id="add-item-btn" {{ isset($dataFromRetur) ? 'style="display:none;"' : '' }}>
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
                    <textarea class="form-control @error('catatan') is-invalid @enderror" id="catatan" name="catatan" rows="3">{{ old('catatan', (isset($dataFromRetur) ? 'Barang pengganti untuk nota retur: ' . $dataFromRetur['nomor_retur_asal'] : '')) }}</textarea>
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
            let dataFromRetur = @json($dataFromRetur ?? null);
            let itemIndex = {{ (isset($dataFromRetur) || !old('details')) ? 0 : count(old('details')) }};

            // Fungsi untuk inisialisasi Select2 Supplier (dengan AJAX) sama seperti produk
            $('#id_supplier').select2({
                theme: "bootstrap-5",
                width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
                placeholder: $(this).data('placeholder'),
                allowClear: {{ isset($dataFromRetur) ? 'false' : 'true' }},
                @if(isset($dataFromRetur))
                // Jika dari retur dan supplier sudah ada, tidak perlu AJAX
                // Namun, jika ingin tetap bisa search meski sudah ada value, jangan disable AJAX
                @else
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
                @endif
            });

            function fetchAndSetPoNumber() {
                const selectedDate = $('#tanggal_pembelian').val();
                const nomorInput = $('#nomor_pembelian');
                const isReturPo = dataFromRetur !== null;
                const prefix = isReturPo ? 'PO-RTR' : 'PO'; // Contoh prefix beda

                if (nomorInput.is('[readonly]')) { // Selalu readonly sekarang
                    nomorInput.val('Memuat...');
                    $.ajax({
                        url: "{{ route('admin.ajax.pembelian.generate_number') }}",
                        type: 'GET',
                        data: { tanggal: selectedDate }, // Kirim tanggal yang dipilih
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                nomorInput.val(response.nomor_pembelian.replace('PO-', prefix + '-')); // Ganti prefix jika retur
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
            // itemIndex sudah diinisialisasi di atas
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
             // Hanya inisialisasi jika tidak dari retur, karena retur akan dihandle populateFormFromRetur
             if (!dataFromRetur) {
                $('.product-select').each(function() {
                 initializeProductSelect2(this);
             });

            // Hitung total awal saat halaman dimuat (jika ada old input)
            calculateTotals();

            // Tampilkan/sembunyikan tanggal bayar berdasarkan status pembayaran
            function handleTanggalBayarVisibility() {
                if ($('#status_pembayaran').val() === 'LUNAS' || (dataFromRetur && $('#status_pembayaran').val() === 'LUNAS (Barang Pengganti)')) {
                     $('#tanggal-bayar-group').slideDown();
                     if (dataFromRetur && !$('#dibayar_at').val()) { // Jika dari retur dan tanggal bayar kosong, set ke hari ini
                        $('#dibayar_at').val(new Date().toISOString().slice(0,10));
                     }
                 } else {
                     $('#tanggal-bayar-group').slideUp();
                     if (!dataFromRetur) { // Jangan kosongkan jika dari retur karena sudah di-set
                        $('#dibayar_at').val('');
                     }
                 }
            }

            $('#status_pembayaran').on('change', handleTanggalBayarVisibility);
            handleTanggalBayarVisibility(); // Panggil saat load
            } // Penutup untuk if (!dataFromRetur)


            // =======================================================
            // ## Logika Baru untuk Mengisi Form dari Data Retur    ##
            // =======================================================
            function populateFormFromRetur() {
                if (!dataFromRetur) return;

                // 1. Tambahkan satu baris item (secara manual, bukan klik tombol)
                let template = $('#detail-item-template').html();
                let newRowHtml = template.replace(/__INDEX__/g, itemIndex); // itemIndex akan 0
                $('#detail-pembelian-body').append(newRowHtml);
                const newRow = $('#detail-pembelian-body tr:last');

                // 2. Isi data produk di baris tersebut
                const produkSelect = newRow.find('.product-select');
                var option = new Option(dataFromRetur.produk_text, dataFromRetur.produk_id, true, true);
                produkSelect.append(option).trigger('change');

                // 3. Kunci field produk
                produkSelect.prop('disabled', true);
                // Kirim value via hidden input (pastikan name-nya benar)
                newRow.find('td:first').append(`<input type="hidden" name="details[${itemIndex}][id_produk]" value="${dataFromRetur.produk_id}">`);

                // 4. Isi jumlah dan harga, lalu kunci
                const jumlahInput = newRow.find('.item-jumlah');
                const hargaInput = newRow.find('.item-harga');

                jumlahInput.val(dataFromRetur.qty).prop('readonly', true);
                hargaInput.val(0).prop('readonly', true); // Harga 0 untuk pengganti

                // 5. Kunci tombol hapus
                newRow.find('.delete-item-btn').remove();
                itemIndex++; // Increment index
                calculateTotals();
            }
            populateFormFromRetur();
        });
    </script>
@endpush