@extends('layouts.app')

@section('title', 'Kelola Pembelian')

@push('styles')
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
{{-- MODAL UNTUK PELUNASAN --}}
<div class="modal fade" id="modalPelunasan" tabindex="-1" aria-labelledby="modalPelunasanLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPelunasanLabel">Konfirmasi Pelunasan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-pelunasan">
                <div class="modal-body">
                    <input type="hidden" id="id_pembelian_lunasi">
                    <p>Anda akan melunasi pembayaran untuk:</p>
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            No. Pembelian
                            <strong id="info_nomor_pembelian"></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Tagihan
                            <strong id="info_total_harga" class="text-danger"></strong>
                        </li>
                    </ul>
                    <div class="mb-3">
                        <label for="metode_pembayaran_lunasi" class="form-label required-label">Metode Pembayaran</label>
                        <select class="form-select" id="metode_pembayaran_lunasi" required>
                             <option value="TUNAI">Tunai</option>
                             <option value="TRANSFER_BCA">Transfer BCA</option>
                             <option value="TRANSFER_MANDIRI">Transfer Mandiri</option>
                        </select>
                    </div>
                     <div class="mb-3">
                        <label for="tanggal_pelunasan" class="form-label required-label">Tanggal Pelunasan</label>
                        <input type="date" class="form-control" id="tanggal_pelunasan" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Konfirmasi Lunas</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '15%', className: 'action-buttons text-center' }
                ],
                language: { 
                    url: '{{ asset('js/i18n/id.json') }}',
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
            
            // === JAVASCRIPT BARU UNTUK MODAL PELUNASAN ===
            $('#pembelian-table').on('click', '.btn-lunasi', function() {
                let idPembelian = $(this).data('id-pembelian');
                let nomorPembelian = $(this).data('nomor-pembelian');
                let totalHarga = $(this).data('total-harga');

                // Isi data ke modal
                $('#id_pembelian_lunasi').val(idPembelian);
                $('#info_nomor_pembelian').text(nomorPembelian);
                $('#info_total_harga').text('Rp ' + new Intl.NumberFormat('id-ID').format(totalHarga));
                $('#tanggal_pelunasan').val(new Date().toISOString().slice(0, 10)); // Set tanggal hari ini

                // Tampilkan modal
                $('#modalPelunasan').modal('show');
            });

            // Handle submit form pelunasan
            $('#form-pelunasan').on('submit', function(e) {
                e.preventDefault();
                let idPembelian = $('#id_pembelian_lunasi').val();
                let url = `/admin/pembelian/${idPembelian}/lunasi`; // Bangun URL secara manual
                
                let formData = {
                    _token: "{{ csrf_token() }}",
                    metode_pembayaran: $('#metode_pembayaran_lunasi').val(),
                    tanggal_pelunasan: $('#tanggal_pelunasan').val(),
                };

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    beforeSend: function() {
                        // Tampilkan loading di tombol
                        $('#form-pelunasan button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                    },
                    success: function(response) {
                        $('#modalPelunasan').modal('hide');
                        if (response.success) {
                            Swal.fire('Berhasil!', response.message, 'success');
                            $('#pembelian-table').DataTable().ajax.reload(); // Reload tabel
                        } else {
                            Swal.fire('Gagal!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Terjadi kesalahan. Silakan coba lagi.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', errorMsg, 'error');
                    },
                    complete: function() {
                        // Kembalikan tombol ke state normal
                        $('#form-pelunasan button[type="submit"]').prop('disabled', false).text('Konfirmasi Lunas');
                    }
                });
            });
        });
    </script>
@endpush