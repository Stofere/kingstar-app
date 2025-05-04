@extends('layouts.app')

@section('title', 'Kelola Pembelian')

@push('styles')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    {{-- SweetAlert2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Style agar tombol aksi tidak terlalu makan tempat & rapat */
        #pembelian-table .action-buttons form,
        #pembelian-table .action-buttons a {
            display: inline-block;
            margin-bottom: 0;
            margin-right: 2px; /* Sedikit jarak antar tombol */
        }
        #pembelian-table .action-buttons {
            white-space: nowrap; /* Mencegah tombol wrap ke baris baru */
        }
         /* Optional: Adjust column width if needed */
        /* #pembelian-table th.col-aksi { width: 120px; } */
        /* #pembelian-table th.col-status { width: 150px; } */
    </style>
@endpush

@section('content')
<div class="container">
    <h1 class="mb-4">Kelola Pembelian</h1>
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
           <h5 class="mb-0">Daftar Pembelian</h5>
            <a href="{{ route('admin.pembelian.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Buat Pembelian Baru
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="pembelian-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Pembelian</th>
                            <th>Supplier</th>
                            <th>Tgl Pembelian</th>
                            <th>Total Harga</th>
                            <th class="col-status">Status Beli</th> {{-- Ubah nama kolom jika perlu --}}
                            <th class="col-status">Status Bayar</th>
                            <th class="col-aksi text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data diisi oleh DataTables AJAX --}}
                        {{-- Kosongkan bagian ini --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Inisialisasi DataTables --}}
    <script>
        $(document).ready(function() {
            var table = $('#pembelian-table').DataTable({
                processing: true, // Tampilkan indikator loading
                serverSide: true, // Aktifkan server-side processing
                responsive: true, // Aktifkan responsivitas
                ajax: "{{ route('admin.pembelian.index') }}", // URL untuk mengambil data AJAX
                columns: [
                    // Kolom nomor urut (dihasilkan oleh DataTables)
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                    // Kolom data dari model Pembelian (sesuaikan 'name' untuk searching/ordering di backend)
                    { data: 'nomor_pembelian', name: 'nomor_pembelian', defaultContent: '-' }, // Tampilkan '-' jika null
                    { data: 'supplier_nama', name: 'supplier.nama' }, // 'supplier_nama' harus dikirim dari controller
                    { data: 'tanggal_pembelian_formatted', name: 'tanggal_pembelian' }, // 'tanggal_pembelian_formatted' harus dikirim dari controller
                    { data: 'total_harga_formatted', name: 'total_harga', className: 'text-end' }, // 'total_harga_formatted' harus dikirim dari controller
                    { data: 'status_pembelian_badge', name: 'status_pembelian', orderable: false, searchable: false }, // 'status_pembelian_badge' (HTML badge) harus dikirim dari controller
                    { data: 'status_pembayaran_badge', name: 'status_pembayaran', orderable: false, searchable: false }, // 'status_pembayaran_badge' (HTML badge) harus dikirim dari controller
                    // Kolom aksi (HTML tombol) - 'action' harus dikirim dari controller
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '10%', className: 'action-buttons text-center' }
                ],
                language: { // Terjemahan Bahasa Indonesia
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
                    processing: '<div class="spinner-border text-primary spinner-border-sm" role="status"><span class="visually-hidden">Memuat...</span></div>' // Indikator loading custom
                },
                order: [[ 3, "desc" ]] // Default order by tanggal pembelian (indeks kolom 3) descending
            });

            // Konfirmasi Hapus AJAX (Sama seperti template Supplier)
            $('#pembelian-table').on('submit', '.form-delete', function(e) {
                e.preventDefault();
                var form = this;
                var url = $(form).attr('action');

                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: "Data pembelian yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'POST', // Method tetap POST untuk form
                            data: $(form).serialize(), // Kirim _token dan _method=DELETE
                            dataType: 'json', // Harapkan response JSON dari controller destroy
                            success: function(response) {
                                if(response.success) {
                                    Swal.fire('Dihapus!', response.message, 'success');
                                    table.ajax.reload(null, false); // Reload tabel tanpa reset pagination
                                } else {
                                    // Tampilkan pesan error dari server (misal: karena relasi)
                                    Swal.fire('Gagal!', response.message || 'Gagal menghapus data.', 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                // Tangani error AJAX umum
                                var errorMessage = 'Terjadi kesalahan saat menghapus data.';
                                if(xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', errorMessage, 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush