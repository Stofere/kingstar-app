@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Dashboard Kasir</h1>
    <p>Selamat datang di dashboard kasir. Klik tombol di bawah untuk menginputkan penjualan.</p>
    <a href="{{ route('kasir.penjualan.create') }}" class="btn btn-primary">Input Penjualan</a>
</div>
@endsection


