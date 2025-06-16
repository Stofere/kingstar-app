@extends('layouts.app')
@section('title', 'Daftar Stok Opname')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Manajemen Stok Opname</h1>
        <a href="{{ route('gudang.stok-opname.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Mulai Sesi Opname Baru</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table id="opname-table" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal Opname</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Dimulai Oleh</th>
                        <th>Diselesaikan Oleh</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#opname-table').DataTable({
        processing: true, serverSide: true,
        ajax: "{{ route('gudang.stok-opname.index') }}",
        columns: [
            { data: 'id', name: 'id' },
            { data: 'tanggal_opname', name: 'tanggal_opname' },
            { data: 'lokasi', name: 'lokasi', defaultContent: 'Semua Lokasi' },
            { data: 'status', name: 'status' },
            { data: 'dimulai_oleh', name: 'dimulai_oleh', orderable: false, searchable: false },
            { data: 'diselesaikan_oleh', name: 'diselesaikan_oleh', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[0, 'desc']]
    });
});
</script>
@endpush