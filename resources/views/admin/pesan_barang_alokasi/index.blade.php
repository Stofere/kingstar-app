@extends('layouts.app') 

@section('title', 'Daftar Pesan Barang Menunggu Alokasi')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Pesan Barang Menunggu Alokasi Stok</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Tgl Pesan</th>
                                    <th>Pelanggan</th>
                                    <th>Item Dipesan (Ringkasan)</th>
                                    <th>Total Pesanan</th>
                                    <th>Uang Muka</th>
                                    <th>Status Bayar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pesananMenungguAlokasi as $pesanan)
                                    <tr>
                                        <td>{{ $pesanan->nomor_penjualan }}</td>
                                        <td>{{ $pesanan->tanggal_penjualan->isoFormat('D MMM YYYY, HH:mm') }}</td>
                                        <td>{{ $pesanan->pelanggan->nama ?? 'Umum' }}</td>
                                        <td>
                                            @foreach($pesanan->detailPenjualan as $detail)
                                                <div>{{ $detail->produk->nama }} ({{ $detail->jumlah }} unit)</div>
                                            @endforeach
                                        </td>
                                        <td class="text-end">{{ number_format($pesanan->total_harga, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($pesanan->uang_muka, 0, ',', '.') }}</td>
                                        <td><span class="badge bg-{{ $pesanan->status_pembayaran == 'DP' ? 'warning text-dark' : 'info' }}">{{ $pesanan->status_pembayaran }}</span></td>
                                        <td>
                                            <a href="{{ route('admin.pesan_barang_alokasi.form', $pesanan->id) }}" class="btn btn-sm btn-info" title="Alokasikan Stok">
                                                <i class="bi bi-check2-square"></i> Alokasikan
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Tidak ada pesanan yang menunggu alokasi saat ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $pesananMenungguAlokasi->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection