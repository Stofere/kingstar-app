@extends('layouts.app')

@section('title', 'Buat Penyesuaian Stok')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Buat Penyesuaian Stok</h1>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Formulir Penyesuaian Stok</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    </div>
                    @endif
                    @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    </div>
                    @endif
                    
                    <form action="{{ route('admin.penyesuaian_stok.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="id_stok_barang" class="form-label required-label">Pilih Batch Stok</label>
                            {{-- Kita akan gunakan Select2 untuk mencari batch --}}
                            <select class="form-select select2-batch-search" id="id_stok_barang" name="id_stok_barang" required data-placeholder="Ketik untuk mencari Batch ID, Produk, atau Supplier...">
                                <option></option>
                            </select>
                            @error('id_stok_barang')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                            <div id="batch-info" class="mt-2 text-muted" style="display:none;"></div>
                        </div>

                        <div class="mb-3">
                            <label for="tipe_penyesuaian" class="form-label required-label">Tipe Penyesuaian</label>
                            <select class="form-select" id="tipe_penyesuaian" name="tipe_penyesuaian" required>
                                <option value="" disabled selected>Pilih tipe...</option>
                                @foreach($tipePenyesuaianOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('tipe_penyesuaian') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                             @error('tipe_penyesuaian')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="jumlah" class="form-label required-label">Jumlah</label>
                            <input type="number" class="form-control" id="jumlah" name="jumlah" value="{{ old('jumlah') }}" required min="1">
                            @error('jumlah')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan (Alasan)</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3">{{ old('catatan') }}</textarea>
                             @error('catatan')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Penyesuaian</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Inisialisasi Select2 untuk pencarian batch
    // MENIRU POLA DARI PERPINDAHAN STOK
    $('#id_stok_barang').select2({
        theme: "bootstrap-5",
        placeholder: $(this).data('placeholder'),
        minimumInputLength: 1, // <<< PENTING: Menunggu 1 karakter sebelum mencari
        ajax: {
            url: "{{ route('perpindahan-stok.ajax.search-batch') }}", // Menggunakan route yang sudah ada & berfungsi
            dataType: 'json',
            delay: 250,
            // processResults sekarang sangat sederhana
            processResults: function (data) {
                return {
                    results: data.results // Asumsi controller mengembalikan key 'results'
                };
            },
            cache: true
        }
    }).on('select2:select', function(e) {
        var data = e.params.data;
        
        // Tampilkan info batch yang dipilih (menggunakan data dari respons AJAX)
        $('#batch-info').html(
            `<strong>Info Batch:</strong> Sisa Stok: ${data.sisa} unit. Supplier: ${data.supplier_nama || 'N/A'}`
        ).show();

        // Otomatis memilih tipe jika batch adalah konsinyasi
        if(data.text && data.text.toLowerCase().includes('konsinyasi')) {
            $('#tipe_penyesuaian').val('PENGEMBALIAN_KONSINYASI');
        } else {
             // Jika bukan konsinyasi, reset ke pilihan default agar user tidak salah pilih
            $('#tipe_penyesuaian').val('');
        }
    }).on('select2:unselect', function (e) {
        // Sembunyikan info jika pilihan dibatalkan
        $('#batch-info').hide().empty();
        $('#tipe_penyesuaian').val('');
    });
});
</script>
@endpush