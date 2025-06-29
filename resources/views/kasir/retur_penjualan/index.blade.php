@extends('layouts.app')

@section('title', 'Daftar Retur Penjualan')

@push('styles')
    <style>
        #retur-penjualan-table th, #retur-penjualan-table td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Daftar Retur Penjualan</h1>
        <a href="{{ route('kasir.retur_penjualan.cari_transaksi') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle-fill me-1"></i> Buat Retur Penjualan Baru
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Riwayat Retur Penjualan</h5>
        </div>
        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            @if(session('info_tukar_barang'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> {{ session('info_tukar_barang') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div class="table-responsive">
                <table id="retur-penjualan-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. Retur</th>
                            <th>Tgl Retur</th>
                            <th>No. Nota Asal</th>
                            <th>Pelanggan</th>
                            <th>Produk Diretur</th>
                            <th>Jml Diretur</th>
                            <th>Tindakan Lanjut</th>
                            <th>Kasir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data akan diisi oleh DataTables dari ReturPenjualanController@index --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')

    <script>
        $(document).ready(function() {
            $('#retur-penjualan-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('kasir.retur_penjualan.index') }}", // Pastikan route ini dihandle controller
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'nomor_retur', name: 'nomor_retur' },
                    { data: 'tanggal_retur_formatted', name: 'tanggal_retur' },
                    { data: 'nomor_penjualan_asal', name: 'detailPenjualan.penjualan.nomor_penjualan', orderable: false },
                    { data: 'pelanggan', name: 'detailPenjualan.penjualan.pelanggan.nama', orderable: false },
                    { data: 'nama_produk', name: 'detailPenjualan.produk.nama', orderable: false },
                    { data: 'jumlah_retur_formatted', name: 'jumlah_retur', className: 'text-center' },
                    { data: 'tindakan_lanjut_display', name: 'tindakan_lanjut' },
                    { data: 'pengguna.nama', name: 'pengguna.nama', orderable: false }, // Kasir yang memproses retur
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                language: { /* ... Opsi bahasa DataTables sama seperti sebelumnya ... */
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Memuat...</span></div>',
                    search: "_INPUT_",
                    searchPlaceholder: "Cari retur...",
                    lengthMenu: "_MENU_",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "<i class='bi bi-chevron-right'></i>",
                        previous: "<i class='bi bi-chevron-left'></i>"
                    },
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ retur",
                    infoEmpty: "Tidak ada data retur ditemukan",
                    infoFiltered: "(disaring dari _MAX_ total retur)",
                    zeroRecords: "Tidak ada retur yang cocok ditemukan"
                },
                order: [[2, 'desc'], [1, 'desc']] // Default order by Tanggal Retur descending, lalu Nomor Retur descending
            });
        });
    </script>
@endpush