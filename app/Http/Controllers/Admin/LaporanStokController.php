<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\StokBarang;
use App\Models\DetailPenjualanStokAlokasi;
use App\Models\DetailReturPenjualan;
use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\ReturPenjualan;
use App\Models\ReturPembelian;
use App\Models\RiwayatPergerakanStok;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class LaporanStokController extends Controller
{
    public function ringkasanProduk(Request $request)
    {
        if ($request->ajax()) {
        // Ganti alias agar lebih jelas, misal: 'stok_siap_jual'
        $aliasStokSiapJual = 'stok_siap_jual_hasil_hitung';

        $produksQueryBuilder = Produk::where('status', true)
            ->with('merk')
            ->withCount([
                // DIUBAH: Tambahkan filter kondisi 'BAIK'
                "stokBarang as {$aliasStokSiapJual}" => function ($query) {
                    $query->select(DB::raw('COALESCE(SUM(jumlah), 0)'))
                          ->where('kondisi', 'BAIK') 
                          ->where('jumlah', '>', 0);
                },
            ]);

            return DataTables::of($produksQueryBuilder)
                ->addIndexColumn()
                ->addColumn('gambar_display', function ($produk) {
                    if ($produk->gambar && Storage::exists('public/produk/' . $produk->gambar)) {
                        $url = Storage::url('produk/' . $produk->gambar);
                        return '<img src="' . $url . '" alt="' . e($produk->nama) . '" style="max-height: 40px; max-width: 40px; object-fit: contain; cursor:pointer;" onclick="showImageModal(\''.$url.'\', \''.e($produk->nama).'\')">';
                    }
                    return '<span class="text-muted small">(N/A)</span>';
                })
                ->addColumn('kode_produk_display', function($produk){
                    return $produk->kode_produk ?? '-';
                })
                ->addColumn('nama_produk_display', function ($produk) {
                    return $produk->nama;
                })
                ->addColumn('merk_display', function ($produk) {
                    return $produk->merk->nama ?? '-';
                })
                ->addColumn('stok_minimum_display', function ($produk) {
                    return ($produk->stok_minimum ?: 0) . ' ' . $produk->satuan;
                })
                ->addColumn('stok_efektif_dan_status_display', function ($produk) use ($aliasStokSiapJual) {
                    // DIUBAH: Menggunakan alias baru dan logikanya sekarang sudah menghitung stok baik saja
                    $stokSiapJual = $produk->{$aliasStokSiapJual} ?: 0;
                    
                    // Logika untuk menghitung yang sudah dipesan tetap sama
                    $totalDipesan = 0; // Sesuaikan jika Anda punya logika pesanan yang kompleks
                    
                    $stokEfektif = $stokSiapJual - $totalDipesan;
                    $stokMinimum = $produk->stok_minimum ?: 0;
                    
                    $html = ($stokEfektif ?: 0) . ' ' . $produk->satuan;
                    $badgeClass = 'bg-success';
                    $statusText = 'Cukup';

                    if ($stokEfektif <= 0) {
                        $badgeClass = 'bg-danger';
                        $statusText = 'Habis';
                    } elseif ($stokMinimum > 0 && $stokEfektif <= $stokMinimum) {
                        $badgeClass = 'bg-danger';
                        $statusText = 'Kritis';
                    }
                    $html .= ' <span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
                    return $html;
                })
                ->addColumn('action', function ($produk) {
                    if (!$produk || !isset($produk->id)) {
                        // Log::error('Objek produk tidak valid atau tidak memiliki ID di addColumn action untuk ringkasan.', ['produk_obj' => $produk ? $produk->toArray() : null]);
                        return 'Error';
                    }
                    $urlDetailBatch = route('admin.laporan.stok.detail_batch_produk', ['produk' => $produk->id]);
                    $urlKartuStok = route('admin.laporan.stok.kartu_stok.data', ['produk' => $produk->id]);
                    $btnDetailBatch = '<a href="' . $urlDetailBatch . '" class="btn btn-info btn-xs me-1" title="Detail Batch"><i class="bi bi-list-task"></i></a>';
                    $btnKartuStok = '<a href="' . $urlKartuStok . '" class="btn btn-primary btn-xs" title="Kartu Stok"><i class="bi bi-file-earmark-text"></i></a>';
                    return '<div class="btn-group">' . $btnDetailBatch . $btnKartuStok . '</div>';
                })
                ->rawColumns(['gambar_display', 'stok_efektif_dan_status_display', 'action'])
                ->make(true);
        }
        return view('admin.laporan.stok.ringkasan_produk');
    }


    public function detailBatchProduk(Request $request, Produk $produk)
    {
        // Query dasar untuk mendapatkan semua batch aktif
        $baseQuery = StokBarang::where('id_produk', $produk->id)
                            ->where('jumlah', '>', 0);

        // Jika ini adalah request AJAX dari DataTables
        if ($request->ajax()) {
            $queryForDataTable = (clone $baseQuery)->with([
                'supplier',
                'riwayatPergerakanMasuk' => function($q) {
                    $q->where('tipe_transaksi', 'PROSES_RETUR_PELANGGAN')
                    ->with('referensi.returPenjualan.penjualanAsal.pelanggan');
                }
            ])->orderBy('diterima_at', 'asc');

            return DataTables::of($queryForDataTable)
                ->addIndexColumn()
                ->addColumn('id_batch', fn($batch) => $batch->id)
                ->addColumn('diterima_at_formatted', fn($batch) => Carbon::parse($batch->diterima_at)->isoFormat('D MMM YY, HH:mm'))
                
                // ### KOLOM SUMBER & HARGA BELI (GABUNGAN) ###
                ->addColumn('sumber_dan_harga_display', function($batch) {
                    $hargaBeli = 'Rp ' . number_format($batch->harga_beli, 0, ',', '.');
                    $sumberText = '';

                    if ($batch->tipe_stok === 'RETUR_DARI_PELANGGAN' && $batch->riwayatPergerakanMasuk) {
                        $riwayat = $batch->riwayatPergerakanMasuk;
                        if ($riwayat->referensi instanceof DetailReturPenjualan) {
                            $namaPelanggan = $riwayat->referensi->returPenjualan->penjualanAsal->pelanggan->nama ?? 'Umum';
                            $sumberText = '<strong>Retur dari:</strong><br><span class="small">' . e($namaPelanggan) . '</span>';
                        }
                    } elseif ($batch->supplier) {
                        $sumberText = e($batch->supplier->nama);
                    } else {
                        $sumberText = 'Penerimaan Manual';
                    }
                    
                    return $sumberText . '<br><small class="text-muted">' . $hargaBeli . '</small>';
                })
                
                ->editColumn('kondisi', function($batch) { 
                    $kondisiText = ucwords(str_replace('_', ' ', $batch->kondisi));
                    $badgeClass = 'bg-secondary';
                    if ($batch->kondisi === 'BAIK') $badgeClass = 'bg-success';
                    if ($batch->kondisi === 'AKAN_DIRETUR_SUPPLIER') $badgeClass = 'bg-warning text-dark';
                    if (str_contains($batch->kondisi, 'RUSAK')) $badgeClass = 'bg-danger';
                    
                    return '<span class="badge ' . $badgeClass . '">' . e($kondisiText) . '</span>';
                })
                ->editColumn('lokasi', fn($batch) => $batch->lokasi)
                ->addColumn('total_jumlah_batch', fn($batch) => $batch->jumlah . ' ' . e($produk->satuan))
                ->addColumn('stok_siap_jual', function($batch) use ($produk){
                    if ($batch->kondisi === 'BAIK') {
                        return '<span class="fw-bold text-success">' . $batch->jumlah . ' ' . e($produk->satuan) . '</span>';
                    }
                    return '<span class="text-muted">0 ' . e($produk->satuan) . '</span>';
                })
                ->addColumn('nomor_seri_tersedia', function($batch) use ($produk) {
                    if (!$produk->memiliki_serial) return '-';

                    // ### LOGIKA BARU UNTUK MENGAMBIL NOMOR SERI ###
                    
                    // 1. Ambil semua kandidat serial yang PERNAH TERCATAT di batch ini,
                    //    baik saat masuk, keluar, atau pindah.
                    $candidateSerials = RiwayatPergerakanStok::where('id_stok_barang_terkait', $batch->id)
                        ->whereNotNull('nomor_seri')
                        ->distinct()
                        ->pluck('nomor_seri');

                    if ($candidateSerials->isEmpty()) {
                        return '-'; // Tidak ada histori serial sama sekali untuk batch ini
                    }

                    // 2. Dari semua kandidat, cari ID pergerakan TERAKHIR untuk setiap serial.
                    $latestMovementIds = RiwayatPergerakanStok::select(DB::raw('MAX(id) as id'))
                        ->whereIn('nomor_seri', $candidateSerials)
                        ->groupBy('nomor_seri')
                        ->pluck('id');

                    // 3. Ambil semua serial dari pergerakan terakhir yang:
                    //    a. Statusnya adalah MASUK (jumlah_masuk > 0) ATAU merupakan perpindahan (tipe_transaksi = 'PERPINDAHAN_STOK')
                    //    b. Dan benar-benar milik BATCH INI.
                    $availableSerials = RiwayatPergerakanStok::whereIn('id', $latestMovementIds)
                        ->where(function ($query) use ($batch) {
                            $query->where('jumlah_masuk', '>', 0)
                                ->orWhere('tipe_transaksi', 'PERPINDAHAN_STOK');
                        })
                        ->where('id_stok_barang_terkait', $batch->id)
                        ->pluck('nomor_seri');

                    return $availableSerials->isNotEmpty() ? $availableSerials->implode(', ') : '<span class="text-danger small">Habis / Pindah</span>';
                })
                ->rawColumns(['stok_siap_jual', 'nomor_seri_tersedia', 'sumber_dan_harga_display', 'kondisi'])
                ->make(true);
        }

        // Jika ini adalah request awal untuk memuat halaman
        // Hitung total stok siap jual
        $totalStokSiapJual = (clone $baseQuery)->where('kondisi', 'BAIK')->sum('jumlah');
        
        // Kirim data ke view
        return view('admin.laporan.stok.detail_batch_produk', compact('produk', 'totalStokSiapJual'));
    }


    public function generateKartuStok(Produk $produk, Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai')
                        ? Carbon::parse($request->input('tanggal_mulai'))->startOfDay()
                        : now()->startOfMonth()->startOfDay();

        $tanggalSelesai = $request->input('tanggal_selesai')
                        ? Carbon::parse($request->input('tanggal_selesai'))->endOfDay()
                        : now()->endOfDay();
        
        // 1. Hitung Saldo Awal
        $saldoAwal = RiwayatPergerakanStok::where('id_produk', $produk->id)
            ->where('tanggal_transaksi', '<', $tanggalMulai)
            ->orderBy('id', 'desc')->value('saldo_setelah_transaksi') ?? 0;

        // 2. Ambil semua data riwayat dengan relasi yang dibutuhkan
    $pergerakanStok = RiwayatPergerakanStok::where('id_produk', $produk->id)
        ->whereBetween('tanggal_transaksi', [$tanggalMulai, $tanggalSelesai])
        ->with([
            'pengguna',
            'stokBarangTerkait.supplier', // Penting untuk semua jenis penerimaan
            'referensi' => function ($morphTo) {
                // Muat relasi turunan untuk setiap tipe referensi
                $morphTo->morphWith([
                    \App\Models\Pembelian::class => ['supplier'],
                    \App\Models\Penjualan::class => ['pelanggan', 'detailPenjualan'],
                    \App\Models\ReturPembelian::class => ['stokBarang.supplier'],
                    \App\Models\DetailReturPenjualan::class => ['returPenjualan.penjualanAsal.pelanggan'],
                    \App\Models\ReturPenjualan::class => ['penjualanAsal.pelanggan'],
                ]);
            }
        ])
        ->orderBy('id', 'asc')
        ->get();
        
        // Inisialisasi data untuk view
        $dataUntukView = [];
        $dataUntukView[] = [
            'tanggal_display' => $tanggalMulai->isoFormat('D MMM YY'),
            'jenis_transaksi_display' => 'SALDO AWAL',
            'nomor_referensi_display' => '-',
            'referensi_link' => null,
            'masuk_display' => '-',
            'keluar_display' => '-',
            'saldo_display' => $saldoAwal . ' ' . $produk->satuan,
            'keterangan_tambahan_display' => "Saldo sebelum periode " . $tanggalMulai->isoFormat('D MMMM YYYY')
        ];
        
        // Menggunakan each untuk memproses setiap baris riwayat
        $pergerakanStok->each(function($item) use (&$dataUntukView, $produk) {
            
            // Data default
            $nomorReferensi = '-';
            $referensiLink = null;
            $keteranganDisplay = $item->keterangan ?? '';
            // Format default, akan ditimpa oleh logika spesifik
            $jenisTransaksiDisplay = ucwords(str_replace('_', ' ', strtolower($item->tipe_transaksi)));

            // ### LOGIKA UTAMA: Cek berdasarkan tipe transaksi ###
            switch ($item->tipe_transaksi) {
                
                case 'PENERIMAAN_PO':
                case 'PENERIMAAN_PENGGANTI_RETUR':
                    $ref = $item->referensi;
                    if ($ref instanceof \App\Models\Pembelian) {
                        $nomorReferensi = $ref->nomor_pembelian;
                        $referensiLink = route('admin.pembelian.show', $ref->id);
                    }
                    $jenisTransaksiDisplay = ($item->tipe_transaksi === 'PENERIMAAN_PENGGANTI_RETUR') ? 'Penerimaan Barang Pengganti' : 'Penerimaan dari Supplier';
                    $keteranganDisplay = "Dari: " . ($item->stokBarangTerkait->supplier->nama ?? 'N/A');
                    if ($item->stokBarangTerkait) {
                        $keteranganDisplay .= ". Harga Beli: Rp " . number_format($item->stokBarangTerkait->harga_beli, 0, ',', '.');
                    }
                    break;

                case 'PENERIMAAN_MANUAL':
                    $jenisTransaksiDisplay = 'Penerimaan Manual';
                    if ($item->stokBarangTerkait) {
                        $namaSupplier = $item->stokBarangTerkait->supplier->nama ?? 'N/A';
                        $tipeStok = ucwords(strtolower(str_replace('_', ' ', $item->stokBarangTerkait->tipe_stok ?? '')));
                        $keteranganDisplay = "Dari: {$namaSupplier} (Tipe: {$tipeStok})";
                    }
                    break;

                case 'PENJUALAN':
                case 'PENJUALAN_PESANAN_BARANG':
                    $ref = $item->referensi;
                    if ($ref instanceof \App\Models\Penjualan) {
                        $jenisTransaksiDisplay = ($item->tipe_transaksi === 'PENJUALAN_PESANAN_BARANG') ? 'Penjualan (Pesan Barang)' : 'Penjualan';
                        $nomorReferensi = $ref->nomor_penjualan;
                        $referensiLink = route('kasir.penjualan.nota', $ref->id);
                        $detailTerkait = $ref->detailPenjualan()->where('nomor_seri_terjual', 'like', '%' . $item->nomor_seri . '%')->first();
                        $hargaJual = $detailTerkait->harga_jual ?? 0;
                        $keteranganDisplay = "Terjual ke: " . ($ref->pelanggan->nama ?? 'Umum') . ". Harga Jual: Rp " . number_format($hargaJual, 0, ',', '.');
                    }
                    break;

                case 'PROSES_RETUR_PELANGGAN':
                    $ref = $item->referensi;
                    if ($ref instanceof \App\Models\DetailReturPenjualan) {
                        $returHeader = $ref->returPenjualan;
                        $nomorReferensi = $returHeader->nomor_retur;
                        $referensiLink = route('admin.proses_retur_pelanggan.show', $returHeader->id);
                        $keteranganDisplay = "Retur diterima dari: " . ($returHeader->penjualanAsal->pelanggan->nama ?? 'Umum');
                    }
                    break;
                
                case 'RETUR_KE_SUPPLIER':
                    $ref = $item->referensi;
                    if ($ref instanceof \App\Models\ReturPembelian) {
                        $jenisTransaksiDisplay = 'Proses Retur ke Supplier';
                        $nomorReferensi = $ref->nomor_retur;
                        $referensiLink = route('admin.retur_pembelian.show', $ref->id);
                        $keteranganDisplay = "Pengembalian barang ke Supplier: " . ($ref->stokBarang->supplier->nama ?? 'N/A');
                    }
                    break;

                case 'PENYERAHAN_BARANG_RETUR':
                    $ref = $item->referensi;
                    if ($ref instanceof \App\Models\ReturPenjualan) {
                        $jenisTransaksiDisplay = 'Penyerahan Barang Pengganti';
                        $nomorReferensi = $ref->nomor_retur;
                        $referensiLink = route('kasir.retur_penjualan.show', $ref->id);
                        $keteranganDisplay = "Diserahkan ke: " . ($ref->penjualanAsal->pelanggan->nama ?? 'Umum');
                    }
                    break;
            }

            // Tambahkan Nomor Seri ke keterangan jika ada
            if (!empty($item->nomor_seri)) {
                $keteranganDisplay .= " (SN: {$item->nomor_seri})";
            }
            
            $dataUntukView[] = [
                'tanggal_display' => $item->tanggal_transaksi->isoFormat('D MMM YYYY, HH:mm'),
                'jenis_transaksi_display' => $jenisTransaksiDisplay,
                'nomor_referensi_display' => $nomorReferensi,
                'referensi_link' => $referensiLink,
                'masuk_display' => $item->jumlah_masuk > 0 ? ($item->jumlah_masuk . ' ' . $produk->satuan) : '-',
                'keluar_display' => $item->jumlah_keluar > 0 ? ($item->jumlah_keluar . ' ' . $produk->satuan) : '-',
                'saldo_display' => $item->saldo_setelah_transaksi . ' ' . $produk->satuan,
                'keterangan_tambahan_display' => trim($keteranganDisplay),
            ];
        });
            
        return view('admin.laporan.stok.kartu_stok_produk', [
            'produk' => $produk,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'dataUntukView' => $dataUntukView
        ]);
    }

    /**
     * FUNGSI BARU: Menampilkan form untuk lacak nomor seri.
     */
    public function showLacakNomorSeriForm()
    {
        return view('admin.laporan.stok.lacak_nomor_seri');
    }

    /**
     * FUNGSI BARU (REVISI FINAL): Memproses pencarian dan menampilkan hasil riwayat nomor seri.
     */
    public function getLacakNomorSeriResult(Request $request)
    {
        $request->validate(['nomor_seri' => 'required|string|max:255']);
        $nomorSeriDicari = trim($request->input('nomor_seri'));

        // Eager loading ini sudah sangat baik, kita pertahankan.
        $riwayat = RiwayatPergerakanStok::with([
            'pengguna', 'stokBarangTerkait.supplier',
            'referensi' => function ($morphTo) {
                $morphTo->morphWith([
                    Pembelian::class => ['supplier'],
                    Penjualan::class => ['pelanggan', 'detailPenjualan'],
                    ReturPembelian::class => ['supplier'], 
                    DetailReturPenjualan::class => ['returPenjualan.penjualanAsal.pelanggan'],
                ]);
            }
        ])
        ->where('nomor_seri', $nomorSeriDicari)
        ->orderBy('id', 'asc')
        ->get();
            
        $riwayat->each(function ($log) {
            $log->referensi_link = null;
            $log->referensi_text = '-';
            $log->jenis_transaksi_display = ucwords(str_replace('_', ' ', strtolower($log->tipe_transaksi)));
            $log->keterangan_display = $log->keterangan ?? 'N/A';
            $ref = $log->referensi;

            // ### LOGIKA BARU YG LEBIH KONSISTEN ###
            switch ($log->tipe_transaksi) {
                
                case 'PENERIMAAN_PO':
                    $log->jenis_transaksi_display = 'Penerimaan dari Supplier';
                    if ($ref instanceof \App\Models\Pembelian) {
                        $log->referensi_link = route('admin.pembelian.show', $ref->id);
                        $log->referensi_text = $ref->nomor_pembelian;
                    }
                    $log->keterangan_display = "Dari: " . ($log->stokBarangTerkait->supplier->nama ?? 'N/A') . ". Harga Beli: Rp " . number_format($log->stokBarangTerkait->harga_beli ?? 0, 0, ',', '.');
                    break;

                case 'PENJUALAN':
                    if ($ref instanceof \App\Models\Penjualan) {
                        $log->referensi_link = route('kasir.penjualan.nota', $ref->id);
                        $log->referensi_text = $ref->nomor_penjualan;
                        $detailTerkait = $ref->detailPenjualan()->where('nomor_seri_terjual', 'like', '%' . $log->nomor_seri . '%')->first();
                        $hargaJual = $detailTerkait->harga_jual ?? 0;
                        $log->keterangan_display = "Terjual ke: " . ($ref->pelanggan->nama ?? 'Umum') . ". Harga Jual: Rp " . number_format($hargaJual, 0, ',', '.');
                    }
                    break;
                
                case 'PROSES_RETUR_PELANGGAN':
                    if ($ref instanceof \App\Models\DetailReturPenjualan) {
                        $returHeader = $ref->returPenjualan;
                        $log->referensi_link = route('admin.proses_retur_pelanggan.show', $returHeader->id);
                        $log->referensi_text = $returHeader->nomor_retur;
                        $log->keterangan_display = "Retur diterima dari: " . ($returHeader->penjualanAsal->pelanggan->nama ?? 'Umum');
                    }
                    break;
                
                case 'RETUR_KE_SUPPLIER':
                    $log->jenis_transaksi_display = 'Retur ke Supplier';
                    if ($ref instanceof \App\Models\ReturPembelian) {
                        $log->referensi_link = route('admin.retur_pembelian.show', $ref->id);
                        $log->referensi_text = $ref->nomor_retur;
                        $log->keterangan_display = "Dikirim ke: " . ($ref->supplier->nama ?? 'N/A');
                    }
                    break;
                    
                case 'PENERIMAAN_PENGGANTI_RETUR':
                    $log->jenis_transaksi_display = 'Penerimaan Barang Pengganti';
                    if ($ref instanceof \App\Models\ReturPembelian) {
                        $log->referensi_link = route('admin.retur_pembelian.show', $ref->id);
                        $log->referensi_text = $ref->nomor_retur;
                        $log->keterangan_display = "Diterima dari: " . ($ref->supplier->nama ?? 'N/A');
                    }
                    break;

                case 'PENYERAHAN_BARANG_RETUR':
                    $log->jenis_transaksi_display = 'Penyerahan Barang Pengganti';
                    if ($ref instanceof \App\Models\DetailReturPenjualan) {
                        $returHeader = $ref->returPenjualan;
                        $log->referensi_link = route('kasir.retur_penjualan.show', $returHeader->id);
                        $log->referensi_text = $returHeader->nomor_retur;
                        $log->keterangan_display = "Diserahkan ke: " . ($returHeader->penjualanAsal->pelanggan->nama ?? 'Umum');
                    }
                    break;
            }

            // Tambahkan SN di akhir keterangan untuk konsistensi
            if (!empty($log->nomor_seri)) {
                // Hapus dulu SN lama jika ada untuk mencegah duplikasi
                $log->keterangan_display = preg_replace("/\s*\(SN:.*? \)/i", '', $log->keterangan_display);
                $log->keterangan_display .= " (SN: {$log->nomor_seri})";
            }
        });


        // Tentukan status terkini
        $statusTerkini = ['status' => 'Tidak Pernah Tercatat', 'lokasi' => '-'];
        $logTerakhir = $riwayat->last();

        if ($logTerakhir) {
            if ($logTerakhir->jumlah_masuk > 0) {
                $statusTerkini['status'] = 'TERSEDIA';
                $lokasiBatch = StokBarang::find($logTerakhir->id_stok_barang_terkait);
                if ($lokasiBatch) {
                    $statusTerkini['lokasi'] = "Batch ID: {$lokasiBatch->id} ({$lokasiBatch->kondisi}, di {$lokasiBatch->lokasi})";
                } else {
                    $statusTerkini['lokasi'] = 'Batch fisik sudah tidak ada/dihapus';
                }
            } else { // Jika jumlah_keluar > 0
                $statusTerkini['status'] = 'TIDAK TERSEDIA (' . ucwords(str_replace('_', ' ', strtolower($logTerakhir->tipe_transaksi))) . ')';
                $statusTerkini['lokasi'] = 'Sudah keluar dari sistem';
            }
        }

        return view('admin.laporan.stok.lacak_nomor_seri', compact('riwayat', 'nomorSeriDicari', 'statusTerkini'));
    }
}