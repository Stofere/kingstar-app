@extends('layouts.app')

@section('title', 'Daftar Pesan Barang Menunggu Penyelesaian')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Pesan Barang Menunggu Pelunasan / Pengambilan</h5>
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
                            <th>Total Pesanan</th>
                            <th>DP</th>
                            <th>Sisa Bayar</th>
                            <th>Status</th>
                            <th>Kasir Pesan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pesananMenungguPenyelesaian as $pesanan)
                            <tr>
                                <td>{{ $pesanan->nomor_penjualan }}</td>
                                <td>{{ $pesanan->tanggal_penjualan->isoFormat('D MMM YYYY, HH:mm') }}</td>
                                <td>{{ $pesanan->pelanggan->nama ?? 'Umum' }}</td>
                                <td class="text-end">{{ number_format($pesanan->total_harga, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($pesanan->uang_muka, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($pesanan->sisa_pembayaran, 0, ',', '.') }}</td>
                                <td>
                                    @if ($pesanan->status_penjualan == 'MENUNGGU_PELUNASAN')
                                        <span class="badge bg-warning text-dark">Menunggu Pelunasan</span>
                                    @elseif ($pesanan->status_penjualan == 'SIAP_DIAMBIL')
                                        <span class="badge bg-info">Siap Diambil (Sudah Lunas DP)</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $pesanan->status_penjualan }}</span>
                                    @endif
                                </td>
                                <td>{{ $pesanan->pengguna->nama ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('kasir.pesan_barang_selesai.form', $pesanan->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-cash-coin me-1"></i> Proses
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada pesan barang yang menunggu penyelesaian saat ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pesananMenungguPenyelesaian->hasPages())
                <div class="mt-3">
                    {{ $pesananMenungguPenyelesaian->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    @if(session('last_penjualan_id_for_nota'))
        var penjualanId = "{{ session('last_penjualan_id_for_nota') }}";
        // Pastikan route 'kasir.penjualan.nota' sudah benar dan bisa diakses
        // Jika nota untuk pesan barang yang selesai sama dengan nota penjualan biasa:
        var urlNota = "{{ route('kasir.penjualan.nota', ['id' => ':id_placeholder']) }}".replace(':id_placeholder', penjualanId);
        
        var notaWindow = window.open(urlNota, '_blank');
        if (notaWindow) {
            notaWindow.focus();
        } else {
            Swal.fire(
                'Nota Siap Dicetak!',
                'Nota untuk transaksi {{ session("last_penjualan_nomor") }} siap. Klik <a href="'+urlNota+'" target="_blank" class="btn btn-sm btn-info">di sini</a> untuk membuka.',
                'info'
            );
        }
    @endif
</script>
@endpush