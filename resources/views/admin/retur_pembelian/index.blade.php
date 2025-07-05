@extends('layouts.app')

@section('title', 'Daftar Retur Pembelian ke Supplier')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #retur-pembelian-table th, #retur-pembelian-table td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Daftar Retur Pembelian</h1>
        <a href="{{ route('admin.retur_pembelian.create') }}" class="btn btn-danger"> {{-- Tombol warna merah untuk retur --}}
            <i class="bi bi-upload me-1"></i> Buat Retur Pembelian Baru
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Riwayat Retur Pembelian ke Supplier</h5>
        </div>
        <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div class="table-responsive">
                <table id="retur-pembelian-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. Retur</th>
                            <th>Tgl Retur</th>
                            <th>Supplier Tujuan</th>
                            <th>Total Jml</th>
                            <th>Status</th>
                            <th>Admin Proses</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data akan diisi oleh DataTables dari ReturPembelianController@index --}}
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
            $('#retur-pembelian-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('admin.retur_pembelian.index') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nomor_retur', name: 'nomor_retur' },
                    { data: 'tanggal_retur_formatted', name: 'tanggal_retur' },
                    { data: 'supplier_tujuan', name: 'supplier.nama' },
                    { data: 'total_jumlah_retur', name: 'total_jumlah_retur', orderable: false, searchable: false },
                    { data: 'status_display', name: 'status' },
                    { data: 'admin_proses', name: 'pengguna.nama' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Memuat...</span></div>',
                    search: "_INPUT_",
                    searchPlaceholder: "Cari retur pembelian...",
                    lengthMenu: "_MENU_",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "<i class='bi bi-chevron-right'></i>",
                        previous: "<i class='bi bi-chevron-left'></i>"
                    },
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ retur",
                    infoEmpty: "Tidak ada data retur pembelian ditemukan",
                    infoFiltered: "(disaring dari _MAX_ total retur)",
                    zeroRecords: "Tidak ada retur pembelian yang cocok ditemukan"
                },
                order: [[2, 'desc'], [1, 'desc']] // Default order by Tanggal Retur descending, lalu Nomor Retur descending
            });
        });
    </script>
@endpush
