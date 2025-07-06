<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Penjualan - {{ $penjualan->nomor_penjualan }}</title>
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

            .info-retur-container {
                border: 2px dashed #d9534f; /* Garis putus-putus merah */
                background-color: #fdf7f7; /* Latar sedikit merah */
                color: #a94442; /* Warna teks merah gelap */
                padding: 15px;
                margin-bottom: 25px; /* Jarak dengan elemen di bawahnya */
                border-radius: 8px; /* Sudut sedikit melengkung */
            }
            .info-retur-container h5 {
                margin-top: 0;
                font-weight: bold;
                font-size: 1.2em;
            }
            .info-retur-container ul {
                padding-left: 20px;
                margin-bottom: 0;
            }
            .info-retur-container li {
                margin-bottom: 8px;
            }

            .info-lanjutan-pesanan {
            margin-top: 30px;
            border-top: 1px solid #eeeeee;
            padding-top: 20px;
            text-align: center;
            font-size: 0.9em;
            background-color: #f0f8ff; /* Warna latar biru muda */
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #bde0fe;
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
                {{-- ### LOGIKA JUDUL NOTA BARU ### --}}
                @if($penjualan->tipe_transaksi == 'PESAN_BARANG' && $penjualan->status_penjualan !== 'SELESAI')
                    BUKTI PESAN BARANG (DP)
                @else
                    NOTA PENJUALAN
                @endif
            </h4>
        </div>

        {{-- (Blok Pemberitahuan Retur) --}}
        @if($penjualan->retur->isNotEmpty())
            @php
                $totalQtyTerjual = $penjualan->detailPenjualan->sum('jumlah');
                
                // Hitung total qty diretur dengan cara yang baru
                $totalQtyDiretur = 0;
                foreach($penjualan->retur as $returHeader) {
                    $totalQtyDiretur += $returHeader->detailReturPenjualan->sum('jumlah_retur');
                }

                $statusReturTeks = ($totalQtyDiretur >= $totalQtyTerjual) ? 'DIRETUR PENUH' : 'DIRETUR SEBAGIAN';
            @endphp

            <div class="info-retur-container">
                {{-- Judul yang lebih jelas --}}
                <h5><i class="bi bi-info-circle-fill"></i> PEMBERITAHUAN: NOTA INI {{ $statusReturTeks }}</h5>
                
                {{-- Ringkasan yang lebih bersih --}}
                <p class="mb-1">Terdapat <strong>{{ $penjualan->retur->count() }} transaksi retur</strong> yang terkait dengan nota penjualan ini.</p>
                
                {{-- Loop hanya untuk menampilkan link ke nota retur --}}
                <strong>Referensi Nota Retur:</strong>
                <ul>
                    @foreach($penjualan->retur as $returHeader) 
                        <li>
                            <a href="{{ route('admin.proses_retur_pelanggan.show', $returHeader->id) }}" target="_blank">
                                {{ $returHeader->nomor_retur }}
                            </a> 
                            <small class="text-muted">(Tanggal: {{ \Carbon\Carbon::parse($returHeader->tanggal_retur)->isoFormat('D MMM YYYY') }})</small>
                        </li>
                    @endforeach
                </ul>
                <small class="d-block mt-2">Untuk melihat detail item yang diretur, silakan klik nomor referensi di atas.</small>
            </div>
        @endif
        

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
                @foreach($penjualan->detailPenjualan as $detail)
                    <tr>
                        <td class="item-detail">
                            <span class="product-name">{{ $detail->nama_produk_snapshot ?: $detail->produk->nama }}</span>
                            
                            {{-- ### LOGIKA TAMPILKAN NOMOR SERI BARU ### --}}
                            {{-- Tampilkan nomor seri HANYA jika status penjualan sudah SELESAI --}}
                            @if($penjualan->status_penjualan == 'SELESAI' && $detail->nomor_seri_terjual)
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
                    @if($penjualan->status_penjualan == 'SELESAI')
                        <tr>
                            <td>Pelunasan</td>
                            <td class="text-end">{{ number_format($penjualan->sisa_pembayaran, 0, ',', '.') }}</td>
                        </tr>
                         <tr>
                            <td>Total Bayar</td>
                            <td class="text-end">{{ number_format($penjualan->total_harga, 0, ',', '.') }}</td>
                        </tr>
                    @else
                         <tr>
                            <td><strong>Sisa Pembayaran</strong></td>
                            <td class="text-end"><strong>{{ number_format($penjualan->sisa_pembayaran, 0, ',', '.') }}</strong></td>
                        </tr>
                    @endif
                @else {{-- Untuk Penjualan BIASA --}}
                    <tr>
                        <td>Uang Bayar</td>
                        <td class="text-end">{{ number_format(($penjualan->uang_bayar ?? $penjualan->total_harga), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Kembalian</td>
                        <td class="text-end">{{ number_format($penjualan->kembalian ?? 0, 0, ',', '.') }}</td>
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
            $itemsWithGaransi = $penjualan->detailPenjualan->filter(fn($d) => !is_null($d->customer_garansi_berakhir_at));
        @endphp

        @if($penjualan->tipe_transaksi == 'PESAN_BARANG' && $penjualan->status_penjualan !== 'SELESAI')
            <div class="info-lanjutan-pesanan">
                @if($penjualan->estimasi_kirim_at)
                    <p class="mb-2">
                        <strong>Estimasi barang tiba di toko:</strong> {{ \Carbon\Carbon::parse($penjualan->estimasi_kirim_at)->isoFormat('dddd, D MMMM YYYY') }}
                    </p>
                @endif
                <p class="fw-bold">Informasi Ketersediaan Barang:</p>
                <p>
                    Untuk mengkonfirmasi ketersediaan barang pesanan Anda,
                    silakan hubungi kami melalui WhatsApp di nomor berikut:
                </p>
                <p style="font-size: 1.2em; font-weight: bold; color: #198754;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                        <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592-.543 0-1.08-.081-1.598-.235l-.48-.154z"/>
                        <path d="M10.871 9.301c-.146-.364-.263-.465-.437-.493-.174-.028-.372-.028-.543.028-.172.055-.372.172-.543.342-.172.172-.343.372-.465.518-.122.146-.243.263-.437.263-.195 0-.44-.081-.687-.263s-.543-.405-1.057-.872c-.513-.465-1.057-1.08-1.057-1.08-.122-.146-.028-.243.055-.316.082-.073.172-.172.24-.263.07-.1.122-.172.172-.24.055-.081.028-.146-.028-.263s-.372-.872-.518-1.141c-.146-.263-.29-.24-.437-.24-.147 0-.316.028-.465.028s-.343.122-.465.263c-.122.146-.465.437-.465.98s.055 1.141.122 1.316c.07.172.316.518.73.931.413.413.872.82 1.342 1.057.465.235.872.364 1.141.364.413 0 .78-.146 1.057-.405s.78-.73.896-.931c.122-.204.122-.372.081-.518-.028-.172-.146-.316-.263-.437z"/>
                    </svg> 
                    0812-9080-8046
                </p>
                <p>
                    Mohon sebutkan <strong>Nomor Pesanan ({{ $penjualan->nomor_penjualan }})</strong> saat menghubungi kami.
                </p>
            </div>
        @endif

        @if($penjualan->status_penjualan == 'SELESAI' && $itemsWithGaransi->isNotEmpty())
            <div class="info-garansi-container">
                <h5>Informasi Garansi:</h5>
                @foreach($itemsWithGaransi as $detail)
                    @php
                        // Logika tipe garansi Anda sudah benar, kita pertahankan
                        $tipeGaransiDariBatch = 'Tidak Ada';
                        if ($detail->stokAlokasi->isNotEmpty() && $detail->stokAlokasi->first()->stokBarang) {
                            $firstBatchType = $detail->stokAlokasi->first()->stokBarang->tipe_garansi ?? 'NONE';
                            if ($firstBatchType === 'RESMI') $tipeGaransiDariBatch = 'Resmi';
                            elseif ($firstBatchType === 'SELF_SERVICE') $tipeGaransiDariBatch = 'Toko';
                        }
                    @endphp
                    @if($tipeGaransiDariBatch !== 'Tidak Ada')
                        <div class="garansi-item">
                            - <strong>{{ $detail->nama_produk_snapshot }}</strong>:
                            Garansi {{ $tipeGaransiDariBatch }}
                            s/d {{ \Carbon\Carbon::parse($detail->customer_garansi_berakhir_at)->isoFormat('D MMMM YYYY') }}
                        </div>
                    @endif
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