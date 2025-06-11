{{-- admin/laporan/stok/ringkasan_produk.blade.php --}}
@extends('layouts.app')

@section('title', 'Laporan Status Stok Produk')

@push('styles')
    <style>
        #status-stok-produk-table th,
        #status-stok-produk-table td {
            vertical-align: middle;
            font-size: 0.875rem;
        }
        #status-stok-produk-table img { max-height: 40px; max-width: 40px; object-fit: contain; cursor: pointer; }
        .badge.bg-warning.text-dark { color: #000 !important; }
        .btn-xs { padding: 0.15rem 0.4rem; font-size: 0.75rem; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0 h3">Laporan Status Stok Produk</h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Status Stok Produk Terkini</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="status-stok-produk-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Kode</th>
                            <th>Nama Produk</th>
                            <th>Merk</th>
                            <th class="text-end">Stok Minimum</th>
                            {{-- DIUBAH: Judul kolom diganti agar lebih jelas --}}
                            <th class="text-end">Stok Siap Jual & Status</th>
                            <th class="text-center">Aksi</th>
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

<!-- Modal untuk menampilkan gambar (jika belum ada di layout utama atau dibutuhkan di sini) -->
<div class="modal fade" id="imageProductModal" tabindex="-1" aria-labelledby="imageProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageProductModalLabel">Gambar Produk</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="modalProductImage" class="img-fluid image-modal-content" alt="Gambar Produk">
      </div>
    </div>
  </div>
</div>
@endsection



@push('scripts')
    <script>
        // Fungsi untuk modal gambar (jika belum ada global)
        function showImageModal(imageUrl, imageTitle) {
            $('#modalProductImage').attr('src', imageUrl);
            $('#imageProductModalLabel').text('Gambar: ' + imageTitle);
            var imageModal = new bootstrap.Modal(document.getElementById('imageProductModal'));
            imageModal.show();
        }


        $(document).ready(function() {
            $('#status-stok-produk-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('admin.laporan.stok.ringkasan_produk') }}", // Pastikan nama route ini benar
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'gambar_display', name: 'gambar_display', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'kode_produk_display', name: 'produk.kode_produk' }, // Menggunakan _display, name ke kolom asli
                    { data: 'nama_produk_display', name: 'produk.nama' },       // Menggunakan _display, name ke kolom asli
                    { data: 'merk_display', name: 'merk.nama', orderable: true, searchable: true }, // Menggunakan _display, name ke kolom asli
                    { data: 'stok_minimum_display', name: 'produk.stok_minimum', className: 'text-end' }, // Kolom baru, name ke kolom asli
                    { data: 'stok_efektif_dan_status_display', name: 'stok_efektif_dan_status_display', orderable: false, searchable: false, className: 'text-end' }, // Kolom gabungan
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                columnDefs: [
                    { width: '5%', targets: 0 },  // No
                    { width: '10%', targets: 1 }, // Gambar
                    { width: '15%', targets: 2 }, // Kode
                    { width: '25%', targets: 3 }, // Nama
                    { width: '15%', targets: 4 }, // Merk
                    { width: '10%', targets: 5 }, // Stok Minimum
                    { width: '10%', targets: 6 }, // Stok Efektif & Status
                    { width: '10%', targets: 7 }  // Aksi
                ],
                language: {
                    processing: '<i class="bi bi-hourglass-split"></i> Memproses...',
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ entri',
                    info: 'Menampilkan _START_ hingga _END_ dari _TOTAL_ entri',
                    infoEmpty: 'Tidak ada data yang tersedia',
                    infoFiltered: '(difilter dari _MAX_ total entri)',
                    zeroRecords: 'Tidak ditemukan data yang sesuai',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Selanjutnya',
                        previous: 'Sebelumnya'
                    }
                },
                order: [[3, 'asc']]
            });
        });
    </script>
@endpush