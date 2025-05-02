@extends('layouts.app')

@section('title', 'Kelola Produk')

@push('styles')
    {{-- DataTables CSS (tetap diperlukan) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #produk-table img { max-width: 100%; height: auto; max-height: 50px; object-fit: contain; }
        #produk-table .action-buttons form { margin-bottom: 0; }
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
                {{-- Tabel HTML biasa --}}
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
                        {{-- Loop data dari Controller menggunakan Blade --}}
                        @forelse ($produk as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                {{-- Tampilkan gambar --}}
                                @if ($item->gambar && Storage::exists('public/produk/' . $item->gambar))
                                    <img src="{{ Storage::url('produk/' . $item->gambar) }}" alt="{{ $item->nama }}" height="50">
                                @else
                                    <span class="text-muted small">(No Image)</span>
                                @endif
                            </td>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->merk ? $item->merk->nama : '-' }}</td>
                            <td>{{ $item->kode_produk ?? '-' }}</td>
                            <td>{{ 'Rp ' . number_format($item->harga_jual_standart ?? 0, 0, ',', '.') }}</td>
                            <td>
                                {{-- Tampilkan status serial --}}
                                @if($item->memiliki_serial)
                                    <span class="badge bg-success">Ya</span>
                                @else
                                    <span class="badge bg-secondary">Tidak</span>
                                @endif
                            </td>
                             <td>
                                {{-- Tampilkan status produk --}}
                                @if($item->status)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td class="action-buttons">
                                {{-- Tombol Aksi --}}
                                <a href="{{ route('admin.produk.edit', $item->id) }}" class="btn btn-warning btn-sm me-1" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('admin.produk.destroy', $item->id) }}" method="POST" class="d-inline form-delete">
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
                            {{-- Pesan jika tidak ada data --}}
                            <td colspan="9" class="text-center">Belum ada data produk.</td>
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
    {{-- DataTables JS (tetap diperlukan) --}}
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    {{-- SweetAlert2 --}}
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Inisialisasi DataTables untuk Client-Side --}}
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables pada tabel HTML
            var table = $('#produk-table').DataTable({
                responsive: true, // Aktifkan responsivitas
                // Tidak perlu 'processing', 'serverSide', 'ajax', 'columns' untuk client-side
                language: { // Opsi untuk bahasa Indonesia
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
                },
                 // Optional: Atur default ordering jika perlu
                 order: [[2, 'asc']] // Order by kolom ke-3 (Nama Produk) ascending
            });

            // Konfirmasi Hapus (tetap sama, bekerja pada tombol di HTML)
            $('#produk-table').on('submit', '.form-delete', function(e) {
                e.preventDefault();
                var form = this;
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
                        form.submit();
                    }
                })
            });
        });
    </script>
@endpush