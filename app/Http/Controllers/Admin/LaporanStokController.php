<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\StokBarang;
use App\Models\DetailPenjualanStokAlokasi;
use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\ReturPenjualan;
use App\Models\ReturPembelian;
use App\Models\PenyesuaianStok;
use App\Models\RiwayatPergerakanStok;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\LogNomorSeri;
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
        // Query utama tidak perlu diubah
        $query = StokBarang::where('id_produk', $produk->id)
                            ->where('jumlah', '>', 0)
                            ->with(['supplier']) 
                            ->orderBy('diterima_at', 'asc');

        if ($request->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('id_batch', fn($batch) => $batch->id)
                ->addColumn('diterima_at_formatted', fn($batch) => Carbon::parse($batch->diterima_at)->isoFormat('D MMM YY, HH:mm'))
                
                // #FIX: Logika Sumber/Supplier dibuat lebih cerdas
                ->addColumn('supplier_nama', function($batch) {
                    if ($batch->supplier) {
                        return $batch->supplier->nama;
                    }
                    if (in_array($batch->kondisi, ['RUSAK', 'GUDANG_RETUR'])) {
                        return 'Retur dari Pelanggan';
                    }
                    return 'Penerimaan Manual';
                })
                
                ->addColumn('total_jumlah_batch', fn($batch) => $batch->jumlah . ' ' . $produk->satuan)
                ->addColumn('sudah_dipesan', fn($batch) => '0 ' . $produk->satuan) // Logika ini bisa disempurnakan nanti
                ->addColumn('stok_siap_jual', function($batch) use ($produk){
                    if ($batch->kondisi === 'BAIK') {
                        return '<span class="fw-bold text-success">' . $batch->jumlah . ' ' . $produk->satuan . '</span>';
                    }
                    return '<span class="text-muted">0 ' . $produk->satuan . '</span>';
                })
                ->editColumn('lokasi', fn($batch) => $batch->lokasi)
                ->editColumn('kondisi', fn($batch) => ucwords(str_replace('_', ' ', $batch->kondisi)))
                
                ->addColumn('nomor_seri_tersedia', function($batch) use ($produk) {
                if (!$produk->memiliki_serial) return '-';

                // =========================================================================
                // ## LOGIKA BARU YANG LEBIH EFISIEN DAN AKURAT ##
                // =========================================================================

                // Langkah 1: Dapatkan semua nomor seri yang PERNAH tercatat masuk ke batch ini.
                // Ini menjadi kandidat kita.
                $candidateSerials = RiwayatPergerakanStok::where('id_stok_barang_terkait', $batch->id)
                    ->where('jumlah_masuk', '>', 0)
                    ->whereNotNull('nomor_seri')
                    ->distinct()
                    ->pluck('nomor_seri');

                if ($candidateSerials->isEmpty()) {
                    return '-';
                }

                // Langkah 2: Cari ID pergerakan TERAKHIR untuk setiap nomor seri kandidat.
                // Ini adalah subquery yang sangat efisien untuk menghindari N+1 problem.
                $latestMovementIds = RiwayatPergerakanStok::select(DB::raw('MAX(id) as id'))
                    ->whereIn('nomor_seri', $candidateSerials)
                    ->groupBy('nomor_seri');

                // Langkah 3: Ambil semua serial dari pergerakan terakhir yang statusnya adalah "MASUK"
                // dan lokasinya ada di BATCH INI.
                $availableSerials = RiwayatPergerakanStok::whereIn('id', $latestMovementIds)
                    ->where('id_stok_barang_terkait', $batch->id)
                    ->where('jumlah_masuk', '>', 0) // Memastikan status terakhir adalah 'masuk', bukan 'keluar'
                    ->pluck('nomor_seri');

                return $availableSerials->isNotEmpty() ? $availableSerials->implode(', ') : '-';
            })
                ->rawColumns(['stok_siap_jual', 'nomor_seri_tersedia'])
                ->make(true);
        }
        return view('admin.laporan.stok.detail_batch_produk', compact('produk'));
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

        // 2. Ambil semua data riwayat dalam periode dengan relasi yang dibutuhkan
        $pergerakanStok = RiwayatPergerakanStok::where('id_produk', $produk->id)
            ->whereBetween('tanggal_transaksi', [$tanggalMulai, $tanggalSelesai])
            ->with([
                'pengguna', 
                'stokBarangTerkait.supplier',
                'referensi' // Polymorphic relation
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
        
        // Logika Agregasi (Menggabungkan baris dari transaksi yang sama)
        $pergerakanStok->groupBy(function($item) {
            // Kelompokkan berdasarkan tipe transaksi DAN nomor referensi.
            return $item->tipe_transaksi . '_' . ($item->id_referensi ?? 'unique_' . $item->id);
        })->each(function($group) use (&$dataUntukView, $produk) {
            
            $firstItem = $group->first();
            
            // Data yang diagregasi
            $jumlahMasuk = $group->sum('jumlah_masuk');
            $jumlahKeluar = $group->sum('jumlah_keluar');
            $saldoAkhirGrup = $group->last()->saldo_setelah_transaksi;
            $nomorSeriGrup = $group->pluck('nomor_seri')->filter()->implode(', ');

            // Inisialisasi data default
            $nomorReferensi = '-';
            $referensiLink = null;
            $keteranganDisplay = $firstItem->keterangan ?? '';
            $jenisTransaksiDisplay = ucwords(str_replace('_', ' ', $firstItem->tipe_transaksi));

            // Tentukan No. Referensi & Keterangan berdasarkan Tipe Referensi
            if ($firstItem->referensi) {
                $referensiModel = $firstItem->referensi;
                
                if ($referensiModel instanceof Pembelian) {
                    $nomorReferensi = $referensiModel->nomor_pembelian;
                    $referensiLink = route('admin.pembelian.show', $referensiModel->id);
                    // Keterangan sudah bagus dari PenerimaanController
                } elseif ($referensiModel instanceof Penjualan) {
                    $nomorReferensi = $referensiModel->nomor_penjualan;
                    $referensiLink = route('kasir.penjualan.nota', $referensiModel->id);
                    // Muat relasi pelanggan untuk mendapatkan nama
                    $referensiModel->loadMissing('pelanggan');
                    $keteranganDisplay = "Terjual ke: " . ($referensiModel->pelanggan->nama ?? 'Umum');
                } if ($referensiModel instanceof ReturPembelian) {
                    $nomorReferensi = $referensiModel->nomor_retur;
                    $referensiLink = route('admin.retur_pembelian.show', $referensiModel->id);
                    // Kita ambil nama supplier dari batch yang diretur
                    $referensiModel->loadMissing('stokBarang.supplier');
                    $keteranganDisplay = "Diretur ke Supplier (" . ($referensiModel->stokBarang->supplier->nama ?? 'N/A') . ")";
                } elseif ($referensiModel instanceof ReturPenjualan) {
                    $nomorReferensi = $referensiModel->nomor_retur;
                    $referensiLink = route('kasir.retur_penjualan.show', $referensiModel->id);
                    // Keterangan sudah diisi dari ProsesReturPelangganController
                }
            }

            // Tentukan ulang Teks Jenis Transaksi & Keterangan untuk kasus spesifik
            switch ($firstItem->tipe_transaksi) {
                case 'PENERIMAAN_PO':
                    $jenisTransaksiDisplay = 'Penerimaan dari Supplier';
                    break;
                case 'PENERIMAAN_KONSINYASI':
                    $jenisTransaksiDisplay = 'Penerimaan Titipan (Konsinyasi)';
                    break;
                case 'PENERIMAAN_MANUAL':
                    $jenisTransaksiDisplay = 'Penerimaan Manual (Stok Lama)';
                    break;
            }

            // Tambahkan Nomor Seri ke keterangan jika ada
            if (!empty($nomorSeriGrup)) {
                $keteranganDisplay .= " (SN: {$nomorSeriGrup})";
            }
            
            // Masukkan data yang sudah diolah ke array untuk view
            $dataUntukView[] = [
                'tanggal_display' => $firstItem->tanggal_transaksi->isoFormat('D MMM YYYY, HH:mm'),
                'jenis_transaksi_display' => $jenisTransaksiDisplay,
                'nomor_referensi_display' => $nomorReferensi,
                'referensi_link' => $referensiLink,
                'masuk_display' => $jumlahMasuk > 0 ? ($jumlahMasuk . ' ' . $produk->satuan) : '-',
                'keluar_display' => $jumlahKeluar > 0 ? ($jumlahKeluar . ' ' . $produk->satuan) : '-',
                'saldo_display' => $saldoAkhirGrup . ' ' . $produk->satuan,
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

        // Muat semua relasi yang mungkin dibutuhkan dalam satu kali jalan
        $riwayat = RiwayatPergerakanStok::with(['stokBarangTerkait.supplier', 'pengguna', 'referensi'])
            ->where('nomor_seri', $nomorSeriDicari)
            ->orderBy('tanggal_transaksi', 'asc')
            ->orderBy('id', 'asc')
            ->get();
            
        // Olah setiap log untuk menambahkan informasi yang lebih kaya
        $riwayat->each(function ($log) {
            $log->referensi_link = null;
            $log->referensi_text = '-';
            $log->jenis_transaksi_display = ucwords(str_replace('_', ' ', $log->tipe_transaksi)); // Default display
            $log->keterangan_display = $log->keterangan ?? ''; // Default keterangan

            // --- LOGIKA KETERANGAN BARU ---
            switch ($log->tipe_transaksi) {
                case 'PENERIMAAN_PO':
                case 'PENERIMAAN_PENGGANTI_RETUR':
                    $log->jenis_transaksi_display = 'Penerimaan dari Supplier';
                    if ($log->stokBarangTerkait && $log->stokBarangTerkait->supplier) {
                        $log->keterangan_display = "Supplier: " . $log->stokBarangTerkait->supplier->nama . ". Harga Beli: Rp " . number_format($log->stokBarangTerkait->harga_beli, 0, ',', '.');
                    }
                    break;
                
                case 'PENERIMAAN_KONSINYASI':
                    $log->jenis_transaksi_display = 'Penerimaan Titipan (Konsinyasi)';
                     if ($log->stokBarangTerkait && $log->stokBarangTerkait->supplier) {
                        $log->keterangan_display = "Supplier: " . $log->stokBarangTerkait->supplier->nama;
                    }
                    break;

                case 'PENERIMAAN_MANUAL':
                    $log->jenis_transaksi_display = 'Penerimaan Manual (Stok Lama)';
                    if ($log->stokBarangTerkait && $log->stokBarangTerkait->supplier) {
                        $log->keterangan_display = "Supplier: " . $log->stokBarangTerkait->supplier->nama;
                    } else {
                        $log->keterangan_display = "Penerimaan tanpa supplier tercatat.";
                    }
                    break;

               case 'PENJUALAN':
                $penjualan = $log->referensi;
                if ($penjualan instanceof Penjualan) {
                    // --- PERBAIKAN DI SINI ---
                    // 1. Ambil detail penjualan yang relevan dari objek penjualan utama
                    $detailPenjualanTerkait = $penjualan->detailPenjualan()->where('id_produk', $log->id_produk)->first();
                    $hargaJual = $detailPenjualanTerkait->harga_jual ?? 0;

                    $log->keterangan_display = "Terjual ke: " . ($penjualan->pelanggan->nama ?? 'Umum') . ". Harga Jual: Rp " . number_format($hargaJual, 0, ',', '.');
                }
                break;
                
                case 'RETUR_SUPPLIER':
                     $log->keterangan_display = 'Retur ke Supplier. ' . $log->keterangan;
                     break;
                
                case 'RETUR_PELANGGAN':
                     $retur = $log->referensi;
                     if($retur instanceof ReturPenjualan) {
                        $log->keterangan_display = "Retur dari " . ($retur->detailPenjualan->penjualan->pelanggan->nama ?? 'Umum') . ". Alasan: " . $retur->alasan_retur;
                     }
                     break;

                case 'PENYERAHAN_BARANG_RETUR':
                     $log->jenis_transaksi_display = 'Penyerahan Barang Pengganti';
                     $retur = $log->referensi;
                     if($retur instanceof ReturPenjualan) {
                        $log->keterangan_display = "Diserahkan ke " . ($retur->detailPenjualan->penjualan->pelanggan->nama ?? 'Umum');
                     }
                     break;
            }

            // Logika untuk link No. Dokumen (tetap sama)
            if ($log->referensi) {
                if ($log->referensi instanceof Pembelian) {
                    $log->referensi_link = route('admin.pembelian.show', $log->referensi->id);
                    $log->referensi_text = $log->referensi->nomor_pembelian;
                } elseif ($log->referensi instanceof Penjualan) {
                    $log->referensi_link = route('kasir.penjualan.nota', $log->referensi->id);
                    $log->referensi_text = $log->referensi->nomor_penjualan;
                } elseif ($log->referensi instanceof ReturPenjualan) {
                    $log->referensi_link = route('kasir.retur_penjualan.show', $log->referensi->id);
                    $log->referensi_text = $log->referensi->nomor_retur;
                } elseif ($log->referensi instanceof ReturPembelian) {
                    $log->referensi_link = route('admin.retur_pembelian.show', $log->referensi->id);
                    $log->referensi_text = $log->referensi->nomor_retur;
                }
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
                $statusTerkini['status'] = 'TIDAK TERSEDIA (' . ucwords(str_replace('_', ' ', $logTerakhir->tipe_transaksi)) . ')';
                $statusTerkini['lokasi'] = 'Sudah keluar dari sistem';
            }
        }

        return view('admin.laporan.stok.lacak_nomor_seri', compact('riwayat', 'nomorSeriDicari', 'statusTerkini'));
    }
}