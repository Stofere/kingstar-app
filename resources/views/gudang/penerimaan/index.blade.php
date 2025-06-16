@extends('layouts.app')

@section('title', 'Daftar PO Menunggu Penerimaan')

@push('styles')
    <style>
        #penerimaan-po-table th, #penerimaan-po-table td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Daftar PO Menunggu Penerimaan</h1>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center">
            <h5 class="mb-0">Purchase Orders</h5>
            <div class="btn-group">
                <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-plus-circle-fill me-1"></i> Buat Penerimaan Baru
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('gudang.penerimaan.create', ['tipe' => 'MANUAL']) }}">Penerimaan Manual (Stok Lama)</a></li>
                    <li><a class="dropdown-item" href="#">Penerimaan dari Retur (Segera Hadir)</a></li>
                    {{-- Nanti link untuk retur bisa dibuat dinamis --}}
                </ul>
            </div>
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
            @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div class="table-responsive">
                <table id="penerimaan-po-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nomor PO</th>
                            <th>Tanggal PO</th>
                            <th>Supplier</th>
                            <th>Status PO</th>
                            <th>Item Belum Diterima</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data akan diisi oleh DataTables --}}
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
            $('#penerimaan-po-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('gudang.penerimaan.index') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'nomor_pembelian', name: 'nomor_pembelian' },
                    { data: 'tanggal_pembelian_formatted', name: 'tanggal_pembelian' },
                    { data: 'supplier_nama', name: 'supplier.nama' }, // Pastikan relasi 'supplier' ada di query controller
                    { data: 'status_pembelian_badge', name: 'status_pembelian' },
                    { data: 'item_belum_diterima', name: 'item_belum_diterima', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Memuat...</span></div>',
                    search: "_INPUT_",
                    searchPlaceholder: "Cari PO...",
                    lengthMenu: "_MENU_",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "<i class='bi bi-chevron-right'></i>",
                        previous: "<i class='bi bi-chevron-left'></i>"
                    },
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ PO",
                    infoEmpty: "Tidak ada PO ditemukan",
                    infoFiltered: "(disaring dari _MAX_ total PO)",
                    zeroRecords: "Tidak ada PO yang cocok ditemukan"
                },
                order: [[1, 'desc']] // Default order by Nomor PO descending
            });
        });
    </script>
@endpush