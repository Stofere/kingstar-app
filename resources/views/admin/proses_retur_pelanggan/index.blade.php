@extends('layouts.app') 

@section('title', 'Proses Tindak Lanjut Retur Pelanggan')

@push('styles')
    <style>
        #proses-retur-table th, 
        #proses-retur-table td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0 h3">Proses Tindak Lanjut Retur dari Pelanggan</h1>
        {{-- Tidak ada tombol "Buat Baru" di sini, karena ini memproses yang sudah ada --}}
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark"> {{-- Warna header bisa disesuaikan --}}
            <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i>Daftar Retur Pelanggan Menunggu Tindakan Admin</h5>
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
                <table id="proses-retur-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. Retur</th>
                            <th>Tgl Retur</th>
                            <th>No. Nota Asal</th>
                            <th>Pelanggan</th>
                            <th>Produk Diretur</th>
                            <th>Jml</th>
                            <th>Alasan Awal</th>
                            <th>Tindakan Awal Kasir</th>
                            <th>Kasir Awal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data akan diisi oleh DataTables dari ProsesReturPelangganController@index --}}
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
            $('#proses-retur-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('admin.proses_retur_pelanggan.index') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'nomor_retur', name: 'retur_penjualan.nomor_retur' }, // Sorting/searching by retur_penjualan.nomor_retur
                    { data: 'tanggal_retur_formatted', name: 'retur_penjualan.tanggal_retur' },
                    { data: 'nomor_penjualan_asal', name: 'detailPenjualan.penjualan.nomor_penjualan', orderable: false },
                    { data: 'pelanggan', name: 'detailPenjualan.penjualan.pelanggan.nama', orderable: false },
                    { data: 'nama_produk', name: 'detailPenjualan.produk.nama', orderable: false },
                    { data: 'jumlah_retur_formatted', name: 'retur_penjualan.jumlah_retur', className: 'text-center' },
                    { data: 'alasan_retur', name: 'retur_penjualan.alasan_retur', orderable: false, searchable: false },
                    { data: 'tindakan_lanjut_awal', name: 'retur_penjualan.tindakan_lanjut', orderable: false},
                    { data: 'kasir_proses_awal', name: 'pengguna.nama', orderable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                language: { /* ... Opsi bahasa DataTables ... */ },
                order: [[2, 'asc']] // Default order by Tanggal Retur ascending (yang paling lama belum diproses)
            });
        });
    </script>
@endpush