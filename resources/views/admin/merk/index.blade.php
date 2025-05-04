@extends('layouts.app')

@section('title', 'Kelola Merk')

@push('styles')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
     <style>
        #merk-table .action-buttons form { margin-bottom: 0; }
    </style>
@endpush

@section('content')
<div class="container">
    <h1 class="mb-4">Kelola Merk Produk</h1>
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
           <h5 class="mb-0">Daftar Merk</h5>
            <a href="{{ route('admin.merk.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Merk
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="merk-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Merk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($merk as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->nama }}</td>
                            <td class="action-buttons">
                                <a href="{{ route('admin.merk.edit', $item->id) }}" class="btn btn-warning btn-sm me-1" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('admin.merk.destroy', $item->id) }}" method="POST" class="d-inline form-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">Belum ada data merk.</td>
                        </tr>
                        @endforelse
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

    {{-- Inisialisasi DataTables Client-Side --}}
    <script>
        $(document).ready(function() {
            $('#merk-table').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
                },
                // Atur default order by kolom ke-2 (Nama Merk) ascending
                order: [[1, 'asc']]
            });

            // Konfirmasi Hapus
            $('#merk-table').on('submit', '.form-delete', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: "Data merk yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                })
            });
        });
    </script>
@endpush