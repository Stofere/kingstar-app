@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@push('styles')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
@endpush

@section('content')
<div class="container">
    <h1>Kelola Pengguna</h1>
    <div class="card">
        <div class="card-header">
            Daftar Pengguna
            <a href="{{ route('admin.pengguna.create') }}" class="btn btn-primary btn-sm float-end">
                <i class="bi bi-plus-lg"></i> Tambah Pengguna
            </a>
        </div>
        <div class="card-body">
            <table id="pengguna-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pengguna as $user)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $user->nama }}</td>
                        <td>{{ $user->username }}</td>
                        <td><span class="badge bg-{{ $user->role == 'ADMIN' ? 'danger' : ($user->role == 'KASIR' ? 'success' : 'info') }}">{{ $user->role }}</span></td>
                        <td><span class="badge bg-{{ $user->status ? 'success' : 'secondary' }}">{{ $user->status ? 'Aktif' : 'Tidak Aktif' }}</span></td>
                        <td>
                            <a href="{{ route('admin.pengguna.edit', $user->id) }}" class="btn btn-warning btn-sm me-1" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            {{-- Tombol Hapus dengan Konfirmasi JS --}}
                            <form action="{{ route('admin.pengguna.destroy', $user->id) }}" method="POST" class="d-inline form-delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- Inisialisasi DataTables --}}
    <script>
        $(document).ready(function() {
            $('#pengguna-table').DataTable({
                responsive: true,
                language: { // Opsi untuk bahasa Indonesia DataTables
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
                },
            });

            // Konfirmasi Hapus
            $('.form-delete').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({ // Menggunakan SweetAlert2 (pastikan sudah di-load)
                    title: 'Apakah Anda Yakin?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
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
     {{-- Pastikan SweetAlert2 sudah di-load, bisa via CDN di app.blade.php atau npm --}}
     <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush