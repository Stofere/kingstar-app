@extends('layouts.app')

@section('title', 'Edit Supplier: ' . $supplier->nama)

@section('content')
<div class="container">
    <h1 class="mb-4">Edit Supplier: <span class="fw-normal">{{ $supplier->nama }}</span></h1>
     <div class="card shadow-sm">
        <div class="card-header bg-light">
             <h5 class="mb-0">Form Edit Supplier</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.supplier.update', $supplier->id) }}" method="POST">
                 @method('PUT')
                 @include('admin.supplier.form', ['supplier' => $supplier])
            </form>
        </div>
    </div>
</div>
@endsection