@extends('layouts.app')

@section('title', 'Kelola Supplier')

@push('styles')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
     <style>
        #supplier-table .action-buttons form { margin-bottom: 0; }
    </style>
@endpush

@section('content')
<div class="container">
    <h1 class="mb-4">Kelola Supplier</h1>
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
           <h5 class="mb-0">Daftar Supplier</h5>
            <a href="{{ route('admin.supplier.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Supplier
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="supplier-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Supplier</th>
                            <th>Telepon</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data diisi oleh DataTables AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- SweetAlert2 --}}
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Inisialisasi DataTables --}}
    <script>
        $(document).ready(function() {
            var table = $('#supplier-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('admin.supplier.index') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                    { data: 'nama', name: 'nama' },
                    { data: 'telepon', name: 'telepon' },
                    { data: 'email', name: 'email', defaultContent: '-' },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '10%', className: 'action-buttons' }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                },
                order: [[1, 'asc']] // Default order by nama supplier
            });

            // Konfirmasi Hapus AJAX
            $('#supplier-table').on('submit', '.form-delete', function(e) {
                e.preventDefault();
                var form = this;
                var url = $(form).attr('action');
                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: "Data supplier yang dihapus tidak dapat dikembalikan!",
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
                            type: 'POST', // Tetap POST
                            data: $(form).serialize(), // Kirim _token & _method
                            dataType: 'json',
                            success: function(response) {
                                if(response.success) {
                                    Swal.fire('Dihapus!', response.message, 'success');
                                    table.ajax.reload(null, false);
                                } else {
                                    // Tampilkan pesan error dari server jika ada relasi
                                    Swal.fire('Gagal!', response.message, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
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