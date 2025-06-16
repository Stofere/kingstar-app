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
use App\Models\RiwayatPergerakanStok;
use App\Models\DetailPenjualan;
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

                // Ambil semua nomor seri unik yang PERNAH MASUK ke batch ini.
                $candidateSerials = RiwayatPergerakanStok::where('id_stok_barang_terkait', $batch->id)
                    ->where('jumlah_masuk', '>', 0)
                    ->whereNotNull('nomor_seri')
                    ->distinct()->pluck('nomor_seri');
                
                if ($candidateSerials->isEmpty()) return '-';

                $availableSerials = [];
                foreach ($candidateSerials as $serial) {
                    // Untuk setiap kandidat, cari tahu di mana lokasi terakhirnya berdasarkan riwayat.
                    $latestMovement = RiwayatPergerakanStok::where('nomor_seri', $serial)
                        ->latest('tanggal_transaksi')->latest('id')->first();
                    
                    // Serial ini dianggap ada di batch ini JIKA:
                    // Log terakhirnya menunjuk ke ID batch ini DAN
                    // transaksinya adalah transaksi MASUK (bukan keluar).
                    if ($latestMovement && $latestMovement->id_stok_barang_terkait == $batch->id && $latestMovement->jumlah_masuk > 0) {
                        $availableSerials[] = $serial;
                    }
                }
                
                return !empty($availableSerials) ? implode(', ', $availableSerials) : '-';
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
    $saldoAwalRecord = RiwayatPergerakanStok::where('id_produk', $produk->id)
        ->where('tanggal_transaksi', '<', $tanggalMulai)
        ->orderBy('id', 'desc')->first();
    $saldoAwal = $saldoAwalRecord->saldo_setelah_transaksi ?? 0;

    // 2. Ambil semua data riwayat dalam periode
    $pergerakanStok = RiwayatPergerakanStok::where('id_produk', $produk->id)
        ->whereBetween('tanggal_transaksi', [$tanggalMulai, $tanggalSelesai])
        ->with('pengguna') // Eager load pengguna
        ->orderBy('id', 'asc')
        ->get();
    
    // =====================================================================
    // ## FIX: Ambil semua data referensi secara efisien di awal          ##
    // =====================================================================

    // 3. Kumpulkan semua ID referensi berdasarkan tipenya
    $referensiIds = $pergerakanStok->groupBy('tipe_referensi')->map(function ($items) {
        return $items->pluck('id_referensi')->unique()->all();
    });

    // 4. Lakukan query untuk setiap tipe referensi HANYA JIKA ADA
    $referensiData = [];
    if (isset($referensiIds[DetailPembelian::class])) {
        $referensiData[DetailPembelian::class] = DetailPembelian::whereIn('id', $referensiIds[DetailPembelian::class])
            ->with('pembelian:id,nomor_pembelian')->get()->keyBy('id');
    }
    if (isset($referensiIds[DetailPenjualan::class])) {
        $referensiData[DetailPenjualan::class] = DetailPenjualan::whereIn('id', $referensiIds[DetailPenjualan::class])
            ->with('penjualan:id,nomor_penjualan')->get()->keyBy('id');
    }
    if (isset($referensiIds[ReturPembelian::class])) {
        $referensiData[ReturPembelian::class] = ReturPembelian::whereIn('id', $referensiIds[ReturPembelian::class])
            ->get(['id', 'nomor_retur'])->keyBy('id');
    }
    if (isset($referensiIds[ReturPenjualan::class])) {
        $referensiData[ReturPenjualan::class] = ReturPenjualan::whereIn('id', $referensiIds[ReturPenjualan::class])
            ->get(['id', 'nomor_retur'])->keyBy('id');
    }
    // =====================================================================
    // ## AKHIR BAGIAN PENGAMBILAN DATA EFISIEN                           ##
    // =====================================================================

    // 5. Siapkan data untuk view
    $dataUntukView = [];
    $dataUntukView[] = [
            'tanggal_display' => $tanggalMulai->isoFormat('D MMM YY'),
            'jenis_transaksi_display' => 'SALDO AWAL', 'nomor_referensi_display' => '-',
            'masuk_display' => '-', 'keluar_display' => '-', 'saldo_display' => $saldoAwal . ' ' . $produk->satuan,
            'keterangan_tambahan_display' => "Saldo sebelum periode " . $tanggalMulai->isoFormat('D MMMM YYYY')];
    
    foreach ($pergerakanStok as $gerak) {
        $nomorReferensi = '-';
        $keteranganTambahan = $gerak->keterangan ?? '';
        
        // Logika untuk menampilkan nomor referensi (sekarang sangat cepat, tanpa query)
        if ($gerak->id_referensi && isset($referensiData[$gerak->tipe_referensi])) {
            $referensi = $referensiData[$gerak->tipe_referensi][$gerak->id_referensi] ?? null;
            if ($referensi) {
                switch ($gerak->tipe_referensi) {
                    case DetailPenjualan::class:
                        $nomorReferensi = $referensi->penjualan->nomor_penjualan ?? 'N/A'; break;
                    case DetailPembelian::class:
                        $nomorReferensi = $referensi->pembelian->nomor_pembelian ?? 'N/A'; break;
                    case ReturPenjualan::class:
                    case ReturPembelian::class:
                        $nomorReferensi = $referensi->nomor_retur ?? 'N/A'; break;
                }
            }
        }

        if ($gerak->nomor_seri) {
            $keteranganTambahan .= " (SN: {$gerak->nomor_seri})";
        }

        $dataUntukView[] = [
            'tanggal_display' => $gerak->tanggal_transaksi->isoFormat('D MMM YY, HH:mm'),
            'jenis_transaksi_display' => ucwords(str_replace('_', ' ', $gerak->tipe_transaksi)),
            'nomor_referensi_display' => $nomorReferensi,
            'masuk_display' => $gerak->jumlah_masuk > 0 ? ($gerak->jumlah_masuk . ' ' . $produk->satuan) : '-',
            'keluar_display' => $gerak->jumlah_keluar > 0 ? ($gerak->jumlah_keluar . ' ' . $produk->satuan) : '-',
            'saldo_display' => $gerak->saldo_setelah_transaksi . ' ' . $produk->satuan,
            'keterangan_tambahan_display' => trim($keteranganTambahan),
        ];
    }
    
    return view('admin.laporan.stok.kartu_stok_produk', [
        'produk' => $produk, 'tanggalMulai' => $tanggalMulai, 'tanggalSelesai' => $tanggalSelesai,
        'saldoAwalKalkulasi' => $saldoAwal, 'pergerakanStokDenganSaldo' => $dataUntukView
    ]);
}
}
