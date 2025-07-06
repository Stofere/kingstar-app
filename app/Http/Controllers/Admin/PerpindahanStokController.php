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
            // === LANGKAH 1: UPDATE STOK FISIK (Tetap Sama) ===
            // Jika seluruh batch dipindah, cukup update lokasi. Jika parsial, buat batch baru.
            $batchBaru = null;
            if ($jumlahPindah == $batchAsal->jumlah) {
                // Pindah seluruh batch, hanya update lokasi
                $batchAsal->update(['lokasi' => $validated['ke_lokasi']]);
                $batchBaru = $batchAsal; // Batch baru adalah batch asal yang sudah diupdate
            } else {
                // Pindah parsial, kurangi batch asal dan buat batch baru
                $batchAsal->decrement('jumlah', $jumlahPindah);
                $batchBaru = $batchAsal->replicate();
                $batchBaru->jumlah = $jumlahPindah;
                $batchBaru->lokasi = $validated['ke_lokasi'];
                $batchBaru->save();
            }

            // === LANGKAH 2: CATAT RIWAYAT PERPINDAHAN (UNTUK LOGISTIK) ===
            $riwayatPindah = RiwayatPerpindahanStok::create([
                'id_stok_barang' => $batchAsal->id, 'id_pengguna' => Auth::id(), 'jumlah' => $jumlahPindah,
                'dari_lokasi' => $batchAsal->lokasi, 'ke_lokasi' => $validated['ke_lokasi'], 'dipindahkan_at' => now(),
                'catatan' => "Batch baru dibuat dengan ID: {$batchBaru->id}. " . ($validated['catatan'] ?? ''),
            ]);

            // === LANGKAH 3 (BARU): CATAT DI RIWAYAT PERGERAKAN STOK ===
            $produk = $batchAsal->produk;
            $keterangan = "Pindah dari {$batchAsal->lokasi} ke {$validated['ke_lokasi']}. " . ($validated['catatan'] ?? '');
            
            // Jika berserial, buat satu riwayat per serial
            if ($produk->memiliki_serial) {
                foreach ($validated['nomor_seri_dipindah'] as $sn) {
                    $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->latest('id')->value('saldo_setelah_transaksi') ?? 0;
                    
                    // Tidak ada stok keluar-masuk, karena saldo total tidak berubah.
                    // Kita hanya update batch terkait dan lokasi.
                    RiwayatPergerakanStok::create([
                        'id_produk' => $produk->id,
                        'id_stok_barang_terkait' => $batchBaru->id, // Langsung kaitkan ke batch baru
                        'nomor_seri' => $sn,
                        'tipe_transaksi' => 'PERPINDAHAN_STOK', // Satu tipe transaksi
                        'jumlah_masuk' => 0, // Saldo tidak berubah
                        'jumlah_keluar' => 0,
                        'saldo_setelah_transaksi' => $saldoTerakhir, // Saldo tetap sama
                        'id_referensi' => $riwayatPindah->id,
                        'tipe_referensi' => RiwayatPerpindahanStok::class,
                        'tanggal_transaksi' => now(),
                        'keterangan' => $keterangan,
                        'id_pengguna' => Auth::id(),
                    ]);
                }
            } else { // Jika non-serial, cukup buat satu record
                $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->latest('id')->value('saldo_setelah_transaksi') ?? 0;
                
                RiwayatPergerakanStok::create([
                    'id_produk' => $produk->id,
                    'id_stok_barang_terkait' => $batchBaru->id,
                    'tipe_transaksi' => 'PERPINDAHAN_STOK',
                    'jumlah_masuk' => 0, // Saldo tidak berubah
                    'jumlah_keluar' => 0,
                    'saldo_setelah_transaksi' => $saldoTerakhir,
                    'id_referensi' => $riwayatPindah->id,
                    'tipe_referensi' => RiwayatPerpindahanStok::class,
                    'tanggal_transaksi' => now(),
                    'keterangan' => $keterangan,
                    'id_pengguna' => Auth::id(),
                ]);
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
     * AJAX untuk mengambil nomor seri dari batch menggunakan RiwayatPergerakanStok.
     */
    public function getSerialsFromBatch(Request $request)
    {
        $validated = $request->validate(['id_stok_barang' => 'required|exists:stok_barang,id']);
        $idStokBarang = $validated['id_stok_barang'];

        $batch = StokBarang::find($idStokBarang);
        if (!$batch || !$batch->produk->memiliki_serial) {
            return response()->json(['success' => true, 'serials' => []]); // Kirim array kosong jika produk non-serial
        }
        
        // 1. Dapatkan semua kandidat serial yang PERNAH tercatat di sistem
        // Ini lebih andal daripada hanya mencari di batch ini.
        $candidateSerials = RiwayatPergerakanStok::where('id_produk', $batch->id_produk)
            ->whereNotNull('nomor_seri')
            ->distinct()
            ->pluck('nomor_seri');
        
        if ($candidateSerials->isEmpty()) {
            return response()->json(['success' => true, 'serials' => []]);
        }

        // 2. Dari semua kandidat, cari ID pergerakan TERAKHIR untuk setiap serial.
        $latestMovementIds = RiwayatPergerakanStok::select(DB::raw('MAX(id) as id'))
            ->whereIn('nomor_seri', $candidateSerials)
            ->groupBy('nomor_seri')
            ->pluck('id');

        // 3. ### INI LOGIKA KUNCINYA ###
        // Ambil semua serial dari pergerakan terakhir yang:
        //    a. Merupakan transaksi MASUK (jumlah_masuk > 0) ATAU merupakan perpindahan (PERPINDAHAN_STOK)
        //    b. Dan benar-benar milik BATCH INI yang sedang kita periksa.
        $availableSerials = RiwayatPergerakanStok::whereIn('id', $latestMovementIds)
            ->where('id_stok_barang_terkait', $idStokBarang) // Harus milik batch ini
            ->where(function ($query) {
                $query->where('jumlah_masuk', '>', 0)
                    ->orWhere('tipe_transaksi', 'PERPINDAHAN_STOK');
            })
            ->pluck('nomor_seri')
            ->values()
            ->all();
        
        return response()->json(['success' => true, 'serials' => $availableSerials]);
    }
}