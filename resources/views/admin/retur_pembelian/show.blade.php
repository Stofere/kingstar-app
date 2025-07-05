{{-- Ganti SELURUH isi file resources/views/admin/retur_pembelian/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Detail Retur Pembelian: ' . $returPembelian->nomor_retur)
@section('content')
<div class="container-fluid">
    {{-- ... Header Halaman ... --}}
    <div class="card shadow-sm">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Retur Pembelian No: {{ $returPembelian->nomor_retur }}</h5>
        </div>
        <div class="card-body">
            {{-- Info Umum --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Tanggal Retur:</strong> {{ \Carbon\Carbon::parse($returPembelian->tanggal_retur)->isoFormat('D MMM YYYY, HH:mm') }}</p>
                    <p><strong>Admin Proses:</strong> {{ $returPembelian->pengguna->nama ?? '-' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Supplier Tujuan:</strong> {{ $returPembelian->supplier->nama ?? 'N/A' }}</p>
                    <p><strong>Status Retur:</strong> <span class="badge bg-info">{{ $returPembelian->status }}</span></p>
                </div>
            </div>

            {{-- Rincian Item --}}
            <h6 class="border-bottom pb-2 mb-3">Rincian Item yang Diretur</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produk & Batch Asal</th>
                            <th class="text-center">Jumlah</th>
                            <th>Alasan</th>
                            <th>Tindakan Diharapkan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returPembelian->detailReturPembelian as $index => $detail)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $detail->stokBarang->produk->nama ?? 'N/A' }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        Dari Batch ID: {{ $detail->id_stok_barang }}
                                        @if($detail->nomor_seri_diretur)
                                            | SN: {{ $detail->nomor_seri_diretur }}
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">{{ $detail->jumlah_retur }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', strtolower($detail->alasan_retur))) }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', strtolower($detail->tindakan_lanjut_supplier))) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

           <div class="card mt-4">
                <div class="card-header">
                    Tindak Lanjut
                </div>
                <div class="card-body text-center">
                    @php
                        $statusHeader = $returPembelian->status;
                        // Ambil tindakan dari item pertama sebagai perwakilan
                        $tindakanSupplier = $returPembelian->detailReturPembelian->first()->tindakan_lanjut_supplier ?? '';
                    @endphp

                    {{-- Skenario 1: Retur masih dalam proses awal --}}
                    @if ($statusHeader === 'PROSES')
                        <div class="alert alert-warning">
                            Status retur ini masih dalam proses. Jika Anda sudah mendapatkan konfirmasi dari supplier, silakan update statusnya.
                        </div>
                        <a href="{{ route('admin.retur_pembelian.edit', $returPembelian->id) }}" class="btn btn-warning">
                            <i class="bi bi-pencil-square me-1"></i> Update Status dari Supplier
                        </a>

                    {{-- Skenario 2: Supplier akan ganti barang, TAPI barangnya BELUM diterima --}}
                    @elseif ($tindakanSupplier === 'SELESAI_DIGANTI' && $returPembelian->penerimaanPengganti->isEmpty())
                        <div class="alert alert-primary"> {{-- Warna biru untuk Menunggu --}}
                            <h5 class="alert-heading">Menunggu Barang Pengganti Tiba</h5>
                            <p class="mb-0">
                                Supplier telah mengkonfirmasi akan mengirim barang pengganti. Saat barang fisik tiba di gudang, lanjutkan proses dengan menekan tombol di bawah ini.
                            </p>
                        </div>
                        <a href="{{ route('gudang.penerimaan.create', ['id_retur_pembelian' => $returPembelian->id]) }}" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Catat Penerimaan Barang Pengganti
                        </a>
                    
                    {{-- Skenario 3: Barang pengganti SUDAH diterima --}}
                    @elseif ($tindakanSupplier === 'SELESAI_DIGANTI' && $returPembelian->penerimaanPengganti->isNotEmpty())
                        @php
                            // Ambil tanggal penerimaan pertama sebagai referensi
                            $tanggalTerima = \Carbon\Carbon::parse($returPembelian->penerimaanPengganti->first()->tanggal_transaksi)->isoFormat('D MMMM YYYY, HH:mm');
                        @endphp
                        <div class="alert alert-success">
                            <h5 class="alert-heading"><i class="bi bi-check-circle-fill"></i> Proses Selesai: Barang Pengganti Diterima</h5>
                            <p class="mb-0">
                                Barang pengganti untuk retur ini telah diterima oleh Gudang pada tanggal <strong>{{ $tanggalTerima }}</strong>.
                                <br>Tidak ada aksi lebih lanjut yang diperlukan untuk nota retur ini.
                            </p>
                        </div>

                    {{-- Skenario 4: Proses selesai karena refund atau ditolak --}}
                    @elseif (in_array($tindakanSupplier, ['SELESAI_DIREFUND', 'DITOLAK_SUPPLIER']))
                        <div class="alert alert-dark">
                            <h5 class="alert-heading">Proses Selesai</h5>
                            <p class="mb-0">
                                Proses untuk retur ini telah selesai dengan status: <strong>{{ ucwords(str_replace('_', ' ', strtolower($tindakanSupplier))) }}</strong>. Tidak ada aksi lebih lanjut yang diperlukan.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Catatan Global --}}
            @if($returPembelian->catatan_internal_retur)
                <div class="mt-3">
                    <strong>Catatan Internal:</strong>
                    <p class="fst-italic bg-light p-2 rounded">{{ $returPembelian->catatan_internal_retur }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection