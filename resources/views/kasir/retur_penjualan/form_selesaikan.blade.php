@extends('layouts.app')
@section('title', 'Selesaikan Penukaran untuk Retur: ' . $returPenjualan->nomor_retur)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="mb-4">Selesaikan Penukaran Barang</h1>
            <div class="card shadow-sm">
                <div class="card-header"><h5>Retur: {{ $returPenjualan->nomor_retur }}</h5></div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p class="mb-1"><strong>Pelanggan:</strong> {{ $returPenjualan->detailPenjualan->penjualan->pelanggan->nama ?? 'Umum' }}</p>
                        <p class="mb-0"><strong>Produk yang Diretur:</strong> {{ $returPenjualan->detailPenjualan->produk->nama }} (Qty: {{ $returPenjualan->jumlah_retur }})</p>
                    </div>
                    <hr>
                    <form action="{{ route('kasir.retur_penjualan.selesaikan.store', $returPenjualan->id) }}" method="POST">
                        @csrf
                        <p class="fw-bold">Pilih Barang Pengganti yang Akan Diberikan:</p>
                        <div class="mb-3">
                            <label for="id_stok_barang" class="form-label">Pilih Batch Stok Pengganti <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_stok_barang" name="id_stok_barang" required data-placeholder="Cari Batch..."></select>
                        </div>
                        @if($returPenjualan->detailPenjualan->produk->memiliki_serial)
                        <div class="mb-3" id="area-serial" style="display: none;">
                            <label for="nomor_seri_pengganti" class="form-label">Pilih Nomor Seri Barang Pengganti <span class="text-danger">*</span></label>
                            <select class="form-select" id="nomor_seri_pengganti" name="nomor_seri_pengganti" required></select>
                        </div>
                        @endif
                        <div class="mb-3">
                            <label for="catatan_penyerahan" class="form-label">Catatan Penyerahan (Opsional)</label>
                            <textarea class="form-control" name="catatan_penyerahan" rows="2"></textarea>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('kasir.retur_penjualan.index') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan & Selesaikan Retur</button>
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
    const idProduk = {{ $returPenjualan->detailPenjualan->id_produk }};
    const hasSerial = {{ $returPenjualan->detailPenjualan->produk->memiliki_serial ? 'true' : 'false' }};

    $('#id_stok_barang').select2({
        theme: "bootstrap-5",
        placeholder: "Cari Batch...",
        ajax: {
            url: "{{ route('kasir.ajax.stok.available') }}", // Kita pakai AJAX yang sudah ada
            dataType: 'json',
            delay: 250,
            data: function(params) {
                // Kita hanya butuh id_produk untuk mencari batch yang relevan
                return { id_produk: idProduk, qty_dibutuhkan: 1, q: params.term };
            },
            processResults: function(data) {
                // Ubah format agar sesuai dengan Select2
                const formattedData = data.batches_data.map(batch => {
                    return { id: batch.id, text: `Batch ID: ${batch.id} | Sisa: ${batch.jumlah_tersedia}` };
                });
                return { results: formattedData };
            }
        }
    }).on('select2:select', function (e) {
        if(hasSerial) {
            const batchId = e.params.data.id;
            loadSerials(batchId);
            $('#area-serial').show();
        }
    });

    function loadSerials(batchId) {
        const serialSelect = $('#nomor_seri_pengganti');
        serialSelect.empty().append('<option value="">Memuat...</option>');
        $.ajax({
            url: "{{ route('kasir.ajax.stok.serials') }}", // AJAX yang sudah ada
            data: { id_stok_barang: batchId },
            success: function(response) {
                serialSelect.empty();
                if (response.success && response.serials.length > 0) {
                    response.serials.forEach(serial => {
                        serialSelect.append(new Option(serial, serial));
                    });
                } else {
                    serialSelect.append('<option value="">Tidak ada serial</option>');
                }
            }
        });
    }
});
</script>
@endpush