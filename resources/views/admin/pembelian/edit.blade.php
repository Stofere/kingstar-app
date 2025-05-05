@extends('layouts.app')

@section('title', 'Edit Pembelian: ' . ($pembelian->nomor_pembelian ?? $pembelian->id))

@push('styles')
    {{-- Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        /* Style sama seperti create.blade.php */
        .delete-item-btn { cursor: pointer; }
        .select2-container--bootstrap-5 .select2-selection--single { height: calc(1.5em + 0.75rem + 2px); }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding: 0.375rem 0.75rem;}
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow { height: calc(1.5em + 0.75rem); }
        input[type=number].text-end { text-align: right; }
    </style>
@endpush

@section('content')
<div class="container">
    <h1 class="mb-4">Edit Pembelian: {{ $pembelian->nomor_pembelian ?? $pembelian->id }}</h1>

    {{-- Form utama --}}
    <form action="{{ route('admin.pembelian.update', $pembelian->id) }}" method="POST" id="form-pembelian">
        @csrf
        @method('PUT') {{-- Method spoofing untuk update --}}

        {{-- Include partial form --}}
        {{-- Variabel $pembelian dan $suppliers otomatis tersedia dari controller --}}
        @include('admin.pembelian.form')

        {{-- Tombol Aksi --}}
        <div class="mt-3 text-end">
            <a href="{{ route('admin.pembelian.index') }}" class="btn btn-secondary me-2">Batal</a>
            <button type="submit" class="btn btn-primary" {{ isset($pembelian) && !in_array($pembelian->status_pembelian, ['DRAFT', 'DIPESAN']) ? 'disabled' : '' }}> {{-- Disable jika status tidak memungkinkan edit --}}
                <i class="bi bi-save me-1"></i> Update Pembelian
            </button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
    {{-- InputMask or AutoNumeric (Optional) --}}

    {{-- Pindahkan JS dari create.blade.php ke file terpisah atau copy paste ke sini --}}
    {{-- Contoh jika di-copy paste: --}}
    <script>
        $(document).ready(function() {
            // --- SEMUA KODE JAVASCRIPT DARI create.blade.php ---
            // (Inisialisasi Select2 Supplier, initializeProductSelect2, calculateTotals,
            //  event listener add-item-btn, delete-item-btn, input jumlah/harga,
            //  inisialisasi Select2 awal, calculateTotals awal, event listener status_pembayaran)
            // --- LETAKKAN DI SINI ---

            // Inisialisasi Select2 untuk Supplier
            $('#id_supplier').select2({
                theme: "bootstrap-5",
                width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
                placeholder: $(this).data('placeholder'),
            });

            // Fungsi untuk inisialisasi Select2 Produk (dengan AJAX)
            function initializeProductSelect2(element) {
                $(element).select2({
                    theme: "bootstrap-5",
                    width: '100%',
                    placeholder: $(element).data('placeholder'),
                    allowClear: true,
                    ajax: {
                        url: "{{ route('admin.ajax.produk.search') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { q: params.term, page: params.page || 1 };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.items,
                                pagination: { more: (params.page * 15) < data.total_count } // Sesuaikan limit jika perlu
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1,
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
                    row.find('.item-subtotal').text('Rp ' + subtotal.toLocaleString('id-ID'));
                    grandTotal += subtotal;
                });
                $('#grand-total').text('Rp ' + grandTotal.toLocaleString('id-ID'));
            }

            // Tambah Item
            let itemIndex = $('#detail-pembelian-body tr').length; // Hitung index dari baris yang sudah ada
            $('#add-item-btn').on('click', function() {
                let template = $('#detail-item-template').html();
                let newRowHtml = template.replace(/__INDEX__/g, itemIndex);
                $('#detail-pembelian-body').append(newRowHtml);
                initializeProductSelect2($('#detail-pembelian-body tr:last .product-select'));
                itemIndex++;
                calculateTotals();
            });

            // Hapus Item
            $('#detail-pembelian-body').on('click', '.delete-item-btn', function() {
                // Optional: Jika ingin menandai untuk dihapus saat update, bukan langsung remove
                // $(this).closest('tr').hide();
                // $(this).closest('tr').find('.mark-for-delete').val('1'); // Tambah input hidden jika perlu
                // Jika langsung remove:
                $(this).closest('.detail-item-row').remove();
                calculateTotals();
            });

            // Hitung Total saat Jumlah atau Harga Berubah
            $('#detail-pembelian-body').on('input change', '.item-jumlah, .item-harga', function() {
                calculateTotals();
            });

            // Inisialisasi Select2 untuk baris yang sudah ada (dari data $pembelian)
             $('#detail-pembelian-body .product-select').each(function() {
                 initializeProductSelect2(this);
             });

            // Hitung total awal saat halaman dimuat
            calculateTotals();

            // Tampilkan/sembunyikan tanggal bayar
             $('#status_pembayaran').on('change', function() {
                if ($(this).val() === 'LUNAS') {
                    $('#tanggal-bayar-group').slideDown();
                } else {
                    $('#tanggal-bayar-group').slideUp();
                    $('#dibayar_at').val('');
                }
            }).trigger('change');

        });
    </script>
@endpush