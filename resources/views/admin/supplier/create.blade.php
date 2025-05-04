@extends('layouts.app')

@section('title', 'Tambah Supplier Baru')

@section('content')
<div class="container">
    <h1 class="mb-4">Tambah Supplier Baru</h1>
    <div class="card shadow-sm">
        <div class="card-header bg-light">
             <h5 class="mb-0">Form Tambah Supplier</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.supplier.store') }}" method="POST">
                @include('admin.supplier.form', ['supplier' => $supplier])
            </form>
        </div>
    </div>
</div>
@endsection