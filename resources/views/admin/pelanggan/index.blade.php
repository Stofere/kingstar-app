@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('title', 'Manajemen Pelanggan')

@push('styles')
    <style>
        /* Optional: Custom styles for DataTables */
        #pelanggan-table th, #pelanggan-table td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Manajemen Pelanggan</h1>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center">
            <h5 class="mb-0">Data Pelanggan</h5>
            <a href="{{ route('admin.pelanggan.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Pelanggan Baru
            </a>
        </div>
        <div class="card-body">
            {{-- Alert untuk pesan sukses atau error --}}
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
                <table id="pelanggan-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Telepon</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th>Dibuat Pada</th>
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
            // Inisialisasi DataTables
            var table = $('#pelanggan-table').DataTable({
                processing: true, // Menampilkan indikator loading
                serverSide: true, // Memproses data di server
                responsive: true, // Mengaktifkan mode responsif
                ajax: {
                    // URL untuk mengambil data pelanggan (method index di controller)
                    url: "{{ route('admin.pelanggan.index') }}",
                    type: 'GET'
                },
                columns: [
                    // Kolom-kolom DataTables sesuai dengan yang dikirim dari controller
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'nama', name: 'nama' },
                    { data: 'telepon', name: 'telepon' },
                    { data: 'alamat', name: 'alamat' },
                    { data: 'status', name: 'status', className: 'text-center' }, // Menggunakan kolom 'status' yang sudah diformat HTML
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' } // Kolom aksi
                ],
                language: {
                    // Konfigurasi bahasa DataTables (opsional, bisa disesuaikan)
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Memuat...</span></div>',
                    search: "_INPUT_",
                    searchPlaceholder: "Cari pelanggan...",
                    lengthMenu: "_MENU_",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "<i class='bi bi-chevron-right'></i>",
                        previous: "<i class='bi bi-chevron-left'></i>"
                    },
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ pelanggan",
                    infoEmpty: "Tidak ada pelanggan ditemukan",
                    infoFiltered: "(disaring dari _MAX_ total pelanggan)",
                    zeroRecords: "Tidak ada pelanggan yang cocok ditemukan"
                },
                order: [[1, 'asc']] // Default order by nama ascending
            });

            // Event listener untuk tombol hapus (menggunakan delegasi event karena tombol ada di dalam DataTables)
            $('#pelanggan-table').on('click', '.btn-delete', function() {
                var pelangganId = $(this).data('id'); // Ambil ID pelanggan dari atribut data-id

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data pelanggan ini akan dihapus secara permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Jika user mengkonfirmasi, kirim request DELETE via AJAX
                        $.ajax({
                            url: '/admin/pelanggan/' + pelangganId, // URL delete (sesuai route resource)
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}' // Kirim token CSRF
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Jika berhasil, tampilkan pesan sukses dan reload DataTables
                                    Swal.fire({
                                        title: 'Dihapus!',
                                        text: response.message,
                                        icon: 'success',
                                        timer: 2000, // Tutup otomatis setelah 2 detik
                                        showConfirmButton: false
                                    });
                                    table.ajax.reload(); // Reload DataTables
                                } else {
                                    // Jika gagal (misal karena foreign key), tampilkan pesan error
                                    Swal.fire({
                                        title: 'Gagal!',
                                        text: response.message,
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function(xhr) {
                                // Tangani error AJAX
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan saat menghapus data.',
                                    icon: 'error'
                                });
                                console.error(xhr.responseText);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush