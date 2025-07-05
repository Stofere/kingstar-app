@extends('layouts.app')

@section('title', 'Buat Retur Pembelian ke Supplier')

@push('styles')
    <style>
        .required-label::after { content: " *"; color: red; }
        .item-retur-card { border: 1px solid #ddd; border-left: 5px solid #fd7e14; }
        .select2-container--bootstrap-5 .select2-dropdown { z-index: 1061; } /* Agar dropdown muncul di atas elemen lain */
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Form Retur Pembelian ke Supplier</h1>

    <form action="{{ route('admin.retur_pembelian.store') }}" method="POST" id="form-retur-pembelian">
        @csrf
        {{-- BAGIAN 1: INFORMASI UMUM --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">Informasi Umum Retur Pembelian</div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger pb-0">
                        <p><strong>Terdapat kesalahan pada input Anda:</strong></p>
                        <ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nomor Retur Pembelian:</label>
                        <input type="text" class="form-control" value="(Otomatis)" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="tanggal_retur" class="form-label required-label">Tanggal Retur:</label>
                        <input type="datetime-local" class="form-control" id="tanggal_retur" name="tanggal_retur" value="{{ old('tanggal_retur', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="id_supplier_tujuan" class="form-label required-label">Supplier Tujuan Retur:</label>
                        <select class="form-select select2-supplier" id="id_supplier_tujuan" name="id_supplier_tujuan" data-placeholder="Pilih Supplier..." required>
                            <option></option> {{-- Option kosong untuk placeholder --}}
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN 2: ITEM YANG AKAN DIRETUR --}}
        <div class="card shadow-sm">
            <div class="card-header bg-warning">
                <h5 class="mb-0 text-dark"><i class="bi bi-box-seam me-2"></i>Item Batch yang Akan Diretur</h5>
            </div>
            <div class="card-body">
                <div id="item-retur-container">
                    {{-- Item retur akan ditambahkan di sini oleh JavaScript --}}
                </div>
                <button type="button" class="btn btn-success mt-2" id="btn-tambah-item" disabled>
                    <i class="bi bi-plus-circle-fill me-1"></i> Tambah Item/Batch
                </button>
                 <hr>
                <div class="mt-3">
                    <label for="catatan_global_retur_pembelian" class="form-label">Catatan Global Retur ke Supplier (Opsional):</label>
                    <textarea class="form-control" id="catatan_global_retur_pembelian" name="catatan_global_retur_pembelian" rows="2">{{ old('catatan_global_retur_pembelian') }}</textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.retur_pembelian.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger"><i class="bi bi-arrow-return-right me-1"></i> Proses Retur ke Supplier</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let itemCounter = 0;
    const alasanOptions = @json($alasanReturOptions ?? []);
    const tindakanOptions = @json($tindakanLanjutSupplierOptions ?? []);

    // Inisialisasi Select2 untuk Supplier
    $('.select2-supplier').select2({
        theme: "bootstrap-5",
        width: '100%',
        ajax: {
            url: "{{ route('admin.ajax.supplier.search') }}", // <-- Gunakan route yang benar
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term, page: params.page || 1 };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                // Pastikan format respons sesuai dengan yang diharapkan Select2
                return {
                    results: data.items,
                    pagination: data.pagination
                };
            },
            cache: true
        }
    }).on('change', function() {
        if ($(this).val()) {
            $('#btn-tambah-item').prop('disabled', false).removeClass('btn-secondary').addClass('btn-success');
        } else {
            $('#btn-tambah-item').prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
            $('#item-retur-container').empty(); 
        }
    });

    // Fungsi untuk membuat HTML item retur baru
    function createItemReturHtml(index) {
        let alasanSelectHtml = '<option value="">Pilih Alasan...</option>';
        for (const [key, value] of Object.entries(alasanOptions)) {
            alasanSelectHtml += `<option value="${key}">${value}</option>`;
        }
        
        let tindakanSelectHtml = '<option value="">Pilih Tindakan...</option>';
        for (const [key, value] of Object.entries(tindakanOptions)) {
            tindakanSelectHtml += `<option value="${key}">${value}</option>`;
        }
        
        return `
            <div class="card item-retur-card shadow-sm mb-3" data-index="${index}">
                <div class="card-body">
                    <button type="button" class="btn-close float-end btn-hapus-item" aria-label="Close"></button>
                    <input type="hidden" name="items_retur[${index}][id_produk_retur]" class="id_produk_retur_hidden">

                    <div class="mb-3">
                        <label class="form-label required-label">Pilih Batch Stok:</label>
                        <select class="form-select select2-batch-item" name="items_retur[${index}][id_stok_barang]" data-placeholder="Cari berdasarkan ID Batch, Nama Produk, atau Supplier..." required></select>
                        <small class="form-text text-muted" id="info-batch-${index}">Pilih batch untuk melihat detail.</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label required-label">Jumlah Diretur:</label>
                            <input type="number" name="items_retur[${index}][jumlah_retur]" class="form-control jumlah-retur-input" min="1" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label required-label">Alasan Retur:</label>
                            <select class="form-select" name="items_retur[${index}][alasan_retur]" required>${alasanSelectHtml}</select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label required-label">Tindak Lanjut Diharapkan dari Supplier:</label>
                            <select class="form-select" name="items_retur[${index}][tindakan_lanjut_supplier]" required>${tindakanSelectHtml}</select>
                        </div>
                    </div>

                    <div class="mb-3 serial-selection-area" style="display: none;">
                        <label class="form-label">Pilih Nomor Seri yang Diretur (<span class="selected-serial-count">0</span> / <span class="max-serial-select">0</span>):</label>
                        <div class="serial-checkbox-list row row-cols-2 row-cols-md-4 g-2"></div>
                    </div>
                    
                    <div>
                        <label class="form-label">Catatan untuk Supplier (Item ini):</label>
                        <textarea class="form-control" name="items_retur[${index}][catatan_ke_supplier_item]" rows="1"></textarea>
                    </div>
                </div>
            </div>`;
    }

    // Event handler untuk tombol "Tambah Item/Batch"
    $('#btn-tambah-item').on('click', function() {
        itemCounter++;
        const newItemHtml = createItemReturHtml(itemCounter);
        $('#item-retur-container').append(newItemHtml);
        initializeSelect2ForBatch(itemCounter);
    });

    // Event handler untuk tombol hapus item
    $('#item-retur-container').on('click', '.btn-hapus-item', function() {
        $(this).closest('.item-retur-card').remove();
    });

    function initializeSelect2ForBatch(index) {
        // ambil ID supplier yang sedang dipilih di form utama
        const idSupplier = $('#id_supplier_tujuan').val();
        $(`select[name="items_retur[${index}][id_stok_barang]"]`).select2({
            theme: "bootstrap-5",
            width: '100%',
            ajax: {
                url: "{{ route('admin.retur_pembelian.ajax.search_batch_stok') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    // Kirim ID supplier yang sudah dipilih bersama dengan keyword pencarian
                    return { 
                        q: params.term, 
                        id_supplier: idSupplier, // <-- KIRIM IDny SUPPLIER KE BACKEND
                        page: params.page || 1 
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: { more: (params.page * 15) < data.total_count }
                    };
                },
                cache: true
            }
        }).on('select2:select', function(e) {
            const data = e.params.data;
            const card = $(this).closest('.item-retur-card');
            card.find(`#info-batch-${index}`).html(`Supplier: <strong>${data.nama_supplier}</strong> | Sisa Stok Batch: <strong>${data.sisa_stok_batch}</strong>`);
            card.find('.jumlah-retur-input').attr('max', data.sisa_stok_batch).val(1).trigger('change');
            card.find('.id_produk_retur_hidden').val(data.id_produk);
            if(data.memiliki_serial){
                loadSerialsForBatch(card, data.id);
            } else {
                card.find('.serial-selection-area').hide();
            }
        });
    }

    // ### TAMBAHAN: Reset item retur jika supplier diubah ###
    $('#id_supplier_tujuan').on('change', function() {
        // Hapus semua item yang sudah ditambahkan jika supplier diubah
        // Ini untuk mencegah Admin meretur batch dari supplier A ke supplier B
        $('#item-retur-container').empty();
        // Anda bisa tambahkan kembali item pertama secara otomatis jika diinginkan
        // $('#btn-tambah-item').trigger('click');
    });

    function loadSerialsForBatch(card, idStokBarang) {
        const serialArea = card.find('.serial-selection-area');
        const serialList = card.find('.serial-checkbox-list');
        serialList.html('<small class="text-muted">Memuat...</small>');
        serialArea.show();

        $.ajax({
            url: "{{ route('admin.retur_pembelian.ajax.get_serials_from_batch') }}",
            data: { id_stok_barang: idStokBarang },
            success: function(response) {
                serialList.empty();
                if(response.success && response.serials.length > 0) {
                    const index = card.data('index');
                    response.serials.forEach(serial => {
                        const uniqueId = `serial-${index}-${serial.replace(/[^a-zA-Z0-9]/g, "")}`;
                        serialList.append(`
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input serial-checkbox" type="checkbox" name="items_retur[${index}][nomor_seri_diretur][]" value="${serial}" id="${uniqueId}">
                                    <label class="form-check-label" for="${uniqueId}">${serial}</label>
                                </div>
                            </div>
                        `);
                    });
                } else {
                    serialList.html('<small class="text-danger">Tidak ada nomor seri tersedia untuk batch ini.</small>');
                }
            }
        });
    }
    
    // Validasi jumlah retur dan pemilihan nomor seri
    $('#item-retur-container').on('change', '.jumlah-retur-input, .serial-checkbox', function() {
        const card = $(this).closest('.item-retur-card');
        const jumlahInput = card.find('.jumlah-retur-input');
        const serialArea = card.find('.serial-selection-area');
        const maxSerials = parseInt(jumlahInput.val()) || 0;
        
        card.find('.max-serial-select').text(maxSerials);
        const checkedCount = card.find('.serial-checkbox:checked').length;
        card.find('.selected-serial-count').text(checkedCount);

        if (serialArea.is(':visible') && checkedCount > maxSerials) {
            Swal.fire('Oops!', `Anda hanya bisa memilih ${maxSerials} nomor seri.`, 'warning');
            if($(this).is(':checkbox')) {
                $(this).prop('checked', false);
                card.find('.selected-serial-count').text(maxSerials);
            }
        }
    });

    // Tambah item pertama secara otomatis jika supplier sudah dipilih saat memuat halaman
    if ($('#id_supplier_tujuan').val()) {
        $('#btn-tambah-item').prop('disabled', false).trigger('click');
    }
});
</script>
@endpush