<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturPembelian;
use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\StokBarang;
use App\Models\RiwayatPergerakanStok;
use App\Models\LogNomorSeri;
use App\Models\Supplier; 
use App\Models\Produk;   
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class ReturPembelianController extends Controller
{
    /**
     * Menampilkan form untuk membuat retur pembelian baru.
     */
    public function create()
    {
        $alasanReturOptions = [
            'BARANG_RUSAK_DARI_SUPPLIER' => 'Barang Rusak dari Supplier (Saat Terima/Cek)',
            'SALAH_KIRIM_SUPPLIER' => 'Salah Kirim Barang oleh Supplier',
            'KELEBIHAN_KIRIM_SUPPLIER' => 'Kelebihan Kirim oleh Supplier',
            'KUALITAS_TIDAK_SESUAI' => 'Kualitas Tidak Sesuai Pesanan',
            'RETUR_PELANGGAN_CACAT_PRODUKSI' => 'Retur Pelanggan (Cacat Produksi dari Supplier)',
            'LAINNYA' => 'Lainnya',
        ];

        // Ini status yang akan kita ajukan/harapkan dari supplier
        $tindakanLanjutSupplierOptions = [
            'MENUNGGU_RESPONS_SUPPLIER' => 'Menunggu Respons Supplier',
            'PROSES_PENGGANTIAN_BARANG' => 'Proses Penggantian Barang',
            'PROSES_REFUND_UANG' => 'Proses Refund Uang',
            // Status "SELESAI_DIGANTI", "SELESAI_DIREFUND", "DITOLAK_SUPPLIER" akan diupdate nanti
        ];


        return view('admin.retur_pembelian.create', compact('alasanReturOptions', 'tindakanLanjutSupplierOptions'));
    }

    /**
     * AJAX untuk mencari batch StokBarang yang bisa diretur.
     * Bisa dicari berdasarkan ID Produk, Nama Produk, Kode Produk, ID Batch, atau Nama Supplier.
     */
    public function searchBatchStokAjax(Request $request)
    {
        $searchTerm = $request->input('q', '');
        $page = $request->input('page', 1);
        $limit = 15;

        $query = StokBarang::with(['produk', 'supplier'])
            ->where('jumlah', '>', 0) // Hanya batch yang masih ada stoknya
            // ->where('kondisi', '!=', 'DIRETUR_KE_SUPPLIER') // Contoh jika ada kondisi khusus
            ->where(function ($q) use ($searchTerm) {
                $q->where('id', 'LIKE', "%{$searchTerm}%") // Cari berdasarkan ID Batch
                  ->orWhereHas('produk', function ($prodQ) use ($searchTerm) {
                      $prodQ->where('nama', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('kode_produk', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('supplier', function ($supQ) use ($searchTerm) {
                      $supQ->where('nama', 'LIKE', "%{$searchTerm}%");
                  });
            });

        $batches = $query->orderBy('diterima_at', 'desc')->orderBy('id', 'desc') // Batch terbaru dulu
                         ->paginate($limit);

        $results = $batches->map(function ($batch) {
            $produkText = $batch->produk ? ($batch->produk->nama . ($batch->produk->kode_produk ? " ({$batch->produk->kode_produk})" : "")) : "Produk Tidak Diketahui";
            $supplierText = $batch->supplier ? $batch->supplier->nama : "Supplier Tidak Diketahui";
            $text = "Batch ID: {$batch->id} - {$produkText} | Supplier: {$supplierText} | Terima: " . Carbon::parse($batch->diterima_at)->isoFormat('D MMM YYYY') . " | Sisa: {$batch->jumlah}";
            return [
                'id' => $batch->id, // ID StokBarang (batch)
                'text' => $text,
                'id_produk' => $batch->id_produk,
                'nama_produk' => $produkText,
                'memiliki_serial' => $batch->produk ? (bool)$batch->produk->memiliki_serial : false,
                'sisa_stok_batch' => $batch->jumlah,
                'id_supplier' => $batch->id_supplier, // Untuk info di form
                'nama_supplier' => $supplierText,   // Untuk info di form
            ];
        });

        return response()->json([
            'items' => $results,
            'total_count' => $batches->total()
        ]);
    }

    /**
     * =========================================================================
     * FUNGSI KUNCI YANG DI REVISI (MENGGUNAKAN RIWAYAT_PERGERAKAN_STOK)
     * AJAX untuk mendapatkan nomor seri yang valid untuk diretur dari sebuah batch.
     * =========================================================================
     */
    public function getSerialsFromBatchAjax(Request $request)
    {
        $request->validate(['id_stok_barang' => 'required|integer|exists:stok_barang,id']);
        $idStokBarang = $request->input('id_stok_barang');

        $batch = StokBarang::with('produk')->find($idStokBarang);
        if (!$batch || !$batch->produk || !$batch->produk->memiliki_serial) {
            return response()->json(['success' => false, 'serials' => [], 'message' => 'Batch tidak ditemukan atau produk tidak berserial.']);
        }

        // Logika tunggal yang berfungsi untuk SEMUA kondisi batch
        
        // 1. Dapatkan semua kandidat serial yang PERNAH tercatat masuk ke batch ini.
        $candidateSerials = RiwayatPergerakanStok::where('id_stok_barang_terkait', $idStokBarang)
            ->where('jumlah_masuk', '>', 0)
            ->whereNotNull('nomor_seri')
            ->distinct()
            ->pluck('nomor_seri');

        if ($candidateSerials->isEmpty()) {
            return response()->json(['success' => false, 'serials' => [], 'message' => 'Tidak ada catatan nomor seri untuk batch ini.']);
        }

        // 2. Dari semua kandidat, cari tahu ID record pergerakan TERAKHIR untuk setiap serial.
        $latestMovementIds = RiwayatPergerakanStok::select(DB::raw('MAX(id) as id'))
            ->whereIn('nomor_seri', $candidateSerials)
            ->groupBy('nomor_seri')
            ->pluck('id');

        // 3. Ambil semua serial dari pergerakan terakhir yang:
        //    a. Merupakan transaksi MASUK (bukan keluar).
        //    b. Benar-benar milik batch yang sedang kita cek.
        $availableSerials = RiwayatPergerakanStok::whereIn('id', $latestMovementIds)
            ->where('jumlah_masuk', '>', 0)
            ->where('id_stok_barang_terkait', $idStokBarang)
            ->pluck('nomor_seri')
            ->values()
            ->all();

        return response()->json(['success' => true, 'serials' => $availableSerials]);
    }
    
    /**
     * =========================================================================
     * Logika validasi dan update disesuaikan untuk RIWAYAT_PERGERAKAN_STOke
     * =========================================================================
     */
    public function store(Request $request)
    {
        // Bagian Validasi Anda sudah sangat bagus, kita pertahankan
        $validated = $request->validate([
            'tanggal_retur' => 'required|date_format:Y-m-d\TH:i',
            'id_supplier_tujuan' => 'required|integer|exists:supplier,id',
            'items_retur' => 'required|array|min:1',
            'items_retur.*.id_stok_barang' => ['required', 'integer', Rule::exists('stok_barang', 'id')],
            'items_retur.*.id_produk_retur' => ['required', 'integer', Rule::exists('produk', 'id')],
            'items_retur.*.jumlah_retur' => 'required|integer|min:1',
            'items_retur.*.alasan_retur' => 'required|string|max:255',
            'items_retur.*.tindakan_lanjut_supplier' => 'required|string|max:100',
            'items_retur.*.nomor_seri_diretur' => 'nullable|array',
            'items_retur.*.nomor_seri_diretur.*' => 'nullable|string|max:255',
            'items_retur.*.catatan_ke_supplier_item' => 'nullable|string|max:500',
            'catatan_global_retur_pembelian' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
       
        try {
            $tanggalRetur = Carbon::parse($validated['tanggal_retur']);
            // Nomor retur bisa dibuat satu per item atau satu per sesi.
            // Asumsi saat ini satu per sesi (beberapa item dalam 1 form punya nomor sama)
            $nomorReturPembelianOtomatis = $this->generateNextReturPembelianNumber($tanggalRetur);
            
            foreach ($validated['items_retur'] as $index => $itemReturData) {
                
                if ((int)($itemReturData['jumlah_retur'] ?? 0) <= 0) continue;

                $stokBarang = StokBarang::with('produk')->lockForUpdate()->find($itemReturData['id_stok_barang']);
                if (!$stokBarang) throw new \Exception("Batch Stok ID {$itemReturData['id_stok_barang']} tidak ditemukan.");
                
                $produk = $stokBarang->produk;
                $jumlahReturSaatIni = (int)$itemReturData['jumlah_retur'];

                if ($jumlahReturSaatIni > $stokBarang->jumlah) {
                    throw new \Exception("Jumlah retur ({$jumlahReturSaatIni}) untuk produk '{$produk->nama}' (Batch ID {$stokBarang->id}) melebihi sisa stok ({$stokBarang->jumlah}).");
                }

                $serialsDireturInputCleaned = collect($itemReturData['nomor_seri_diretur'] ?? [])->filter()->unique()->values()->all();

                if ($produk->memiliki_serial && count($serialsDireturInputCleaned) !== $jumlahReturSaatIni) {
                    throw new \Exception("Jumlah nomor seri (".count($serialsDireturInputCleaned).") tidak sesuai jumlah retur ({$jumlahReturSaatIni}) untuk produk '{$produk->nama}'.");
                }

                // Buat record di retur_pembelian
                $retur = ReturPembelian::create([
                    'id_stok_barang' => $stokBarang->id,
                    'id_pengguna' => Auth::id(),
                    'nomor_retur' => $nomorReturPembelianOtomatis,
                    'jumlah_retur' => $jumlahReturSaatIni,
                    'nomor_seri_diretur' => implode(',', $serialsDireturInputCleaned),
                    'alasan_retur' => $itemReturData['alasan_retur'],
                    'catatan_ke_supplier' => $itemReturData['catatan_ke_supplier_item'] ?? null,
                    'tindakan_lanjut_supplier' => $itemReturData['tindakan_lanjut_supplier'],
                    'catatan_internal_retur' => ($index === 0) ? $validated['catatan_global_retur_pembelian'] : null,
                    'tanggal_retur' => $tanggalRetur,
                ]);

                // 1. Kurangi Stok Fisik pada Batch
                $stokBarang->decrement('jumlah', $jumlahReturSaatIni);
                
                // =====================================================================
                // ## PENCATATAN BARU KE RIWAYAT PERGERAKAN STOK (REVISI FINAL)       ##
                // =====================================================================
                $keteranganRiwayat = 'Diretur ke Supplier (' . ($stokBarang->supplier->nama ?? 'N/A') . ')';

                if ($produk->memiliki_serial && !empty($serialsDireturInputCleaned)) {
                    // Jika BERSERIAL, buat satu baris untuk setiap nomor seri
                    foreach ($serialsDireturInputCleaned as $snRetur) {
                        $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->value('saldo_setelah_transaksi') ?? 0;
                        
                        RiwayatPergerakanStok::create([
                            'id_produk' => $produk->id,
                            'id_stok_barang_terkait' => $stokBarang->id,
                            'nomor_seri' => $snRetur,
                            'tipe_transaksi' => 'RETUR_SUPPLIER',
                            'jumlah_masuk' => 0,
                            'jumlah_keluar' => 1, // Selalu 1 untuk pergerakan serial
                            'saldo_setelah_transaksi' => $saldoTerakhir - 1,
                            'id_referensi' => $retur->id,
                            'tipe_referensi' => get_class($retur),
                            'tanggal_transaksi' => $tanggalRetur,
                            'keterangan' => $keteranganRiwayat,
                            'id_pengguna' => Auth::id(),
                        ]);
                    }
                } else {
                    // Jika TIDAK BERSERIAL, buat satu baris untuk total jumlah
                    $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->value('saldo_setelah_transaksi') ?? 0;
                    
                    RiwayatPergerakanStok::create([
                        'id_produk' => $produk->id,
                        'id_stok_barang_terkait' => $stokBarang->id,
                        'nomor_seri' => null,
                        'tipe_transaksi' => 'RETUR_SUPPLIER',
                        'jumlah_masuk' => 0,
                        'jumlah_keluar' => $jumlahReturSaatIni,
                        'saldo_setelah_transaksi' => $saldoTerakhir - $jumlahReturSaatIni,
                        'id_referensi' => $retur->id,
                        'tipe_referensi' => get_class($retur),
                        'tanggal_transaksi' => $tanggalRetur,
                        'keterangan' => $keteranganRiwayat,
                        'id_pengguna' => Auth::id(),
                    ]);
                }
                // =====================================================================
                // ## AKHIR PENCATATAN BARU                                         ##
                // =====================================================================
            } // End foreach


            DB::commit();

            return redirect()->route('admin.retur_pembelian.index')
                             ->with('success', "Retur Pembelian ({$nomorReturPembelianOtomatis}) ke supplier berhasil disimpan.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan retur pembelian: ' . $e->getMessage())->withInput();
        }
    }

    private function generateNextReturPembelianNumber(Carbon $date): string
    {
        $branchCode = config('app.branch_code', 'KGT');
        $dateFormatted = $date->format('dmy');
        $prefix = "RTRB-{$branchCode}-{$dateFormatted}-"; // RTRB untuk Retur Beli
        $lastToday = ReturPembelian::whereDate('tanggal_retur', $date->toDateString())
                                ->where('nomor_retur', 'LIKE', $prefix . '%')
                                ->orderBy('nomor_retur', 'desc')
                                ->first();
        $nextSequence = 1;
        if ($lastToday) {
            $lastSequencePart = substr($lastToday->nomor_retur, strlen($prefix));
            if (is_numeric($lastSequencePart)) {
                $nextSequence = (int)$lastSequencePart + 1;
            }
        }
        return $prefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Menampilkan daftar retur pembelian yang sudah dibuat.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ReturPembelian::with([
                            'stokBarang.produk', // Produk dari batch yang diretur
                            'stokBarang.supplier', // Supplier dari batch yang diretur (supplier asal batch)
                            'pengguna' // Admin yang memproses retur
                        ])
                        ->select('retur_pembelian.*')
                        ->orderBy('retur_pembelian.tanggal_retur', 'desc')
                        ->orderBy('retur_pembelian.id', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('nama_produk', function($row){
                    return $row->stokBarang->produk->nama ?? '-';
                })
                ->addColumn('supplier_asal_batch', function($row){ // Supplier dari batch yang diretur
                    return $row->stokBarang->supplier->nama ?? 'N/A (Batch)';
                })
                ->editColumn('tanggal_retur_formatted', function ($row) {
                    return Carbon::parse($row->tanggal_retur)->isoFormat('D MMM YYYY, HH:mm');
                })
                ->addColumn('jumlah_retur_formatted', function ($row) {
                    return $row->jumlah_retur . ' unit';
                })
                ->addColumn('tindakan_lanjut_supplier_display', function($row){
                    // Anda bisa buat helper atau array mapping untuk display nama yang lebih baik
                    // Ini adalah opsi yang dipilih saat retur, bukan status update dari supplier
                    $options = [
                        'MENUNGGU_RESPONS_SUPPLIER' => 'Menunggu Respons Supplier',
                        'PROSES_PENGGANTIAN_BARANG' => 'Diajukan Penggantian',
                        'PROSES_REFUND_UANG' => 'Diajukan Refund',
                    ];
                    return $options[$row->tindakan_lanjut_supplier] ?? ucwords(str_replace('_', ' ', $row->tindakan_lanjut_supplier));
                })
                ->addColumn('admin_proses', function($row){
                    return $row->pengguna->nama ?? '-';
                })
                ->addColumn('action', function ($row) {
                    $btnShow = '<a href="' . route('admin.retur_pembelian.show', $row->id) . '" class="btn btn-info btn-sm me-1" title="Lihat Detail Retur"><i class="bi bi-eye"></i></a>';
                    // Jika ada status update dari supplier, mungkin ada tombol edit status di sini
                    return $btnShow;
                })
                ->rawColumns(['action', 'tindakan_lanjut_supplier_display'])
                ->make(true);
        }
        return view('admin.retur_pembelian.index');
    }

    /**
     * Menampilkan detail satu retur pembelian.
     */
    public function show(ReturPembelian $returPembelian) // Route model binding
    {
        $returPembelian->load([
            'stokBarang.produk',
            'stokBarang.supplier', // Supplier asal batch
            'pengguna',
            // Jika retur pembelian terkait dengan PO asal, Anda mungkin ingin menampilkan info PO tersebut
            // Ini memerlukan relasi dari StokBarang -> DetailPembelian -> Pembelian
            'stokBarang.detailPembelian.pembelian'
        ]);
        return view('admin.retur_pembelian.show', compact('returPembelian'));
    }

    public function edit(ReturPembelian $returPembelian) // Route Model Binding
    {
        // Validasi apakah retur ini boleh diupdate statusnya
        $statusFinal = ['SELESAI_DIGANTI', 'SELESAI_DIREFUND', 'DITOLAK_SUPPLIER'];
        if (in_array($returPembelian->tindakan_lanjut_supplier, $statusFinal)) {
            return redirect()->route('admin.retur_pembelian.index')->with('info', 'Retur pembelian ini sudah memiliki status tindak lanjut final dan tidak dapat diedit.');
        }

        $returPembelian->load(['stokBarang.produk', 'stokBarang.supplier', 'pengguna']);

        // Opsi status tindak lanjut final dari supplier
        $tindakanLanjutSupplierFinalOptions = [
            'MENUNGGU_RESPONS_SUPPLIER' => 'Menunggu Respons Supplier', // Mungkin ini status awal
            'PROSES_PENGGANTIAN_BARANG' => 'Diajukan/Proses Penggantian Barang',
            'PROSES_REFUND_UANG' => 'Diajukan/Proses Refund Uang',
            'SELESAI_DIGANTI' => 'SELESAI - Barang Sudah Diganti Supplier',
            'SELESAI_DIREFUND' => 'SELESAI - Sudah Direfund Supplier',
            'DITOLAK_SUPPLIER' => 'DITOLAK oleh Supplier',
        ];

        return view('admin.retur_pembelian.edit', compact('returPembelian', 'tindakanLanjutSupplierFinalOptions'));
    }

    public function update(Request $request, ReturPembelian $returPembelian)
    {
        $statusFinalSebelumnya = ['SELESAI_DIGANTI', 'SELESAI_DIREFUND', 'DITOLAK_SUPPLIER'];
        if (in_array($returPembelian->tindakan_lanjut_supplier, $statusFinalSebelumnya)) {
            return redirect()->route('admin.retur_pembelian.index')->with('info', 'Retur pembelian ini sudah memiliki status tindak lanjut final dan tidak dapat diedit.');
        }

        $validated = $request->validate([
            'tindakan_lanjut_supplier' => 'required|string|max:100', // Validasi dengan key dari $tindakanLanjutSupplierFinalOptions
            'catatan_ke_supplier' => 'nullable|string|max:1000',
            'catatan_internal_retur' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $returPembelian->tindakan_lanjut_supplier = $validated['tindakan_lanjut_supplier'];
            if ($request->filled('catatan_ke_supplier')) {
                $returPembelian->catatan_ke_supplier = $validated['catatan_ke_supplier'];
            }
            if ($request->filled('catatan_internal_retur')) {
                $returPembelian->catatan_internal_retur = $validated['catatan_internal_retur'];
            }
            $returPembelian->save();

           
            if ($validated['tindakan_lanjut_supplier'] === 'SELESAI_DIGANTI') {

                session()->flash('info_barang_pengganti', "Status retur pembelian {$returPembelian->nomor_retur} diupdate. Jangan lupa catat penerimaan untuk barang pengganti dari supplier jika sudah datang.");
            }

            DB::commit();
        
            return redirect()->route('admin.retur_pembelian.index')->with('success', "Tindak lanjut untuk Retur Pembelian No. {$returPembelian->nomor_retur} berhasil diperbarui.");

        } catch (\Exception $e) {
            DB::rollBack();
        
            return redirect()->back()->with('error', 'Gagal memperbarui tindak lanjut retur: ' . $e->getMessage())->withInput();
        }
    }


}