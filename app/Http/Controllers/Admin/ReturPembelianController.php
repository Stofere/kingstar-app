<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturPembelian;
use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\StokBarang;
use App\Models\RiwayatPergerakanStok;
use App\Models\DetailReturPembelian;
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
        $idSupplier = $request->input('id_supplier'); 
        $page = $request->input('page', 1);
        $limit = 15;

        $query = StokBarang::with(['produk', 'supplier'])
        ->where('jumlah', '>', 0); // Hanya batch yang masih ada stoknya
            // ### PERBAIKAN UTAMA DI SINI ###
            // Jika ID supplier dikirim, tambahkan filter ini ke query
            if ($idSupplier) {
                $query->where('id_supplier', $idSupplier);
            }
            // Filter pencarian berdasarkan keyword 
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('produk', function ($prodQ) use ($searchTerm) {
                    $prodQ->where('nama', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('kode_produk', 'LIKE', "%{$searchTerm}%");
                });
                // Kita tidak perlu lagi mencari berdasarkan nama supplier di sini karena sudah difilter di atas
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

            // 1. Buat SATU record Header Retur Pembelian
            $returHeader = ReturPembelian::create([
                'nomor_retur' => $this->generateNextReturPembelianNumber($tanggalRetur),
                'id_pengguna' => Auth::id(),
                'id_supplier_tujuan' => $validated['id_supplier_tujuan'],
                'tanggal_retur' => $tanggalRetur,
                'catatan_internal_retur' => $validated['catatan_global_retur_pembelian'],
                'status' => 'PROSES',
            ]);
            
            // 2. Loop untuk membuat BANYAK record Detail
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

                // Buat record di tabel detail_retur_pembelian
                $detailRetur = DetailReturPembelian::create([
                    'id_retur_pembelian' => $returHeader->id, // Link ke header
                    'id_stok_barang' => $itemReturData['id_stok_barang'],
                    'jumlah_retur' => $itemReturData['jumlah_retur'],
                    'nomor_seri_diretur' => implode(',', $itemReturData['nomor_seri_diretur'] ?? []),
                    'alasan_retur' => $itemReturData['alasan_retur'],
                    'tindakan_lanjut_supplier' => $itemReturData['tindakan_lanjut_supplier'],
                    'catatan_ke_supplier' => $itemReturData['catatan_ke_supplier_item'] ?? null,
                ]);

                // 1. Kurangi Stok Fisik pada Batch
                $stokBarang->decrement('jumlah', $jumlahReturSaatIni);
                
                // =====================================================================
                // ## PENCATATAN BARU KE RIWAYAT PERGERAKAN STOK (REVISI FINAL)       ##
                // =====================================================================
                $keteranganRiwayat = 'Pengembalian barang ke Supplier: ' . ($stokBarang->supplier->nama ?? 'N/A');
                $tipeTransaksiRiwayat = 'RETUR_KE_SUPPLIER';

                if ($produk->memiliki_serial && !empty($serialsDireturInputCleaned)) {
                    // Jika BERSERIAL, buat satu baris untuk setiap nomor seri
                    foreach ($serialsDireturInputCleaned as $snRetur) {
                        $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->value('saldo_setelah_transaksi') ?? 0;
                        
                        RiwayatPergerakanStok::create([
                            'id_produk' => $produk->id,
                            'id_stok_barang_terkait' => $stokBarang->id,
                            'nomor_seri' => $snRetur,
                            'tipe_transaksi' => $tipeTransaksiRiwayat,
                            'jumlah_masuk' => 0,
                            'jumlah_keluar' => 1, // Selalu 1 untuk pergerakan serial
                            'saldo_setelah_transaksi' => $saldoTerakhir - 1,
                            'id_referensi' => $returHeader->id,
                            'tipe_referensi' => get_class($returHeader),
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
                        'tipe_transaksi' => $tipeTransaksiRiwayat,
                        'jumlah_masuk' => 0,
                        'jumlah_keluar' => $jumlahReturSaatIni,
                        'saldo_setelah_transaksi' => $saldoTerakhir - $jumlahReturSaatIni,
                        'id_referensi' => $returHeader->id,
                        'tipe_referensi' => get_class($returHeader),
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
                             ->with('success', "Retur Pembelian ({$returHeader->nomor_retur}) ke supplier berhasil disimpan.");

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
                'pengguna',
                'supplier',
                'detailReturPembelian', // Dibutuhkan untuk menghitung total
                // ### RELASI BARU: Cek apakah sudah ada penerimaan ###
                'penerimaanPengganti'
            ])->select('retur_pembelian.*');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('tanggal_retur_formatted', fn ($row) => Carbon::parse($row->tanggal_retur)->isoFormat('D MMM YYYY, HH:mm'))
                ->addColumn('supplier_tujuan', fn($row) => $row->supplier->nama ?? 'N/A')
                ->addColumn('total_jumlah_retur', function($row){
                    return $row->detailReturPembelian->sum('jumlah_retur') . ' unit';
                })

                // ### LOGIKA STATUS DINAMIS BARU ###
                ->addColumn('status_display', function($row) {
                    // Ambil status dari header nota retur
                    $statusHeader = $row->status; 
                    // Ambil status dari item pertama untuk tahu tindakan supplier
                    $tindakanSupplier = $row->detailReturPembelian->first()->tindakan_lanjut_supplier ?? 'PROSES';

                    if ($statusHeader === 'PROSES') {
                        return '<span class="badge bg-warning text-dark">Proses</span>';
                    }
                    
                    if ($tindakanSupplier === 'SELESAI_DIGANTI') {
                        // Jika akan diganti, cek apakah sudah ada penerimaan
                        if ($row->penerimaanPengganti->isNotEmpty()) {
                            return '<span class="badge bg-success">Selesai (Diterima)</span>';
                        } else {
                            return '<span class="badge bg-primary">Menunggu Barang</span>';
                        }
                    }
                    
                    // Untuk kasus refund atau ditolak
                    if (in_array($tindakanSupplier, ['SELESAI_DIREFUND', 'DITOLAK_SUPPLIER'])) {
                        return '<span class="badge bg-success">Selesai</span>';
                    }

                    // Fallback
                    return '<span class="badge bg-secondary">' . e($statusHeader) . '</span>';
                })
                
                ->addColumn('admin_proses', fn($row) => $row->pengguna->nama ?? '-')
                ->addColumn('action', function ($row) {
                    $btnShow = '<a href="' . route('admin.retur_pembelian.show', $row->id) . '" class="btn btn-info btn-sm me-1" title="Lihat Detail"><i class="bi bi-eye"></i></a>';
                    $btnEdit = '';
                    if ($row->status === 'PROSES') {
                        $btnEdit = '<a href="' . route('admin.retur_pembelian.edit', $row->id) . '" class="btn btn-warning btn-sm" title="Update Status"><i class="bi bi-pencil-square"></i></a>';
                    }
                    return $btnShow . $btnEdit;
                })
                ->rawColumns(['action', 'status_display'])
                ->make(true);
        }
        return view('admin.retur_pembelian.index');
    }

    /**
     * Menampilkan detail satu retur pembelian.
     */
    public function show(ReturPembelian $returPembelian)
    {
        // Memuat semua relasi yang dibutuhkan oleh view show
        $returPembelian->load([
            'pengguna',
            'supplier', // Supplier tujuan
            'detailReturPembelian.stokBarang.produk', // Detail -> batch -> produk
            'penerimaanPengganti' // ### TAMBAHKAN RELASI INI ###
        ]);
        return view('admin.retur_pembelian.show', compact('returPembelian'));
    }

    public function edit(ReturPembelian $returPembelian) // Route Model Binding
    {
        if ($returPembelian->status !== 'PROSES') {
            return redirect()->route('admin.retur_pembelian.index')->with('info', 'Retur ini sudah final dan tidak dapat diedit.');
        }

        $returPembelian->load(['supplier', 'detailReturPembelian.stokBarang.produk']);

        // Opsi status tindak lanjut final dari supplier
        $tindakanLanjutSupplierFinalOptions = [
            'SELESAI_DIGANTI' => 'SELESAI - Barang Sudah Diganti Supplier',
            'SELESAI_DIREFUND' => 'SELESAI - Sudah Direfund Supplier',
            'DITOLAK_SUPPLIER' => 'DITOLAK oleh Supplier',
        ];

        return view('admin.retur_pembelian.edit', compact('returPembelian', 'tindakanLanjutSupplierFinalOptions'));
    }

    public function update(Request $request, ReturPembelian $returPembelian)
    {
        // Validasi status final saja
        $validated = $request->validate([
            'tindakan_lanjut_supplier' => ['required', 'string', Rule::in(['SELESAI_DIGANTI', 'SELESAI_DIREFUND', 'DITOLAK_SUPPLIER'])],
            'catatan_internal_retur' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Update status di setiap detail
            foreach($returPembelian->detailReturPembelian as $detail) {
                $detail->tindakan_lanjut_supplier = $validated['tindakan_lanjut_supplier'];
                $detail->save();
            }
            
            // Update status header menjadi selesai dan tambahkan catatan
            $returPembelian->status = 'SELESAI';
            $catatanLama = $returPembelian->catatan_internal_retur ? $returPembelian->catatan_internal_retur . "\n" : "";
            $returPembelian->catatan_internal_retur = $catatanLama . "[ADMIN-UPDATE] " . ($validated['catatan_internal_retur'] ?? 'Status diperbarui.');
            $returPembelian->save();
            
            // Berikan pesan khusus jika barang akan diganti
            if ($validated['tindakan_lanjut_supplier'] === 'SELESAI_DIGANTI') {
                session()->flash('info_barang_pengganti', 
                    "Status retur #{$returPembelian->nomor_retur} telah diupdate menjadi 'Selesai Diganti'. " .
                    "JANGAN LUPA untuk mencatat penerimaan barang pengganti melalui menu 'Penerimaan Barang' saat barang fisik tiba."
                );
            }

            DB::commit();
            return redirect()->route('admin.retur_pembelian.index')->with('success', "Status untuk Retur #{$returPembelian->nomor_retur} berhasil diperbarui.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui status retur: ' . $e->getMessage())->withInput();
        }
    }
}