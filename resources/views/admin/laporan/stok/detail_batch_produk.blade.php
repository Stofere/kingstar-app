@extends('layouts.app')

@section('title', 'Detail Stok Batch untuk: ' . $produk->nama)

@push('styles')
    <style>
        #detail-batch-table th,
        #detail-batch-table td {
            vertical-align: middle;
            font-size: 0.85rem;
        }
        .serial-list-cell {
            max-width: 300px; /* Lebarkan sedikit untuk serial */
            white-space: normal;
            word-break: break-all;
        }
        .footer-total-stok {
            background-color: #f8f9fa; /* Warna latar footer */
            font-size: 1.1rem;
            font-weight: bold;
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
                            <th style="width: 5%;">ID</th>
                            <th style="width: 15%;">Tgl Masuk</th>
                            {{-- KOLOM BARU: Sumber & Harga Beli --}}
                            <th style="width: 20%;">Sumber & Harga Beli</th>
                            <th style="width: 10%;">Kondisi</th>
                            <th style="width: 10%;">Lokasi</th>
                            <th class="text-end" style="width: 10%;">Total Qty</th>
                            <th class="text-end" style="width: 10%;">Qty Siap Jual</th>
                            <th style="width: 20%;">Nomor Seri Tersedia</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data akan diisi oleh DataTables --}}
                    </tbody>
                    {{-- FOOTER BARU UNTUK TOTAL STOK --}}
                    <tfoot class="footer-total-stok">
                        <tr>
                            <th colspan="6" class="text-end">Total Stok Siap Jual (Kondisi BAIK):</th>
                            <th class="text-end text-success">
                                {{ $totalStokSiapJual ?: 0 }} {{ $produk->satuan }}
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
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
                    { data: 'id_batch', name: 'id' },
                    { data: 'diterima_at_formatted', name: 'diterima_at' },
                    // KOLOM BARU KITA
                    { data: 'sumber_dan_harga_display', name: 'sumber_dan_harga_display', orderable: false, searchable: false },
                    { data: 'kondisi', name: 'kondisi' },
                    { data: 'lokasi', name: 'lokasi' },
                    { data: 'total_jumlah_batch', name: 'jumlah', className: 'text-end' },
                    { data: 'stok_siap_jual', name: 'stok_siap_jual', orderable: false, searchable: false, className: 'text-end' },
                    { data: 'nomor_seri_tersedia', name: 'nomor_seri_tersedia', orderable: false, searchable: false, className: 'serial-list-cell' }
                ],
                // Kita hilangkan footer bawaan DataTables agar tidak tumpang tindih
                // dengan <tfoot> HTML kita
                dom: 'lrtip', // l-length, r-processing, t-table, i-info, p-paginate
                language: {
                    url: '{{ asset('js/i18n/id.json') }}',
                },
                order: [[1, 'asc']] // Order by Tgl Masuk
            });
        });
    </script>
@endpush
