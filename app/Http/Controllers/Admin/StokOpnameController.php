<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StokOpname;
use App\Models\DetailStokOpname;
use App\Models\StokBarang;
use App\Models\RiwayatPergerakanStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Str;

class StokOpnameController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = StokOpname::with(['penggunaMulai', 'penggunaSelesai']);
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('tanggal_opname', fn($row) => Carbon::parse($row->tanggal_opname)->isoFormat('D MMMM YYYY'))
                ->addColumn('dimulai_oleh', fn($row) => $row->penggunaMulai->nama ?? '-')
                ->addColumn('diselesaikan_oleh', fn($row) => $row->penggunaSelesai->nama ?? '-')
                ->editColumn('status', function ($row) {
                    $statusMapping = ['BERJALAN' => 'info', 'SELESAI' => 'success', 'DIBATALKAN' => 'danger'];
                    $class = $statusMapping[$row->status] ?? 'secondary';
                    return '<span class="badge bg-' . $class . '">' . str_replace('_', ' ', $row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('gudang.stok-opname.show', $row->id) . '" class="btn btn-primary btn-sm">Detail & Proses</a>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('admin.stok_opname.index');
    }

    public function create()
    {
        return view('admin.stok_opname.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_opname' => 'required|date',
            'lokasi' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);
        $stokOpname = StokOpname::create([
            'id_pengguna_mulai' => Auth::id(), 'tanggal_opname' => $validated['tanggal_opname'], 'lokasi' => $validated['lokasi'],
            'status' => 'BERJALAN', 'catatan' => $validated['catatan'], 'started_at' => now(),
        ]);
        return redirect()->route('gudang.stok-opname.show', $stokOpname->id)->with('success', 'Sesi Stok Opname baru berhasil dimulai.');
    }

    public function show(StokOpname $stokOpname)
    {
        // Snapshot stok jika sesi masih berjalan
        if ($stokOpname->status === 'BERJALAN') {
            $queryStok = StokBarang::query();
            if ($stokOpname->lokasi) {
                $queryStok->where('lokasi', $stokOpname->lokasi);
            }
            // Kita ambil juga yang stoknya 0 jika pernah ada di opname sebelumnya, untuk konsistensi
            $existingBatchIds = $stokOpname->detailStokOpname()->pluck('id_stok_barang');
            $queryStok->where('jumlah', '>', 0)->orWhereIn('id', $existingBatchIds);
            
            $stokUntukDiopname = $queryStok->get();

            foreach ($stokUntukDiopname as $stok) {
                DetailStokOpname::firstOrCreate(
                    ['id_stok_opname' => $stokOpname->id, 'id_stok_barang' => $stok->id],
                    ['jumlah_sistem' => $stok->jumlah]
                );
            }
        }

        $stokOpname->load(['penggunaMulai', 'penggunaSelesai']);
        $detailOpname = $stokOpname->detailStokOpname()->with('stokBarang.produk')->get();
        
        $availableSerials = [];
        foreach ($detailOpname as $detail) {
            if ($detail->stokBarang?->produk->memiliki_serial) {
                $serialsInBatch = RiwayatPergerakanStok::select('nomor_seri')
                    ->where('id_stok_barang_terkait', $detail->id_stok_barang)
                    ->whereNotNull('nomor_seri')->groupBy('nomor_seri')
                    ->havingRaw('SUM(jumlah_masuk) > SUM(jumlah_keluar)')
                    ->pluck('nomor_seri')->all();
                $availableSerials[$detail->id] = $serialsInBatch;
            }
        }
        
        $lokasiOptions = ['GUDANG', 'TOKO'];
        return view('admin.stok_opname.show', compact('stokOpname', 'detailOpname', 'availableSerials', 'lokasiOptions'));
    }
    
    /**
     * Menyelesaikan sesi, menyimpan hasil, dan membuat penyesuaian dalam satu aksi.
     */
    public function finishAndAdjust(Request $request, StokOpname $stokOpname)
    {
        if ($stokOpname->status !== 'BERJALAN') {
            return back()->with('error', 'Sesi opname ini sudah selesai atau dibatalkan.');
        }

        $validated = $request->validate([
            'details' => 'required|array',
            'details.*.jumlah_fisik' => 'nullable|integer|min:0',
            'details.*.catatan' => 'nullable|string|max:255',
            'details.*.serials_kurang' => 'nullable|array',
            'details.*.serials_lebih' => 'nullable|array',
            'details.*.harga_beli_lebih' => 'nullable|numeric|min:0',
            'details.*.lokasi_lebih' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Loop pertama: Simpan semua hasil hitung ke detail_stok_opname
            foreach ($validated['details'] as $detailId => $data) {
                $detail = DetailStokOpname::find($detailId);
                if ($detail && $detail->id_stok_opname == $stokOpname->id) {
                    $jumlahFisik = isset($data['jumlah_fisik']) ? (int)$data['jumlah_fisik'] : $detail->jumlah_sistem;
                    $selisih = $jumlahFisik - $detail->jumlah_sistem;
                    $detail->update([
                        'jumlah_fisik' => $jumlahFisik, 'selisih' => $selisih, 'catatan' => $data['catatan'],
                    ]);
                }
            }

            // Loop kedua: Proses penyesuaian hanya untuk yang ada selisih
            $detailsWithDifference = $stokOpname->detailStokOpname()->where('selisih', '!=', 0)->with('stokBarang.produk')->get();
            foreach ($detailsWithDifference as $detail) {
                $stokAsal = $detail->stokBarang;
                $produk = $stokAsal->produk;
                $selisih = $detail->selisih;
                
                $adjustmentData = $validated['details'][$detail->id] ?? [];
                
                if ($selisih < 0) { // SELISIH KURANG
                    $stokAsal->decrement('jumlah', abs($selisih));
                    if ($produk->memiliki_serial) {
                        $serialsKurang = $adjustmentData['serials_kurang'] ?? [];
                        if (count($serialsKurang) !== abs($selisih)) throw new \Exception("Jumlah serial kurang tidak cocok.");
                        foreach ($serialsKurang as $sn) {
                            $this->createAdjustmentHistory($stokOpname, $stokAsal, $detail, 'KURANG', 1, $sn);
                        }
                    } else {
                        $this->createAdjustmentHistory($stokOpname, $stokAsal, $detail, 'KURANG', abs($selisih));
                    }
                } elseif ($selisih > 0) { // SELISIH LEBIH
                    $hargaBeliBaru = $adjustmentData['harga_beli_lebih'] ?? 0;
                    $lokasiBaru = $adjustmentData['lokasi_lebih'] ?? $stokAsal->lokasi;
                    
                    $batchBaru = StokBarang::create([
                        'id_produk' => $produk->id, 'jumlah' => $selisih, 'harga_beli' => $hargaBeliBaru,
                        'lokasi' => $lokasiBaru, 'diterima_at' => now(), 'kondisi' => 'BAIK',
                    ]);

                    if ($produk->memiliki_serial) {
                        $serialsLebih = $adjustmentData['serials_lebih'] ?? [];
                        if (count($serialsLebih) !== $selisih) throw new \Exception("Jumlah serial lebih tidak cocok.");
                        foreach ($serialsLebih as $sn) {
                            $this->createAdjustmentHistory($stokOpname, $batchBaru, $detail, 'LEBIH', 1, $sn, true);
                        }
                    } else {
                        $this->createAdjustmentHistory($stokOpname, $batchBaru, $detail, 'LEBIH', $selisih, null, true);
                    }
                }
            }

            // Selesaikan sesi opname
            $stokOpname->status = 'SELESAI';
            $stokOpname->id_pengguna_selesai = Auth::id();
            $stokOpname->finished_at = now();
            $stokOpname->catatan = ($stokOpname->catatan ?? '') . "\n[SISTEM] Sesi diselesaikan dan penyesuaian dibuat pada " . now();
            $stokOpname->save();

            DB::commit();
            return redirect()->route('gudang.stok-opname.show', $stokOpname)->with('success', 'Stok Opname berhasil diselesaikan dan penyesuaian telah dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }
    
    private function createAdjustmentHistory($stokOpname, $stok, $detail, $type, $qty, $serial = null, $isNewBatch = false)
    {
        $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $stok->id_produk)->lockForUpdate()->latest('id')->first();
        $saldoSebelumnya = $saldoTerakhir->saldo_setelah_transaksi ?? 0;

        $jumlahMasuk = ($type === 'LEBIH') ? $qty : 0;
        $jumlahKeluar = ($type === 'KURANG') ? $qty : 0;
        
        $keterangan = "Hasil Stok Opname #" . $stokOpname->id . ". " . ($detail->catatan ?? '');
        if ($isNewBatch) $keterangan .= " (Batch Baru ID: {$stok->id})";

        RiwayatPergerakanStok::create([
            'id_produk' => $stok->id_produk, 'id_stok_barang_terkait' => $stok->id,
            'nomor_seri' => $serial, 'tipe_transaksi' => 'PENYESUAIAN_OPNAME_' . $type,
            'jumlah_masuk' => $jumlahMasuk, 'jumlah_keluar' => $jumlahKeluar,
            'saldo_setelah_transaksi' => $saldoSebelumnya + $jumlahMasuk - $jumlahKeluar,
            'id_referensi' => $stokOpname->id, 'tipe_referensi' => StokOpname::class,
            'tanggal_transaksi' => now(), 'id_pengguna' => Auth::id(), 'keterangan' => $keterangan,
        ]);
    }
}