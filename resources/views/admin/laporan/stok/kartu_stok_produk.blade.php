@extends('layouts.app') 

@section('title', 'Kartu Stok: ' . $produk->nama)

@push('styles')
    <style>
        .kartu-stok-table th, .kartu-stok-table td {
            font-size: 0.85rem; /* Ukuran font agar tidak terlalu besar */
            vertical-align: middle;
            padding: 0.5rem; /* Padding yang konsisten */
        }
        .table-sm th, .table-sm td {
            padding: 0.4rem;
        }
        .info-header p {
            margin-bottom: 0.25rem; /* Kurangi margin pada info header */
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="info-header">
            <h1 class="mb-1 h3">Kartu Stok Produk</h1>
            <h2 class="mb-0 h5 text-muted">Produk: <span class="fw-bold">{{ $produk->nama }}</span> ({{ $produk->kode_produk ?? '-' }})</h2>
            {{-- Menampilkan periode yang digunakan untuk laporan --}}
            <p class="mb-0 text-muted">
                Periode Laporan: 
                <span class="fw-bold">{{ $tanggalMulai->isoFormat('D MMMM YYYY') }}</span> s/d 
                <span class="fw-bold">{{ $tanggalSelesai->isoFormat('D MMMM YYYY') }}</span>
            </p>
        </div>
        <a href="{{ route('admin.laporan.stok.ringkasan_produk') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Status Stok
        </a>
    </div>

    {{-- Form Filter Tanggal --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.laporan.stok.kartu_stok.data', $produk->id) }}" class="row gx-2 gy-2 align-items-end">
                <div class="col-md-5">
                    <label for="tanggal_mulai" class="form-label">Dari Tanggal:</label>
                    <input type="date" class="form-control form-control-sm" id="tanggal_mulai" name="tanggal_mulai" 
                           value="{{ $tanggalMulai->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-5">
                    <label for="tanggal_selesai" class="form-label">Sampai Tanggal:</label>
                    <input type="date" class="form-control form-control-sm" id="tanggal_selesai" name="tanggal_selesai" 
                           value="{{ $tanggalSelesai->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-filter"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>


    <div class="card shadow-sm">
        <div class="card-header bg-success text-white"> {{-- Ubah warna header --}}
            <h5 class="mb-0"><i class="bi bi-list-columns-reverse me-2"></i>Detail Pergerakan Stok</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal & Waktu</th>
                            <th>Jenis Transaksi</th>
                            <th>No. Referensi</th>
                            <th class="text-center">Masuk</th>
                            <th class="text-center">Keluar</th>
                            <th class="text-end">Saldo</th>
                            <th>Keterangan Tambahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataUntukView as $data)
                            <tr>
                                <td class="text-nowrap">{{ $data['tanggal_display'] }}</td>
                                <td>{{ $data['jenis_transaksi_display'] }}</td>
                                <td>
                                    @if(isset($data['referensi_link']) && $data['referensi_link'])
                                        <a href="{{ $data['referensi_link'] }}" target="_blank">{{ $data['nomor_referensi_display'] }}</a>
                                    @else
                                        {{ $data['nomor_referensi_display'] }}
                                    @endif
                                </td>
                                <td class="text-center">{{ $data['masuk_display'] }}</td>
                                <td class="text-center">{{ $data['keluar_display'] }}</td>
                                <td class="text-end fw-bold">{{ $data['saldo_display'] }}</td>
                                <td>{{ $data['keterangan_tambahan_display'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada pergerakan stok pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(!empty($pergerakanStokDenganSaldo))
        <div class="card-footer text-muted">
            {{-- Saldo awal yang dihitung sebelum periode filter --}}
            <small>Perhitungan Saldo Awal (sebelum {{ $tanggalMulai->isoFormat('D MMM YYYY') }}) untuk produk {{ $produk->nama }}: {{ $saldoAwalKalkulasi }} {{ $produk->satuan }}</small>
        </div>
        @endif
    </div>
</div>
@endsection
