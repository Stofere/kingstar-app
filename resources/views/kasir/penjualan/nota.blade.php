<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Penjualan - {{ $penjualan->nomor_penjualan }}</title>
    {{-- Bootstrap hanya digunakan untuk tombol aksi di luar area nota --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif; /* Font yang lebih umum */
            font-size: 12pt; /* Ukuran font dasar sedikit lebih besar */
            margin: 0;
            padding: 0; /* Padding akan diatur di container */
            background-color: #e9e9e9; /* Warna latar belakang halaman agar kontainer nota terlihat */
            color: #333;
        }
        .nota-container {
            width: 90%; /* Lebar kontainer utama */
            max-width: 800px; /* Batas lebar maksimal */
            margin: 20px auto; /* Posisi tengah dengan margin atas-bawah */
            border: 1px solid #cccccc;
            padding: 25px; /* Padding internal kontainer */
            background-color: #ffffff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1); /* Sedikit bayangan untuk efek raised */
        }
        .header-nota {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        .header-nota h3 { /* Nama Toko */
            margin: 0 0 5px 0;
            font-size: 1.8em; /* Lebih besar */
            font-weight: bold;
            color: #2c3e50;
        }
        .header-nota h4 { /* Judul Nota (Nota Penjualan / Bukti Pesan) */
            margin: 10px 0 0 0;
            font-size: 1.4em;
            font-weight: bold;
        }
        .header-nota p { /* Alamat & Telepon Toko */
            margin: 4px 0;
            font-size: 0.9em;
            color: #555;
        }
        .info-transaksi {
            margin-bottom: 25px;
            font-size: 1.0em; /* Sedikit lebih besar dari sebelumnya */
            line-height: 1.6;
        }
        .info-transaksi table {
            width: 100%;
            border-collapse: collapse; /* Menghilangkan spasi antar sel */
        }
        .info-transaksi td {
            padding: 6px 0; /* Padding atas-bawah untuk setiap baris info */
        }
        .info-transaksi td:first-child { /* Label (No. Nota, Tanggal, dll.) */
            width: 130px; /* Lebar tetap untuk label */
            font-weight: bold;
            padding-right: 10px;
        }
        .info-transaksi td:nth-child(2) { /* Titik dua pemisah */
            width: 15px;
            text-align: center;
        }

        .table-items-nota {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 1.0em;
        }
        .table-items-nota th, .table-items-nota td {
            border: 1px solid #dddddd; /* Garis solid tipis untuk sel tabel */
            padding: 10px; /* Padding lebih besar dalam sel */
            text-align: left;
            vertical-align: top;
        }
        .table-items-nota th {
            background-color: #f7f7f7; /* Warna latar header tabel */
            font-weight: bold;
            color: #333;
        }
        .table-items-nota .text-end {
            text-align: right;
        }
        .item-detail .product-name {
            display: block;
            font-weight: 500; /* Sedikit tebal untuk nama produk */
        }
        .item-detail .serial-numbers {
            font-size: 0.85em;
            color: #666;
            padding-left: 8px; /* Indentasi untuk SN */
            display: block;
            margin-top: 4px;
        }
        .rincian-pembayaran {
            margin-top: 25px;
            font-size: 1.0em;
        }
        .rincian-pembayaran table {
            width: 60%; /* Rincian pembayaran tidak perlu full width */
            margin-left: auto; /* Rata kanan */
            border-collapse: collapse;
        }
        .rincian-pembayaran td {
            padding: 8px 5px;
        }
        .rincian-pembayaran td:first-child {
             /* Biarkan otomatis */
        }
        .rincian-pembayaran .total-akhir-row td { /* Baris Total Akhir */
            font-weight: bold;
            font-size: 1.15em; /* Total akhir lebih menonjol */
            border-top: 2px solid #333;
            border-bottom: 2px solid #333; /* Garis ganda di total */
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .info-garansi-container {
            margin-top: 30px;
            border-top: 1px solid #eeeeee;
            padding-top: 15px;
        }
        .info-garansi-container h5 { /* Judul Informasi Garansi */
            font-size: 1.1em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .garansi-item {
            font-size: 0.95em;
            margin-bottom: 6px;
            padding-left: 10px; /* Indentasi untuk item garansi */
        }
        .footer-nota {
            text-align: center;
            margin-top: 35px;
            font-size: 0.9em;
            border-top: 1px solid #eeeeee;
            padding-top: 20px;
            color: #777777;
        }
        .footer-nota p {
            margin: 5px 0;
        }

        .actions-nota { /* Area tombol di luar nota-container */
            text-align: center;
            margin: 25px auto; /* Di tengah halaman, di bawah nota-container */
            padding: 15px;
             width: 90%;
            max-width: 800px;
            /* Tidak perlu background jika body sudah berwarna, atau bisa ditambahkan jika ingin kontras */
        }

        @media print {
            body {
                font-size: 10pt; /* Ukuran font bisa disesuaikan untuk print */
                margin: 0;
                padding: 0;
                background-color: #fff; /* Latar putih saat print */
            }
            .nota-container {
                width: 100%; /* Gunakan lebar penuh kertas */
                max-width: none; /* Hilangkan max-width untuk print */
                border: none; /* Hilangkan border kontainer saat print */
                margin: 0;
                padding: 10mm; /* Margin cetak standar */
                box-shadow: none; /* Hilangkan bayangan */
            }
            .actions-nota {
                display: none !important; /* Sembunyikan tombol saat print */
            }
            .header-nota {
                border-bottom: 2px solid #000; /* Pastikan garis terlihat */
            }
            .table-items-nota th {
                background-color: #f0f0f0 !important; /* Warna header tabel untuk print */
                -webkit-print-color-adjust: exact; /* Untuk browser berbasis WebKit */
                color-adjust: exact; /* Standar */
            }
            .table-items-nota th, .table-items-nota td {
                border: 1px solid #999; /* Garis tabel lebih jelas untuk print */
                padding: 8px;
            }
            .rincian-pembayaran table {
                width: 50%; /* Mungkin perlu disesuaikan lagi untuk print */
            }
            .rincian-pembayaran .total-akhir-row td {
                font-size: 1.1em;
                 border-top: 2px solid #000;
                border-bottom: 2px solid #000;
            }
            .footer-nota {
                font-size: 0.85em;
            }
        }
    </style>
</head>
<body>
    <div class="nota-container">
        {{-- 1. Bagian Header (Kop Nota) --}}
        <div class="header-nota">
            <h3>{{ $namaToko ?? 'Nama Toko Anda' }}</h3>
            @if(isset($alamatToko) && $alamatToko) <p>{{ $alamatToko }}</p> @endif
            @if(isset($teleponToko) && $teleponToko) <p>Telp: {{ $teleponToko }}</p> @endif
            {{-- Garis bawah sudah dihandle oleh border-bottom pada .header-nota --}}
            <h4>
                @if($penjualan->tipe_transaksi == 'PESAN_BARANG')
                    BUKTI PESAN BARANG (DP)
                @else
                    NOTA PENJUALAN
                @endif
            </h4>
        </div>

        {{-- 2. Informasi Transaksi --}}
        <div class="info-transaksi">
            <table>
                <tr>
                    <td>No. Nota</td><td>:</td><td>{{ $penjualan->nomor_penjualan }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td><td>:</td><td>{{ \Carbon\Carbon::parse($penjualan->tanggal_penjualan)->isoFormat('D MMMM YYYY, HH:mm') }}</td>
                </tr>
                <tr>
                    <td>Kasir</td><td>:</td><td>{{ $penjualan->pengguna->nama ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Pelanggan</td><td>:</td><td>{{ $penjualan->pelanggan->nama ?? 'Umum' }}</td>
                </tr>
            </table>
        </div>

        {{-- 3. Detail Barang (Dalam Bentuk Tabel) --}}
        <table class="table-items-nota">
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($penjualan->detailPenjualan as $index => $detail)
                    <tr>
                        <td class="item-detail">
                            <span class="product-name">{{ $detail->nama_produk_snapshot ?: $detail->produk->nama }}</span>
                            @if($detail->nomor_seri_terjual)
                                <span class="serial-numbers">SN: {{ str_replace(',', ', ', $detail->nomor_seri_terjual) }}</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $detail->jumlah }}</td>
                        <td class="text-end">{{ number_format($detail->harga_jual, 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- 4. Rincian Pembayaran --}}
        <div class="rincian-pembayaran">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td class="text-end">{{ number_format($penjualan->detailPenjualan->sum('subtotal'), 0, ',', '.') }}</td>
                </tr>
                @if(isset($penjualan->diskon_nominal) && $penjualan->diskon_nominal > 0)
                    <tr>
                        <td>Diskon</td>
                        <td class="text-end">- {{ number_format($penjualan->diskon_nominal, 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total-akhir-row">
                    <td>
                        @if($penjualan->tipe_transaksi == 'PESAN_BARANG')
                            Total Pesanan
                        @else
                            Total Akhir
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($penjualan->total_harga, 0, ',', '.') }}</td>
                </tr>

                @if($penjualan->tipe_transaksi == 'PESAN_BARANG')
                    <tr>
                        <td>Uang Muka (DP)</td>
                        <td class="text-end">{{ number_format($penjualan->uang_muka, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Sisa Pembayaran</strong></td>
                        <td class="text-end"><strong>{{ number_format($penjualan->sisa_pembayaran, 0, ',', '.') }}</strong></td>
                    </tr>
                @else
                    <tr>
                        <td>Uang Bayar</td>
                        <td class="text-end">
                            {{-- Logika untuk uang bayar bisa disesuaikan --}}
                            {{-- Jika ada field $penjualan->uang_bayar, gunakan itu. Jika tidak, bisa dihitung atau disesuaikan. --}}
                            {{ number_format(($penjualan->uang_bayar ?? $penjualan->total_harga + ($penjualan->kembalian ?? 0)), 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td>Kembalian</td>
                        <td class="text-end">
                            {{ number_format($penjualan->kembalian ?? 0, 0, ',', '.') }}
                        </td>
                    </tr>
                @endif
                 <tr>
                    <td>Metode Bayar</td>
                    <td class="text-end">{{ ucwords(str_replace('_', ' ', $penjualan->metode_pembayaran)) }}</td>
                </tr>
            </table>
        </div>

        {{-- 5. Informasi Garansi --}}
        @php
            $itemsWithGaransi = $penjualan->detailPenjualan->filter(function($detail) {
                return $detail->customer_garansi_berakhir_at !== null;
            });
        @endphp

        @if($itemsWithGaransi->isNotEmpty() && $penjualan->tipe_transaksi !== 'PESAN_BARANG')
            <div class="info-garansi-container">
                <h5>Informasi Garansi:</h5>
                @foreach($itemsWithGaransi as $detail)
                    @php
                        $tipeGaransiDisplay = 'Tidak Diketahui';
                        $tipeGaransiDariBatch = 'Tidak Ada';
                        // Logika untuk mendapatkan tipe garansi dari batch
                        // Ini adalah asumsi dari kode asli Anda, pastikan $detail->stokAlokasi dan relasinya benar
                        if ($detail->stokAlokasi->isNotEmpty() && $detail->stokAlokasi->first()->stokBarang) {
                            $firstBatchType = $detail->stokAlokasi->first()->stokBarang->tipe_garansi ?? 'NONE';
                            if ($firstBatchType === 'RESMI') $tipeGaransiDariBatch = 'Resmi';
                            elseif ($firstBatchType === 'SELF_SERVICE') $tipeGaransiDariBatch = 'Toko';
                        }
                    @endphp
                    <div class="garansi-item">
                        - <strong>{{ $detail->nama_produk_snapshot ?: $detail->produk->nama }}</strong>:
                        Garansi {{ $tipeGaransiDariBatch }}
                        s/d {{ \Carbon\Carbon::parse($detail->customer_garansi_berakhir_at)->isoFormat('D MMMM YYYY') }}
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 6. Bagian Footer --}}
        <div class="footer-nota">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p>** BARANG YANG SUDAH DIBELI TIDAK DAPAT DITUKAR/DIKEMBALIKAN KECUALI ADA PERJANJIAN **</p>
        </div>
    </div>

    {{-- Tombol Aksi di Halaman Nota (menggunakan Bootstrap) --}}
    <div class="actions-nota">
        <button type="button" class="btn btn-primary btn-lg" onclick="window.print();">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer-fill me-1" viewBox="0 0 16 16">
                <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/>
                <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
            </svg>
            Cetak Nota
        </button>
        <button type="button" class="btn btn-secondary btn-lg ms-2" onclick="window.close();">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle me-1" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
            </svg>
            Tutup
        </button>
    </div>

    <script>
        // Opsional: Jika ingin auto-print saat halaman dimuat
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>