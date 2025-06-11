<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\StokBarang;
use App\Models\DetailPenjualanStokAlokasi;
use App\Models\DetailPembelian;
use App\Models\ReturPenjualan;
use App\Models\ReturPembelian;
use App\Models\PenyesuaianStok;
// use App\Models\Penjualan; // Tidak secara langsung, tapi via relasi
use App\Models\LogNomorSeri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
        $query = StokBarang::where('id_produk', $produk->id)
                            ->where('jumlah', '>', 0)
                            ->with(['supplier']) 
                            ->orderBy('diterima_at', 'asc');

        if ($request->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('id_batch', fn($batch) => $batch->id)
                ->addColumn('diterima_at_formatted', fn($batch) => Carbon::parse($batch->diterima_at)->isoFormat('D MMM YY, HH:mm'))
                ->addColumn('supplier_nama', fn($batch) => $batch->supplier->nama ?? 'Retur Pelanggan') 

                ->addColumn('total_jumlah_batch', fn($batch) => $batch->jumlah . ' ' . $produk->satuan)
                
                ->addColumn('sudah_dipesan', function($batch) use ($produk){
                    return '0 ' . $produk->satuan; 
                })

                ->addColumn('stok_siap_jual', function($batch) use ($produk){
                    if ($batch->kondisi === 'BAIK') {
                        $dipesan = 0; 
                        $siapJual = $batch->jumlah - $dipesan;
                        return '<span class="fw-bold text-success">' . $siapJual . ' ' . $produk->satuan . '</span>';
                    }
                    return '<span class="text-muted">0 ' . $produk->satuan . '</span>';
                })
                
                ->editColumn('lokasi', fn($batch) => $batch->lokasi)
                ->editColumn('kondisi', fn($batch) => ucwords(str_replace('_', ' ', $batch->kondisi)))
                ->addColumn('nomor_seri_tersedia', function($batch) use ($produk) {
                    if ($produk->memiliki_serial) {
                        // Langkah 1: Ambil SEMUA nomor seri yang ASLI berasal dari batch ini.
                        $semuaSerialAsalBatch = LogNomorSeri::where('id_stok_barang_asal', $batch->id)
                                                        ->where('status_log', 'DITERIMA')
                                                        ->pluck('nomor_seri');

                        if ($semuaSerialAsalBatch->isEmpty()) {
                            return '-';
                        }

                        // Langkah 2: Cari tahu mana dari serial tersebut yang statusnya sudah TIDAK TERSEDIA lagi.
                        $serialTidakTersedia = LogNomorSeri::whereIn('nomor_seri', $semuaSerialAsalBatch)
                                                            ->whereIn('status_log', ['TERJUAL', 'DIRETUR_PELANGGAN', 'RUSAK_FINAL_RETUR', 'DIRETUR_SUPPLIER'])
                                                            ->pluck('nomor_seri')
                                                            ->unique();

                        // Langkah 3: Kurangi daftar semua serial dengan yang tidak tersedia.
                        $serialYangMasihTersedia = $semuaSerialAsalBatch->diff($serialTidakTersedia);
                        
                        return $serialYangMasihTersedia->implode(', ');
                    }
                    return '-';
                })
                ->rawColumns(['stok_siap_jual']) // Pastikan kolom HTML di-render
                ->make(true);
        }
        return view('admin.laporan.stok.detail_batch_produk', compact('produk'));
    }


    public function generateKartuStok(Produk $produk, Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai')
                        ? Carbon::parse($request->input('tanggal_mulai'))->startOfDay()
                        : Carbon::now()->startOfMonth()->startOfDay();

        $tanggalSelesai = $request->input('tanggal_selesai')
                        ? Carbon::parse($request->input('tanggal_selesai'))->endOfDay()
                        : Carbon::now()->endOfDay();

        if ($tanggalSelesai->lt($tanggalMulai)) {
            $tanggalSelesai = $tanggalMulai->copy()->endOfDay();
        }

        // =========================================================================
        // 1. HITUNG SALDO AWAL (sebelum periode yang dipilih)
        // =========================================================================
        $masukSebelum = StokBarang::where('id_produk', $produk->id)
                                ->where('diterima_at', '<', $tanggalMulai)
                                ->sum('jumlah');
                                
        $keluarSebelum = DetailPenjualanStokAlokasi::whereHas('stokBarang', fn($q) => $q->where('id_produk', $produk->id))
                                ->whereHas('detailPenjualan.penjualan', fn($q) => $q->where('tanggal_penjualan', '<', $tanggalMulai))
                                ->sum('jumlah_diambil');
                                
        $saldoAwal = $masukSebelum - $keluarSebelum;
        
        $barisSaldoAwal = [
            'tanggal_display' => $tanggalMulai->isoFormat('D MMM YY'),
            'jenis_transaksi_display' => 'SALDO AWAL',
            'nomor_referensi_display' => '-', 'masuk_display' => '-', 'keluar_display' => '-',
            'saldo_display' => $saldoAwal . ' ' . $produk->satuan,
            'keterangan_tambahan_display' => "Saldo sebelum periode " . $tanggalMulai->isoFormat('D MMM YY')
        ];

        // =========================================================================
        // 2. KUMPULKAN SEMUA PERGERAKAN STOK DALAM PERIODE
        // =========================================================================
        $pergerakanStok = collect();

        // A. STOK MASUK: Dari Penerimaan PO dan Retur Pelanggan
        $stokMasuk = StokBarang::where('id_produk', $produk->id)
            ->whereBetween('diterima_at', [$tanggalMulai, $tanggalSelesai])
            ->with([
                'detailPembelian.pembelian', 
                'logNomorSeri.retur.detailPenjualan.penjualan.pelanggan' // <-- Relasi penting untuk melacak retur
            ])
            ->get();

        foreach ($stokMasuk as $stok) {
            $jenis_transaksi = 'Penerimaan Lain';
            $nomor_referensi = 'Batch: ' . $stok->id;
            $keterangan = 'Penerimaan manual/lainnya. Batch ID: ' . $stok->id;

            // Jika stok ini berasal dari PEMBELIAN
            if ($stok->detailPembelian && $stok->detailPembelian->pembelian) {
                $jenis_transaksi = 'Penerimaan PO';
                $nomor_referensi = $stok->detailPembelian->pembelian->nomor_pembelian;
                $keterangan = 'Diterima dari Supplier. Batch ID: ' . $stok->id;
            }
            // Jika stok ini adalah hasil dari RETUR (dikenali dari kondisi & log)
            elseif ($stok->kondisi === 'RUSAK' && $stok->logNomorSeri->isNotEmpty()) {
                $returInfo = $stok->logNomorSeri->first()->retur ?? null;
                if ($returInfo) {
                    $pelanggan = $returInfo->detailPenjualan->penjualan->pelanggan->nama ?? 'Umum';
                    $jenis_transaksi = 'Retur dari Pelanggan';
                    $nomor_referensi = $returInfo->nomor_retur;
                    $keterangan = "Retur dari Pelanggan: {$pelanggan}. Batch Karantina ID: {$stok->id}";
                }
            }

            $pergerakanStok->push([
                'tanggal' => $stok->diterima_at,
                'jenis_transaksi' => $jenis_transaksi,
                'nomor_referensi' => $nomor_referensi,
                'masuk' => $stok->jumlah,
                'keluar' => 0,
                'keterangan_tambahan' => $keterangan,
            ]);
        }

        // B. STOK KELUAR: Dari Penjualan
        $stokKeluar = DetailPenjualanStokAlokasi::whereHas('stokBarang', fn($q) => $q->where('id_produk', $produk->id))
            ->with(['detailPenjualan.penjualan.pelanggan'])
            ->whereHas('detailPenjualan.penjualan', fn($q) => $q->whereBetween('tanggal_penjualan', [$tanggalMulai, $tanggalSelesai]))
            ->get();

        foreach ($stokKeluar as $alokasi) {
            $penjualan = $alokasi->detailPenjualan->penjualan;
            $pelanggan = $penjualan->pelanggan->nama ?? 'Umum';
            
            $pergerakanStok->push([
                'tanggal' => $penjualan->tanggal_penjualan,
                'jenis_transaksi' => 'Penjualan',
                'nomor_referensi' => $penjualan->nomor_penjualan,
                'masuk' => 0,
                'keluar' => $alokasi->jumlah_diambil,
                'keterangan_tambahan' => "Terjual ke Pelanggan: {$pelanggan}. Dari Batch ID: {$alokasi->id_stok_barang}",
            ]);
        }

        // Anda bisa menambahkan sumber pergerakan lain di sini (Retur Pembelian, Penyesuaian, dll)

        // =========================================================================
        // 3. OLAH DATA FINAL UNTUK DITAMPILKAN
        // =========================================================================
        $pergerakanStokSorted = $pergerakanStok->sortBy('tanggal')->values();
        
        $saldoBerjalan = $saldoAwal;
        $pergerakanStokDenganSaldo = [];
        $pergerakanStokDenganSaldo[] = $barisSaldoAwal;

        foreach ($pergerakanStokSorted as $gerak) {
            $saldoBerjalan += $gerak['masuk'] - $gerak['keluar'];
            $pergerakanStokDenganSaldo[] = [
                'tanggal_display' => Carbon::parse($gerak['tanggal'])->isoFormat('D MMM YY, HH:mm'),
                'jenis_transaksi_display' => $gerak['jenis_transaksi'],
                'nomor_referensi_display' => $gerak['nomor_referensi'],
                'masuk_display' => $gerak['masuk'] > 0 ? ($gerak['masuk'] . ' ' . $produk->satuan) : '-',
                'keluar_display' => $gerak['keluar'] > 0 ? ($gerak['keluar'] . ' ' . $produk->satuan) : '-',
                'saldo_display' => $saldoBerjalan . ' ' . $produk->satuan,
                'keterangan_tambahan_display' => $gerak['keterangan_tambahan'] ?? '-'
            ];
        }

        return view('admin.laporan.stok.kartu_stok_produk', [
            'produk' => $produk,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'saldoAwalKalkulasi' => $saldoAwal,
            'pergerakanStokDenganSaldo' => $pergerakanStokDenganSaldo
        ]);
    }
}
