@extends('layouts.app')

@section('title', 'Selesaikan Pesan Barang: ' . $penjualan->nomor_penjualan)

@push('styles')
    {{-- Tambahkan style jika perlu --}}
    <style>
        .item-pesanan-detail { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: .25rem;}
        .item-pesanan-detail strong { display: inline-block; min-width: 100px;}
        .serial-list span { display: inline-block; margin-right: 5px; padding: .2em .6em .3em; font-size: 75%; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25rem; background-color: #6c757d;}
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <form action="{{ route('kasir.pesan_barang_selesai.store', $penjualan->id) }}" method="POST" id="form-selesaikan-pesan-barang">
        @csrf
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Proses Penyelesaian Pesanan: {{ $penjualan->nomor_penjualan }}</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-3"><strong>No. Pesanan:</strong><br>{{ $penjualan->nomor_penjualan }}</div>
                    <div class="col-md-3"><strong>Pelanggan:</strong><br>{{ $penjualan->pelanggan->nama ?? 'Umum' }}</div>
                    <div class="col-md-3"><strong>Tgl Pesan:</strong><br>{{ $penjualan->tanggal_penjualan->isoFormat('D MMM YYYY, HH:mm') }}</div>
                    <div class="col-md-3"><strong>Kasir Awal:</strong><br>{{ $penjualan->pengguna->nama ?? '-' }}</div>
                </div>
                <hr>

                <h6>Item yang Dipesan dan Dialokasikan:</h6>
                @foreach ($penjualan->detailPenjualan as $detail)
                    <div class="item-pesanan-detail">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Produk:</strong> {{ $detail->produk->nama }} ({{ $detail->produk->kode_produk }})<br>
                                <strong>Jumlah Dipesan:</strong> {{ $detail->jumlah }} unit
                            </div>
                            <div class="col-md-6">
                                <strong>Alokasi Stok dari Admin:</strong><br>
                                @if ($detail->stokAlokasi->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')->isNotEmpty())
                                    @foreach ($detail->stokAlokasi->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN') as $alokasi)
                                        <small>
                                            Dari Batch ID: {{ $alokasi->id_stok_barang }} (Qty: {{ $alokasi->jumlah_diambil }})
                                            @if ($detail->produk->memiliki_serial && $alokasi->nomor_seri_terkait)
                                                <br>SN Dialokasikan: <span class="serial-list">
                                                    @foreach(explode(',', $alokasi->nomor_seri_terkait) as $sn)
                                                        <span>{{ $sn }}</span>
                                                    @endforeach
                                                </span>
                                            @endif
                                        </small><br>
                                    @endforeach
                                @else
                                    <span class="text-danger">Belum ada stok yang dialokasikan oleh Admin untuk item ini.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                <hr>

                <h6>Rincian Pembayaran Pesanan:</h6>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td>Total Harga Pesanan</td>
                                <td class="text-end fw-bold">{{ number_format($penjualan->total_harga, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Uang Muka (DP)</td>
                                <td class="text-end">{{ number_format($penjualan->uang_muka, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-danger">
                                <td class="fw-bold">Sisa Pembayaran</td>
                                <td class="text-end fw-bold">{{ number_format($penjualan->sisa_pembayaran, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        @if ($penjualan->status_penjualan == 'MENUNGGU_PELUNASAN' && $penjualan->sisa_pembayaran > 0)
                            <div class="mb-3">
                                <label for="metode_pembayaran_pelunasan" class="form-label required-label">Metode Pembayaran Pelunasan:</label>
                                <select class="form-select @error('metode_pembayaran_pelunasan') is-invalid @enderror" id="metode_pembayaran_pelunasan" name="metode_pembayaran_pelunasan" required>
                                    <option value="">Pilih Metode...</option>
                                    @foreach($metodePembayaran as $value => $label)
                                        <option value="{{ $value }}" {{ old('metode_pembayaran_pelunasan') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('metode_pembayaran_pelunasan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="uang_bayar_pelunasan" class="form-label required-label">Uang Bayar Pelunasan:</label>
                                <input type="text" class="form-control input-rupiah @error('uang_bayar_pelunasan') is-invalid @enderror" id="uang_bayar_pelunasan" name="uang_bayar_pelunasan" data-inputmask-alias="numeric" required data-sisa-bayar="{{ $penjualan->sisa_pembayaran }}">
                                @error('uang_bayar_pelunasan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Kembalian Pelunasan:</label>
                                <input type="text" class="form-control-plaintext total-display text-end text-success" id="display_kembalian_pelunasan" value="Rp 0" readonly>
                            </div>
                        @elseif ($penjualan->status_penjualan == 'SIAP_DIAMBIL') {{-- Berarti sudah lunas DP --}}
                            <div class="alert alert-success">
                                Pesanan ini sudah lunas (via DP). Silakan konfirmasi pengambilan barang.
                            </div>
                            <input type="hidden" name="metode_pembayaran_pelunasan" value="{{ $penjualan->metode_pembayaran }}"> {{-- Kirim metode bayar asli jika sudah lunas --}}
                        @else
                             <div class="alert alert-info">
                                Tidak ada sisa pembayaran untuk pesanan ini atau status tidak memerlukan pelunasan saat ini.
                            </div>
                        @endif
                    </div>
                </div>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Perhatian:</strong> Pastikan pelanggan sudah memeriksa kondisi barang dan semua item sesuai sebelum menyelesaikan transaksi. Barang yang sudah diambil tidak dapat dikembalikan kecuali ada perjanjian khusus.
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('kasir.pesan_barang_selesai.index') }}" class="btn btn-secondary">Kembali ke Daftar</a>
                @if(in_array($penjualan->status_penjualan, ['MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL']))
                    <button type="submit" class="btn btn-success" id="btn-selesaikan-pesanan">
                        <i class="bi bi-check-circle-fill me-1"></i> Selesaikan & Ambil Barang
                    </button>
                @else
                     <button type="submit" class="btn btn-success" disabled>
                        <i class="bi bi-check-circle-fill me-1"></i> Selesaikan & Ambil Barang
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    {{-- jQuery, Bootstrap JS, Inputmask, SweetAlert2 sudah di-include di layout utama atau di sini jika perlu --}}
    
    <script>
        // Fungsi helper global (jika belum ada di file JS terpisah)
        function formatRupiah(angka, prefix = 'Rp ') {
            if (isNaN(angka) || angka === null || angka === undefined) return prefix + '0';
            let number_string = Math.round(angka).toString().replace(/[^,\d]/g, ''),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);
            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
            return prefix + rupiah;
        }

        function parseRupiah(rupiahString) {
            if (typeof rupiahString !== 'string') return 0;
            return parseInt(rupiahString.replace(/[^0-9]/g, ''), 10) || 0;
        }

        $(document).ready(function() {
            $('.input-rupiah').inputmask({
                alias: 'numeric', groupSeparator: '.', radixPoint: ',', digits: 0, autoGroup: true,
                prefix: 'Rp ', rightAlign: false, removeMaskOnSubmit: true,
                oncleared: function () { $(this).val(''); }
            });

            $('#uang_bayar_pelunasan').on('input', function() {
                const sisaBayar = parseFloat($(this).data('sisa-bayar')) || 0;
                const uangBayar = parseRupiah($(this).val());
                const kembalian = uangBayar - sisaBayar;
                $('#display_kembalian_pelunasan').val(formatRupiah(Math.max(0, kembalian)));
            });

            $('#form-selesaikan-pesan-barang').on('submit', function(e){
                const sisaPembayaran = parseFloat($('#uang_bayar_pelunasan').data('sisa-bayar')) || 0;
                const uangBayarPelunasan = parseRupiah($('#uang_bayar_pelunasan').val());

                // Hanya validasi uang bayar jika memang ada sisa pembayaran
                if (sisaPembayaran > 0 && uangBayarPelunasan < sisaPembayaran) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Pembayaran Kurang',
                        text: 'Jumlah uang bayar pelunasan kurang dari sisa pembayaran.',
                    });
                    return false;
                }

                // Konfirmasi sebelum submit
                e.preventDefault(); // Selalu cegah submit default dulu
                Swal.fire({
                    title: 'Konfirmasi Penyelesaian',
                    text: "Apakah Anda yakin ingin menyelesaikan pesanan ini dan barang sudah diambil pelanggan?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Selesaikan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#btn-selesaikan-pesanan').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                        $(this).off('submit').submit(); // Hapus handler, lalu submit form asli
                    }
                });
            });
        });
    </script>
@endpush