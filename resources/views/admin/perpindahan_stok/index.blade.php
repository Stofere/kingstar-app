@extends('layouts.app')
@section('title', 'Riwayat Perpindahan Stok')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Riwayat Perpindahan Stok</h1>
        <a href="{{ route('perpindahan-stok.create') }}" class="btn btn-primary"><i class="bi bi-arrows-move"></i> Buat Perpindahan Baru</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table id="perpindahan-table" class="table table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal Pindah</th>
                        <th>Produk</th>
                        <th>Jml</th>
                        <th>Dari</th>
                        <th>Ke</th>
                        <th>Batch Asal</th>
                        <th>Oleh</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#perpindahan-table').DataTable({
        processing: true, serverSide: true,
        ajax: "{{ route('perpindahan-stok.index') }}",
        columns: [
            { data: 'id', name: 'id' },
            { data: 'dipindahkan_at', name: 'dipindahkan_at' },
            { data: 'produk', name: 'stokBarang.produk.nama' },
            { data: 'jumlah', name: 'jumlah' },
            { data: 'dari_lokasi', name: 'dari_lokasi' },
            { data: 'ke_lokasi', name: 'ke_lokasi' },
            { data: 'batch_asal', name: 'id_stok_barang' },
            { data: 'pengguna_nama', name: 'pengguna.nama' },
            { data: 'catatan', name: 'catatan' },
        ],
        order: [[0, 'desc']]
    });
});
</script>
@endpush