{{-- resources/views/admin/laporan/stok/detail_batch_produk.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Stok Batch untuk Produk: ' . $produk->nama)

@push('styles')
    <style>
        #detail-batch-table th,
        #detail-batch-table td {
            vertical-align: middle;
            font-size: 0.85rem; /* Ukuran font lebih kecil untuk tabel detail */
        }
        .serial-list-cell {
            max-width: 250px; /* Batasi lebar kolom serial */
            white-space: normal; /* Izinkan word wrap */
            word-break: break-all;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1 h3">Laporan Detail Stok Batch</h1>
            <h2 class="mb-0 h5 text-muted">Produk: {{ $produk->nama }} ({{ $produk->kode_produk }})</h2>
        </div>
        <a href="{{ route('admin.laporan.stok.ringkasan_produk') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Ringkasan Stok
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-boxes me-2"></i>Daftar Batch Aktif untuk {{ $produk->nama }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="detail-batch-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>ID Batch</th>
                            <th>Tgl Masuk</th>
                            <th>Sumber</th>
                            <th>Kondisi</th>
                            <th>Lokasi</th>
                            <th class="text-end">Total Jumlah</th>
                            <th class="text-end">Sudah Dipesan</th>
                            <th class="text-end">Stok Siap Jual</th>
                            <th>Nomor Seri (jika ada)</th>
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
            $('#detail-batch-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('admin.laporan.stok.detail_batch_produk', $produk->id) }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'id_batch', name: 'stok_barang.id' },
                    { data: 'diterima_at_formatted', name: 'stok_barang.diterima_at' },
                    { data: 'supplier_nama', name: 'supplier.nama' },
                    { data: 'kondisi', name: 'stok_barang.kondisi' },
                    { data: 'lokasi', name: 'stok_barang.lokasi' },
                    { data: 'total_jumlah_batch', name: 'stok_barang.jumlah', className: 'text-end' },
                    { data: 'sudah_dipesan', name: 'sudah_dipesan', orderable: false, searchable: false, className: 'text-end text-warning' },
                    { data: 'stok_siap_jual', name: 'stok_siap_jual', orderable: false, searchable: false, className: 'text-end' },
                    { data: 'nomor_seri_tersedia', name: 'nomor_seri_tersedia', orderable: false, searchable: false, className: 'serial-list-cell' }
                ],
                language: {  
                    processing: '<i class="bi bi-hourglass-split"></i> Memuat data...'
                },
                order: [[2, 'asc']] 
            });
        });
    </script>
@endpush