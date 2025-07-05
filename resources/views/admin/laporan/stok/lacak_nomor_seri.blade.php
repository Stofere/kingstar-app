@extends('layouts.app')

@section('title', isset($nomorSeriDicari) ? 'Hasil Lacak Nomor Seri: ' . $nomorSeriDicari : 'Lacak Riwayat Nomor Seri')

@push('styles')
<style>
    .result-header {
        background-color: #e9f5ff; /* Warna biru muda untuk header hasil */
        border: 1px solid #b8daff;
    }
    .status-badge {
        font-size: 0.9rem;
        padding: 0.4em 0.8em;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Lacak Riwayat Nomor Seri</h1>

    {{-- Form Pencarian --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Pencarian Riwayat</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.laporan.stok.lacak_nomor_seri.result') }}" method="POST">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label for="nomor_seri" class="form-label">Masukkan Nomor Seri:</label>
                        <input type="text" class="form-control" id="nomor_seri" name="nomor_seri" value="{{ $nomorSeriDicari ?? '' }}" placeholder="Ketik nomor seri yang akan dilacak..." required autofocus>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Lacak Riwayat</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Hasil Pencarian (ditampilkan jika ada variabel $riwayat) --}}
    @if(isset($riwayat))
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Hasil Pelacakan untuk Nomor Seri: {{ $nomorSeriDicari }}</h5>
        </div>
        <div class="card-body">
            
            {{-- Status Terkini --}}
            <div class="alert alert-info result-header">
                <h6 class="alert-heading mb-1">Status Terkini</h6>
                <p class="mb-1">
                    @if($statusTerkini['status'] === 'TERSEDIA')
                        <span class="badge status-badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> TERSEDIA</span>
                    @else
                         <span class="badge status-badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i> {{ $statusTerkini['status'] }}</span>
                    @endif
                </p>
                <small class="text-muted">
                    <i class="bi bi-geo-alt-fill me-1"></i> Lokasi/Batch Terakhir: {{ $statusTerkini['lokasi'] }}
                </small>
            </div>

            {{-- Tabel Riwayat --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis Transaksi</th>
                            <th>No. Referensi</th>
                            <th class="text-center">Masuk</th>
                            <th class="text-center">Keluar</th>
                            <th>Keterangan Tambahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayat as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->tanggal_transaksi->isoFormat('D MMM YYYY, HH:mm') }}</td>
                                <td>
                                    @if($log->jumlah_masuk > 0)
                                        <span class="badge bg-success-subtle text-success-emphasis">{{ $log->jenis_transaksi_display }}</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger-emphasis">{{ $log->jenis_transaksi_display }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->referensi_link)
                                        <a href="{{ $log->referensi_link }}" target="_blank" title="Lihat Dokumen">{{ $log->referensi_text }} <i class="bi bi-box-arrow-up-right small"></i></a>
                                    @else
                                        {{ $log->referensi_text }}
                                    @endif
                                </td>
                                <td class="text-center text-success fw-bold">{{ $log->jumlah_masuk > 0 ? '+' . $log->jumlah_masuk : '-' }}</td>
                                <td class="text-center text-danger fw-bold">{{ $log->jumlah_keluar > 0 ? '-' . $log->jumlah_keluar : '-' }}</td>
                                <td>{{ $log->keterangan_display }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada riwayat ditemukan untuk nomor seri ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection