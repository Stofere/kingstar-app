{{-- Ganti SELURUH isi file resources/views/admin/proses_retur_pelanggan/nota_retur.blade.php --}}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Retur - {{ $returPenjualan->nomor_retur }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11pt; margin: 0; padding: 0; background-color: #e9e9e9; color: #333; }
        .nota-container { width: 90%; max-width: 800px; margin: 20px auto; border: 1px solid #ccc; padding: 25px; background-color: #fff; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .header-nota { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #333; }
        .header-nota h3 { margin: 0 0 5px 0; font-size: 1.6em; font-weight: bold; }
        .header-nota p { margin: 4px 0; font-size: 0.9em; color: #555; }
        .header-nota h4 { margin: 10px 0 0 0; font-size: 1.3em; font-weight: bold; color: #dc3545; }
        .info-transaksi { margin-bottom: 25px; font-size: 0.95em; line-height: 1.6; }
        .info-transaksi table { width: 100%; border-collapse: collapse; }
        .info-transaksi td { padding: 4px 0; }
        .info-transaksi td:first-child { width: 150px; font-weight: bold; }
        .info-transaksi td:nth-child(2) { width: 15px; text-align: center; }
        .table-items-nota { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 0.95em; }
        .table-items-nota th, .table-items-nota td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        .table-items-nota th { background-color: #f7f7f7; font-weight: bold; }
        .footer-tanda-tangan { margin-top: 40px; width: 100%; display: table; }
        .kolom-ttd { display: table-cell; width: 50%; text-align: center; }
        .kolom-ttd p { margin-bottom: 0; }
        .kolom-ttd .nama-terang { margin-top: 60px; border-top: 1px solid #555; display: inline-block; padding: 5px 20px 0 20px; }
        .actions-nota { text-align: center; margin: 25px auto; padding: 15px; width: 90%; max-width: 800px; }
        .alasan-retur-text { text-transform: capitalize; }
        @media print {
            body { background-color: #fff; font-size: 10pt; }
            .nota-container { width: 100%; max-width: none; border: none; margin: 0; padding: 10mm; box-shadow: none; }
            .actions-nota { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="nota-container">
        <div class="header-nota">
            <h3>{{ $namaToko ?? 'KINGSTAR ELEKTRONIK' }}</h3>
            {{-- Info Toko yang Jelas --}}
            <p>{{ $alamatToko ?? 'Pasar Genteng Baru Lt. 2 Blok N no. 20, Surabaya' }}</p>
            <p>Telp: {{ $teleponToko ?? '081290808046' }}</p>
            <h4>BUKTI RETUR BARANG</h4>
        </div>

        <div class="info-transaksi">
            <div class="row">
                <div class="col-6">
                    <table>
                        <tr>
                            <td>No. Retur</td><td>:</td><td><strong>{{ $returPenjualan->nomor_retur }}</strong></td>
                        </tr>
                        <tr>
                            <td>Tanggal Retur</td><td>:</td><td>{{ \Carbon\Carbon::parse($returPenjualan->tanggal_retur)->isoFormat('D MMMM YYYY, HH:mm') }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-6">
                     <table>
                        <tr>
                            <td>Pelanggan</td><td>:</td><td>{{ $returPenjualan->penjualanAsal->pelanggan->nama ?? 'Umum' }}</td>
                        </tr>
                        <tr>
                            <td>No. Nota Asal</td><td>:</td><td>{{ $returPenjualan->penjualanAsal->nomor_penjualan }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <p>Telah diterima kembali barang-barang berikut dari transaksi penjualan di atas:</p>
        <table class="table-items-nota">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">No</th>
                    <th style="width: 45%;">Produk Diretur</th>
                    <th class="text-center" style="width: 10%;">Jumlah</th>
                    <th style="width: 40%;">Alasan Retur</th>
                </tr>
            </thead>
            <tbody>
                @foreach($returPenjualan->detailReturPenjualan as $index => $detail)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            {{ $detail->detailPenjualanAsal->produk->nama ?? 'N/A' }}
                            @if($detail->nomor_seri_diretur)
                                <br><small class="text-muted">SN: {{ $detail->nomor_seri_diretur }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $detail->jumlah_retur }}</td>
                        <td class="alasan-retur-text">
                            {{-- Menghilangkan underscore dan membuat format kalimat --}}
                            {{ str_replace('_', ' ', strtolower($detail->alasan_retur)) }}
                            @if($detail->catatan_pelanggan)
                                <br><small class="fst-italic">"{{ $detail->catatan_pelanggan }}"</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer-tanda-tangan">
            <div class="kolom-ttd">
                <p>Diserahkan oleh,</p>
                <p class="nama-terang">( {{ $returPenjualan->penjualanAsal->pelanggan->nama ?? 'Pelanggan' }} )</p>
            </div>
            <div class="kolom-ttd">
                <p>Diterima oleh,</p>
                <p class="nama-terang">( {{ $returPenjualan->pengguna->nama ?? 'Kasir' }} )</p>
            </div>
        </div>

        <div class="info-lanjutan-container" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; text-align: center; font-size: 0.9em;">
            <p class="fw-bold">Informasi Tindak Lanjut:</p>
            <p>
                Untuk mengetahui status perbaikan (servis) atau ketersediaan barang pengganti,
                silakan hubungi Admin kami melalui WhatsApp di nomor berikut:
            </p>
            <p style="font-size: 1.2em; font-weight: bold; color: #198754;">
                <i class="bi bi-whatsapp"></i> 0812-9080-8046
            </p>
            <p>
                Mohon sebutkan <strong>Nomor Retur ({{ $returPenjualan->nomor_retur }})</strong> saat menghubungi kami.
            </p>
        </div>

    </div>

    <div class="actions-nota">
        <button type="button" class="btn btn-primary btn-lg" onclick="window.print();">Cetak</button>
        <button type="button" class="btn btn-secondary btn-lg ms-2" onclick="window.close();">Tutup</button>
    </div>
</body>
</html>