<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StokBarang;
use App\Models\RiwayatPerpindahanStok;
use App\Models\RiwayatPergerakanStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use App\Models\LogNomorSeri;

class PerpindahanStokController extends Controller
{
    /**
     * Menampilkan riwayat perpindahan stok.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = RiwayatPerpindahanStok::with(['stokBarang.produk', 'pengguna'])
                ->select('riwayat_perpindahan_stok.*');
            
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('dipindahkan_at', fn($row) => Carbon::parse($row->dipindahkan_at)->isoFormat('D MMM YYYY, HH:mm'))
                ->addColumn('produk', fn($row) => $row->stokBarang->produk->nama ?? 'N/A')
                ->addColumn('batch_asal', fn($row) => $row->id_stok_barang)
                ->addColumn('pengguna_nama', fn($row) => $row->pengguna->nama ?? '-')
                ->make(true);
        }
        return view('admin.perpindahan_stok.index');
    }

    /**
     * Menampilkan form untuk membuat perpindahan stok baru.
     */
    public function create()
    {
        $lokasiOptions = ['GUDANG' => 'GUDANG', 'TOKO' => 'TOKO'];
        return view('admin.perpindahan_stok.create', compact('lokasiOptions'));
    }

    /**
     * Menyimpan data perpindahan stok.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'id_stok_barang_asal' => 'required|exists:stok_barang,id',
        'jumlah_pindah' => 'required|integer|min:1',
        'ke_lokasi' => 'required|string',
        'catatan' => 'nullable|string',
        'nomor_seri_dipindah' => 'nullable|array',
    ]);

    $batchAsal = StokBarang::with('produk')->lockForUpdate()->find($validated['id_stok_barang_asal']);
    $jumlahPindah = (int)$validated['jumlah_pindah'];
    
    // Validasi
    if ($jumlahPindah > $batchAsal->jumlah) {
        return back()->with('error', 'Jumlah pindah melebihi sisa stok.')->withInput();
    }
    if ($validated['ke_lokasi'] === $batchAsal->lokasi) {
        return back()->with('error', 'Lokasi tujuan tidak boleh sama.')->withInput();
    }
    if ($batchAsal->produk->memiliki_serial && count($validated['nomor_seri_dipindah'] ?? []) !== $jumlahPindah) {
        return back()->with('error', 'Jumlah nomor seri yang dipilih tidak sesuai.')->withInput();
    }

    DB::beginTransaction();
    try {
        // === LANGKAH 1: UPDATE STOK FISIK ===
        $batchAsal->decrement('jumlah', $jumlahPindah);
        $batchBaru = $batchAsal->replicate();
        $batchBaru->jumlah = $jumlahPindah;
        $batchBaru->lokasi = $validated['ke_lokasi'];
        $batchBaru->save();

        // === LANGKAH 2: CATAT RIWAYAT PERPINDAHAN (UNTUK LOGISTIK) ===
        $riwayatPindah = RiwayatPerpindahanStok::create([
            'id_stok_barang' => $batchAsal->id, 'id_pengguna' => Auth::id(), 'jumlah' => $jumlahPindah,
            'dari_lokasi' => $batchAsal->lokasi, 'ke_lokasi' => $validated['ke_lokasi'], 'dipindahkan_at' => now(),
            'catatan' => "Batch baru dibuat dengan ID: {$batchBaru->id}. " . ($validated['catatan'] ?? ''),
        ]);

        // === LANGKAH 3: UPDATE DAN CATAT RIWAYAT UNTUK SETIAP NOMOR SERI ===
        $keteranganKeluar = "Pindah ke {$validated['ke_lokasi']} dari Batch {$batchAsal->id}";
        $keteranganMasuk = "Pindah dari {$batchAsal->lokasi} ke Batch {$batchBaru->id}";
        
        if ($batchAsal->produk->memiliki_serial) {
            foreach ($validated['nomor_seri_dipindah'] as $sn) {
                // A. Update Log Lama (log_nomor_seri)
                LogNomorSeri::where('nomor_seri', $sn)->where('id_stok_barang_asal', $batchAsal->id)
                    ->update(['id_stok_barang_asal' => $batchBaru->id]);

                // B. Catat di Riwayat Baru (riwayat_pergerakan_stok)
                // Keluar dari batch lama
                $saldoTerakhirKeluar = RiwayatPergerakanStok::where('id_produk', $batchAsal->id_produk)->lockForUpdate()->latest('id')->first();
                $saldoSebelumKeluar = $saldoTerakhirKeluar->saldo_setelah_transaksi ?? 0;
                RiwayatPergerakanStok::create([
                    'id_produk' => $batchAsal->id_produk, 'id_stok_barang_terkait' => $batchAsal->id,
                    'nomor_seri' => $sn, 'tipe_transaksi' => 'PINDAH_LOKASI_KELUAR',
                    'jumlah_keluar' => 1, 'saldo_setelah_transaksi' => $saldoSebelumKeluar - 1,
                    'id_referensi' => $riwayatPindah->id, 'tipe_referensi' => RiwayatPerpindahanStok::class,
                    'tanggal_transaksi' => now(), 'keterangan' => $keteranganKeluar, 'id_pengguna' => Auth::id(),
                ]);

                // Masuk ke batch baru
                $saldoTerakhirMasuk = RiwayatPergerakanStok::where('id_produk', $batchAsal->id_produk)->lockForUpdate()->latest('id')->first();
                $saldoSebelumMasuk = $saldoTerakhirMasuk->saldo_setelah_transaksi ?? 0;
                RiwayatPergerakanStok::create([
                    'id_produk' => $batchAsal->id_produk, 'id_stok_barang_terkait' => $batchBaru->id,
                    'nomor_seri' => $sn, 'tipe_transaksi' => 'PINDAH_LOKASI_MASUK',
                    'jumlah_masuk' => 1, 'saldo_setelah_transaksi' => $saldoSebelumMasuk + 1,
                    'id_referensi' => $riwayatPindah->id, 'tipe_referensi' => RiwayatPerpindahanStok::class,
                    'tanggal_transaksi' => now()->addSecond(), 'keterangan' => $keteranganMasuk, 'id_pengguna' => Auth::id(),
                ]);
            }
        }
        
        DB::commit();
        return redirect()->route('perpindahan-stok.index')->with('success', 'Perpindahan stok berhasil dicatat.');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
    }
}
    
    /**
     * AJAX untuk mencari batch stok yang akan dipindah.
     */
    public function searchBatchAjax(Request $request)
    {
        $term = $request->input('q', '');
        $batches = StokBarang::with(['produk:id,nama,kode_produk,memiliki_serial', 'supplier:id,nama'])
            ->where('jumlah', '>', 0)
            ->where(function ($q) use ($term) {
                $q->where('id', 'LIKE', "%{$term}%")
                  ->orWhereHas('produk', function ($prodQ) use ($term) {
                      $prodQ->where('nama', 'LIKE', "%{$term}%")
                            ->orWhere('kode_produk', 'LIKE', "%{$term}%");
                   })
                  // Tambahkan kemampuan mencari berdasarkan nama supplier
                  ->orWhereHas('supplier', function ($supQ) use ($term) {
                      $supQ->where('nama', 'LIKE', "%{$term}%");
                  });
            })
            ->limit(15)->get();

        $results = $batches->map(function ($batch) {
            
            $supplierInfo = $batch->supplier ? $batch->supplier->nama : 'Penerimaan Manual';
            $tipeInfo = ($batch->tipe_stok === 'KONSINYASI') ? ' | Konsinyasi' : '';
            $kondisiInfo = ($batch->kondisi !== 'BAIK') ? " | {$batch->kondisi}" : '';
            
            $text = "{$batch->produk->nama} (Batch #{$batch->id}) | Sumber: {$supplierInfo}{$tipeInfo}{$kondisiInfo} | Sisa: {$batch->jumlah}";
            return [
            'id' => $batch->id,
            'text' => $text,
            'sisa' => $batch->jumlah,
            'lokasi_asal' => $batch->lokasi,
            'has_serial' => (bool)$batch->produk->memiliki_serial,
            'supplier_nama' => $batch->supplier->nama ?? 'N/A',
        ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * FUNGSI BARU: AJAX untuk mengambil nomor seri dari batch.
     */
    public function getSerialsFromBatch(Request $request)
    {
        $validated = $request->validate(['id_stok_barang' => 'required|exists:stok_barang,id']);
        
        // Gunakan logika 'Log Terakhir' yang sudah terbukti
        $batch = StokBarang::find($validated['id_stok_barang']);
        $candidateSerials = LogNomorSeri::where('id_stok_barang_asal', $batch->id)->distinct()->pluck('nomor_seri');
        
        $availableSerials = [];
        foreach ($candidateSerials as $serial) {
            $latestLog = LogNomorSeri::where('nomor_seri', $serial)->latest('tanggal_status')->latest('id')->first();
            if ($latestLog && $latestLog->id_stok_barang_asal == $batch->id && $latestLog->status_log === 'DITERIMA') {
                $availableSerials[] = $serial;
            }
        }
        
        return response()->json(['success' => true, 'serials' => $availableSerials]);
    }
}