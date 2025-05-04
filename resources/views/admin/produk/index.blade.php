@extends('layouts.app')

@section('title', 'Kelola Produk')

@push('styles')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #produk-table img { max-width: 100%; height: auto; max-height: 50px; object-fit: contain; cursor: pointer; }
        #produk-table .action-buttons form { margin-bottom: 0; }
        /* Style untuk modal gambar */
        .image-modal-content { max-width: 90vw; max-height: 85vh; }
    </style>
@endpush

@section('content')
<div class="container">
    <h1 class="mb-4">Kelola Produk</h1>
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
           <h5 class="mb-0">Daftar Produk</h5>
            <a href="{{ route('admin.produk.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Produk
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                {{-- Tabel HTML KOSONG, hanya header --}}
                <table id="produk-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Nama Produk</th>
                            <th>Merk</th>
                            <th>Kode</th>
                            <th>Harga Jual</th>
                            <th>Serial?</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data akan diisi oleh DataTables via AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk menampilkan gambar -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Gambar Produk</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="modalImage" class="img-fluid image-modal-content" alt="Gambar Produk">
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
    {{-- SweetAlert2 --}}
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Inisialisasi DataTables untuk Server-Side --}}
    <script>
        // Fungsi untuk menampilkan modal gambar
        function showImageModal(imageUrl, imageTitle) {
            $('#modalImage').attr('src', imageUrl);
            $('#imageModalLabel').text('Gambar Produk: ' + imageTitle);
            var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
        }

        $(document).ready(function() {
            // Inisialisasi DataTables (akan berjalan setelah app.js selesai)
            var table = $('#produk-table').DataTable({
                processing: true, // Tampilkan pesan "Processing..."
                serverSide: true, // Aktifkan server-side processing
                responsive: true,
                ajax: "{{ route('admin.produk.index') }}", // URL untuk ambil data AJAX
                columns: [
                    // Kolom harus sesuai dengan data yang dikirim dari controller
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' }, // Nomor urut
                    { data: 'gambar_display', name: 'gambar_display', orderable: false, searchable: false, width: '10%' }, // Kolom gambar
                    { data: 'nama', name: 'nama' }, // Nama produk (bisa search & sort)
                    { data: 'merk.nama', name: 'merk.nama', defaultContent: '-', searchable: true, orderable: true }, // Merk (bisa search & sort)
                    { data: 'kode_produk', name: 'kode_produk', defaultContent: '-' }, // Kode produk
                    { data: 'harga_jual_standart', name: 'harga_jual_standart' }, // Harga
                    { data: 'memiliki_serial', name: 'memiliki_serial', orderable: false, searchable: false }, // Serial
                    { data: 'status', name: 'status', orderable: false, searchable: false }, // Status
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '10%' } // Kolom aksi
                ],
                language: { // Opsi untuk bahasa Indonesia DataTables
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>' // Custom loading indicator
                },
                order: [[2, 'asc']] // Default order by kolom ke-3 (Nama Produk) ascending
            });

            // Konfirmasi Hapus (delegasi event)
            $('#produk-table').on('submit', '.form-delete', function(e) {
                e.preventDefault();
                var form = this;
                var url = $(form).attr('action'); // Ambil URL dari form

                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: "Data produk yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Kirim request hapus via AJAX untuk refresh tabel otomatis
                        console.log('Data yang akan dikirim:', $(form).serialize());
                        // $.ajax({
                        //     url: url,
                        //     type: 'POST', // Method tetap POST karena ada @method('DELETE')
                        //     data: $(form).serialize(), // Kirim data form (termasuk _token & _method)
                        //     dataType: 'json', // Harapkan response JSON dari controller
                        //     success: function(response) {
                        //         if(response.success) {
                        //             Swal.fire(
                        //                 'Dihapus!',
                        //                 response.message,
                        //                 'success'
                        //             );
                        //             table.ajax.reload(null, false); // Reload DataTables tanpa reset pagination
                        //         } else {
                        //             Swal.fire(
                        //                 'Gagal!',
                        //                 response.message,
                        //                 'error'
                        //             );
                        //         }
                        //     },
                        //     error: function(xhr, status, error) {
                        //         // Tangani error AJAX
                        //         var errorMessage = 'Terjadi kesalahan saat menghapus data.';
                        //         if(xhr.responseJSON && xhr.responseJSON.message) {
                        //             errorMessage = xhr.responseJSON.message;
                        //         }
                        //         Swal.fire(
                        //             'Error!',
                        //             errorMessage,
                        //             'error'
                        //         );
                        //     }
                        // });
                        form.submit();
                    }
                })
            });
        });
    </script>
@endpush