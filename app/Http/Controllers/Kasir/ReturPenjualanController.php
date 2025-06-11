<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\ReturPenjualan;
use App\Models\Produk;
use App\Models\LogNomorSeri;
use App\Models\StokBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables; // Pastikan ini di-import jika index menggunakan DataTables

class ReturPenjualanController extends Controller
{
    /**
     * Menampilkan form untuk mencari transaksi penjualan yang akan diretur.
     */
    public function showCariTransaksiForm()
    {
        return view('kasir.retur_penjualan.cari_transaksi');
    }

    /**
     * AJAX untuk mengambil detail transaksi penjualan yang bisa diretur.
     */
    public function getTransaksiDetailAjax(Request $request)
    {
        $request->validate(['nomor_penjualan' => 'required|string']);
        $nomorPenjualan = trim($request->nomor_penjualan);

        Log::channel('returlog')->info("============================================================"); // Pemisah log per request
        Log::channel('returlog')->info("AJAX getTransaksiDetailAjax - Mencari penjualan dengan nomor: {$nomorPenjualan}");

        $penjualan = Penjualan::where('nomor_penjualan', $nomorPenjualan)
            ->with([
                'pelanggan', // Eager load pelanggan
                'detailPenjualan' => function ($query) {
                    // Eager load produk dan SEMUA retur yang pernah ada untuk setiap detail penjualan
                    $query->with(['produk', 'returPenjualan']);
                }
            ])
            ->first();

        if (!$penjualan) {
            Log::channel('returlog')->warning("AJAX getTransaksiDetailAjax - Penjualan tidak ditemukan untuk nomor: {$nomorPenjualan}");
            return response()->json(['success' => false, 'message' => 'Transaksi penjualan tidak ditemukan.'], 404);
        }

        Log::channel('returlog')->info("AJAX getTransaksiDetailAjax - Penjualan DITEMUKAN: ID={$penjualan->id}, No={$penjualan->nomor_penjualan}, Pelanggan=" . ($penjualan->pelanggan->nama ?? 'Umum'));
        Log::channel('returlog')->info("AJAX getTransaksiDetailAjax - Status Penjualan: {$penjualan->status_penjualan}, Tanggal Penjualan: {$penjualan->tanggal_penjualan}");
        Log::channel('returlog')->info("AJAX getTransaksiDetailAjax - Jumlah Detail Penjualan Awal (sebelum map): " . $penjualan->detailPenjualan->count());

        // Pastikan $penjualan->detailPenjualan tidak null sebelum di-count atau di-map
        if (is_null($penjualan->detailPenjualan)) {
            Log::channel('returlog')->error("AJAX getTransaksiDetailAjax - Relasi detailPenjualan adalah NULL untuk Penjualan ID: {$penjualan->id}. Periksa definisi relasi di model Penjualan.");
            return response()->json(['success' => false, 'message' => 'Gagal memuat detail item penjualan.'], 500);
        }


        if ($penjualan->status_penjualan !== 'SELESAI') {
            Log::channel('returlog')->info("AJAX getTransaksiDetailAjax - Transaksi {$nomorPenjualan} belum SELESAI (Status: {$penjualan->status_penjualan}). Tidak bisa diretur.");
            return response()->json(['success' => false, 'message' => 'Transaksi ini belum berstatus SELESAI dan tidak dapat diretur.'], 422);
        }

        $batasHariRetur = config('app.batas_hari_retur_penjualan', 7);
        $tanggalPenjualanCarbon = Carbon::parse($penjualan->tanggal_penjualan); // Parse sekali saja
        if ($tanggalPenjualanCarbon->lt(Carbon::now()->subDays($batasHariRetur))) {
            Log::channel('returlog')->info("AJAX getTransaksiDetailAjax - Transaksi {$nomorPenjualan} melewati batas retur. Tgl Jual: {$tanggalPenjualanCarbon->isoFormat('D MMM YYYY')}, Batas: {$batasHariRetur} hari.");
            return response()->json(['success' => false, 'message' => "Transaksi ini dibuat pada tanggal " . $tanggalPenjualanCarbon->isoFormat('D MMM YYYY') . " dan sudah melewati batas waktu retur ({$batasHariRetur} hari)."], 422);
        }

        $detailItemsProcessedLog = []; // Array untuk logging data item sebelum filter

        $detailItemsReturInfo = $penjualan->detailPenjualan->map(function ($detail) use (&$detailItemsProcessedLog) {
            $produkInfoNama = 'N/A';
            $produkInfoKode = 'N/A';
            $produkMemilikiSerial = false;

            if ($detail->produk) { // Pastikan objek produk ada
                $produkInfoNama = $detail->produk->nama;
                $produkInfoKode = $detail->produk->kode_produk;
                $produkMemilikiSerial = (bool) $detail->produk->memiliki_serial;
            } else {
                Log::channel('returlog')->warning("   MAP - Produk tidak ter-load untuk DetailPenjualan ID: {$detail->id}");
            }

            Log::channel('returlog')->info("   MAP - Memproses DetailPenjualan ID: {$detail->id}, Produk: {$produkInfoNama}, Jumlah Beli Awal: {$detail->jumlah}");
            Log::channel('returlog')->info("   MAP - Serial Terjual Mentah (dari detail_penjualan.nomor_seri_terjual): '{$detail->nomor_seri_terjual}'");

            $totalSudahDireturUntukItemIni = 0;
            if ($detail->relationLoaded('returPenjualan') && $detail->returPenjualan !== null) { // Cek jika relasi dimuat dan tidak null
                $totalSudahDireturUntukItemIni = $detail->returPenjualan->sum('jumlah_retur');
                Log::channel('returlog')->info("   MAP - Relasi returPenjualan ter-load. Jumlah record retur: " . $detail->returPenjualan->count() . ". Total qty diretur: {$totalSudahDireturUntukItemIni}");
            } else {
                Log::channel('returlog')->warning("   MAP - Relasi returPenjualan TIDAK ter-load atau NULL untuk DetailPenjualan ID: {$detail->id}. Asumsi total diretur 0.");
            }

            $sisaBisaDiretur = $detail->jumlah - $totalSudahDireturUntukItemIni;
            Log::channel('returlog')->info("   MAP - Sisa Qty Bisa Diretur untuk Detail ID {$detail->id}: {$sisaBisaDiretur}");

            $serialsTerjualAsliArray = !empty($detail->nomor_seri_terjual) ? array_map('trim', explode(',', $detail->nomor_seri_terjual)) : [];
            Log::channel('returlog')->info("   MAP - Serial Terjual (Array setelah explode & trim) untuk Detail ID {$detail->id}:", $serialsTerjualAsliArray);

            $serialsYangSudahDireturUntukItemIni = collect();
            if ($detail->relationLoaded('returPenjualan') && $detail->returPenjualan !== null) {
                $serialsYangSudahDireturUntukItemIni = $detail->returPenjualan
                    ->whereNotNull('nomor_seri_diretur') // Hanya ambil yang ada nomor seri direturnya
                    ->pluck('nomor_seri_diretur')
                    ->flatMap(function($serialStringDalamSatuRecordRetur) {
                        return array_map('trim', explode(',', $serialStringDalamSatuRecordRetur));
                    })
                    ->filter() // Hapus string kosong jika ada hasil explode
                    ->unique()
                    ->values(); // Hasilnya adalah Laravel Collection
            }
            Log::channel('returlog')->info("   MAP - Serial Yang Sudah Pernah Diretur (dari semua retur untuk item ini) untuk Detail ID {$detail->id}:", $serialsYangSudahDireturUntukItemIni->all());

            $serialsYangMasihBisaDiretur = array_values(array_diff($serialsTerjualAsliArray, $serialsYangSudahDireturUntukItemIni->all()));
            Log::channel('returlog')->info("   MAP - Serial Yang MASIH Bisa Diretur untuk Detail ID {$detail->id}:", $serialsYangMasihBisaDiretur);

            $itemData = [
                'id_detail_penjualan' => $detail->id,
                'id_produk' => $detail->id_produk,
                'nama_produk' => $produkInfoNama . ($produkInfoKode !== 'N/A' ? ' (' . $produkInfoKode . ')' : ''),
                'jumlah_beli_awal' => $detail->jumlah,
                'harga_jual_item' => $detail->harga_jual,
                'nomor_seri_terjual_item' => $serialsTerjualAsliArray,
                'serials_yang_masih_bisa_diretur' => $serialsYangMasihBisaDiretur,
                'sisa_qty_bisa_diretur_item' => $sisaBisaDiretur,
                'produk_memiliki_serial' => $produkMemilikiSerial,
            ];
            $detailItemsProcessedLog[] = $itemData; // Tambahkan ke log sebelum filter
            return $itemData;

        })->filter(function($detailInfo) {
            $lolosFilter = $detailInfo['sisa_qty_bisa_diretur_item'] > 0;
            Log::channel('returlog')->info("   FILTER - Item: {$detailInfo['nama_produk']}, Sisa Bisa Diretur: {$detailInfo['sisa_qty_bisa_diretur_item']}, LolosFilter=" . ($lolosFilter ? 'YA':'TIDAK'));
            return $lolosFilter;
        })->values(); // Re-index array setelah filter

        Log::channel('returlog')->info("AJAX getTransaksiDetailAjax - Data items yang diproses SEBELUM filter:", $detailItemsProcessedLog);
        Log::channel('returlog')->info("AJAX getTransaksiDetailAjax - Hasil Akhir detailItemsReturInfo (SETELAH filter):", $detailItemsReturInfo->toArray());

        if($detailItemsReturInfo->isEmpty()){
            Log::channel('returlog')->warning("AJAX getTransaksiDetailAjax - Tidak ada item yang lolos filter untuk diretur pada penjualan nomor: {$nomorPenjualan}. Pesan dikirim ke frontend.");
            return response()->json(['success' => false, 'message' => 'Semua item dari transaksi ini sudah diretur sepenuhnya atau tidak ada lagi yang bisa diretur.'], 422);
        }

        return response()->json([
            'success' => true,
            'penjualan' => [
                'id' => $penjualan->id,
                'nomor_penjualan' => $penjualan->nomor_penjualan,
                'tanggal_penjualan_formatted' => $tanggalPenjualanCarbon->isoFormat('D MMM YYYY, HH:mm'), // Gunakan yg sudah di-parse
                'pelanggan_nama' => $penjualan->pelanggan->nama ?? 'Umum',
            ],
            'detail_items_info' => $detailItemsReturInfo->toArray() // Kirim sebagai array
        ]);
    }


    /**
     * Menampilkan form untuk input detail retur.
     */
    public function showReturForm(Penjualan $penjualan)
    {
        if ($penjualan->status_penjualan !== 'SELESAI') {
            return redirect()->route('kasir.retur_penjualan.cari_transaksi')->with('error', 'Transaksi ini belum selesai dan tidak dapat diretur.');
        }
        $batasHariRetur = config('app.batas_hari_retur_penjualan', 7);
        if (Carbon::parse($penjualan->tanggal_penjualan)->lt(Carbon::now()->subDays($batasHariRetur))) {
             return redirect()->route('kasir.retur_penjualan.cari_transaksi')->with('error', "Transaksi ini sudah melewati batas waktu retur ({$batasHariRetur} hari).");
        }

        $penjualan->load(['pelanggan', 'detailPenjualan' => fn($q) => $q->with(['produk', 'returPenjualan'])]);
        $detailItemsUntukForm = [];
        foreach ($penjualan->detailPenjualan as $detail) {
            $totalSudahDiretur = $detail->returPenjualan ? $detail->returPenjualan->sum('jumlah_retur') : 0;
            $sisaBisaDiretur = $detail->jumlah - $totalSudahDiretur;
            if ($sisaBisaDiretur > 0) {
                $serialsTerjualAsliArray = !empty($detail->nomor_seri_terjual) ? array_map('trim', explode(',', $detail->nomor_seri_terjual)) : [];
                $serialsSudahDireturUntukItemIniColl = collect();
                if ($detail->returPenjualan) {
                     $serialsSudahDireturUntukItemIniColl = $detail->returPenjualan->whereNotNull('nomor_seri_diretur')->pluck('nomor_seri_diretur')->flatMap(fn($s) => array_map('trim', explode(',', $s)))->unique()->values();
                }
                $serialsYangMasihBisaDiretur = array_values(array_diff($serialsTerjualAsliArray, $serialsSudahDireturUntukItemIniColl->all()));
                $detailItemsUntukForm[] = (object)[
                    'id_detail_penjualan' => $detail->id,
                    'produk' => $detail->produk, // Kirim objek produk
                    'jumlah_beli_awal' => $detail->jumlah,
                    'sisa_qty_bisa_diretur_item' => $sisaBisaDiretur,
                    'serials_yang_masih_bisa_diretur' => $serialsYangMasihBisaDiretur,
                ];
            }
        }
        if (empty($detailItemsUntukForm)) {
            return redirect()->route('kasir.retur_penjualan.cari_transaksi')->with('info', 'Semua item dari transaksi ini sudah diretur atau tidak bisa diretur lagi.');
        }

        $alasanReturOptions = [
            'BARANG_RUSAK_PELANGGAN' => 'Barang Rusak Saat Diterima Pelanggan',
            'SALAH_BARANG_TERKIRIM' => 'Salah Kirim Barang',
            'BERUBAH_PIKIRAN' => 'Pelanggan Berubah Pikiran (Sesuai Kebijakan)',
            'TIDAK_SESUAI_SPESIFIKASI' => 'Tidak Sesuai Spesifikasi',
            'LAINNYA' => 'Lainnya',
        ];
        $tindakanLanjutOptions = [
            'DITERIMA_KEMBALI_PERLU_CEK' => 'Diterima Kembali (Perlu Pengecekan Admin)',
            'DITERIMA_LANGSUNG_RUSAK' => 'Diterima Kembali (Langsung Catat Rusak)',
            // 'KOMPLAIN_KE_SUPPLIER' => 'Disisihkan untuk Komplain ke Supplier', // Admin proses
        ];
        return view('kasir.retur_penjualan.form_retur', compact('penjualan', 'detailItemsUntukForm', 'alasanReturOptions', 'tindakanLanjutOptions'));
    }

    /**
     * Menyimpan data retur penjualan.
     */
    public function storeRetur(Request $request, Penjualan $penjualan)
    {
        Log::channel('returlog')->info("============================================================");
        Log::channel('returlog')->info("MASUK storeRetur untuk Penjualan ID: {$penjualan->id}. Request Data:", $request->all());

        $validated = $request->validate([
            'items_retur' => 'required|array|min:1',
            'items_retur.*.id_detail_penjualan' => ['required', 'integer', Rule::exists('detail_penjualan', 'id')->where('id_penjualan', $penjualan->id)],
            'items_retur.*.jumlah_retur' => 'required|integer|min:1',
            'items_retur.*.alasan_retur' => 'required|string|max:255',
            'items_retur.*.tindakan_lanjut' => 'required|string|max:100',
            'items_retur.*.nomor_seri_diretur' => 'nullable|array',
            'items_retur.*.nomor_seri_diretur.*' => 'nullable|string|max:255',
            'items_retur.*.catatan_tambahan_item' => 'nullable|string|max:500',
            'tanggal_retur' => 'required|date_format:Y-m-d\TH:i',
            'catatan_global_retur' => 'nullable|string|max:1000',
        ],[
            'items_retur.required' => 'Minimal ada satu item yang dipilih untuk diretur.',
            'items_retur.*.id_detail_penjualan.required' => 'Detail penjualan item retur tidak boleh kosong.',
            'items_retur.*.id_detail_penjualan.exists' => 'Detail penjualan item retur tidak valid untuk transaksi ini.',
            'items_retur.*.jumlah_retur.required' => 'Jumlah retur wajib diisi untuk item yang dipilih.',
            'items_retur.*.jumlah_retur.min' => 'Jumlah retur minimal 1.',
            'items_retur.*.alasan_retur.required' => 'Alasan retur wajib diisi.',
            'items_retur.*.tindakan_lanjut.required' => 'Tindakan lanjut wajib dipilih.',
            'tanggal_retur.date_format' => 'Format tanggal retur tidak sesuai.',
        ]);

        DB::beginTransaction();
    try {
        $tanggalRetur = Carbon::parse($validated['tanggal_retur']);
        $nomorReturPenjualanOtomatis = $this->generateNextReturPenjualanNumber($tanggalRetur);
        
        $adaItemYangValidDiretur = false;
        $catatanGlobalSudahDisimpan = false;

        foreach ($validated['items_retur'] as $index => $itemReturData) {
            $jumlahReturSaatIni = (int)($itemReturData['jumlah_retur'] ?? 0);
            if ($jumlahReturSaatIni <= 0) { continue; }
            $adaItemYangValidDiretur = true;

            $detailPenjualan = DetailPenjualan::with('produk', 'returPenjualan')->find($itemReturData['id_detail_penjualan']);
            
            // 1. Validasi Kuantitas
            $totalSudahDireturSebelumnya = $detailPenjualan->returPenjualan->sum('jumlah_retur');
            $sisaBisaDiretur = $detailPenjualan->jumlah - $totalSudahDireturSebelumnya;

            if ($jumlahReturSaatIni > $sisaBisaDiretur) {
                throw new \Exception("Jumlah retur ({$jumlahReturSaatIni}) untuk produk '{$detailPenjualan->produk->nama}' melebihi sisa yang bisa diretur ({$sisaBisaDiretur}).");
            }

            // 2. Validasi Nomor Seri
            $serialsDireturInputCleaned = collect($itemReturData['nomor_seri_diretur'] ?? [])->map(fn($sn) => trim($sn))->filter()->unique()->values()->all();
            
            if ($detailPenjualan->produk->memiliki_serial) {
                if (count($serialsDireturInputCleaned) !== $jumlahReturSaatIni) {
                    throw new \Exception("Jumlah nomor seri (".count($serialsDireturInputCleaned).") tidak sesuai jumlah retur ({$jumlahReturSaatIni}) untuk produk '{$detailPenjualan->produk->nama}'.");
                }
                
                // Cek apakah serial yang diretur valid
                $serialsTerjualAsli = !empty($detailPenjualan->nomor_seri_terjual) ? array_map('trim', explode(',', $detailPenjualan->nomor_seri_terjual)) : [];
                $serialsSudahDireturHistoris = $detailPenjualan->returPenjualan->whereNotNull('nomor_seri_diretur')->pluck('nomor_seri_diretur')->flatMap(fn($s) => array_map('trim', explode(',', $s)))->unique()->values()->all();
                $serialsYangBolehDiretur = array_values(array_diff($serialsTerjualAsli, $serialsSudahDireturHistoris));

                foreach ($serialsDireturInputCleaned as $snRetur) {
                    if (!in_array($snRetur, $serialsYangBolehDiretur)) {
                        throw new \Exception("Nomor Seri '{$snRetur}' tidak valid atau sudah pernah diretur untuk produk '{$detailPenjualan->produk->nama}'.");
                    }
                }
            }

            // Membuat record ReturPenjualan awal
            $dataCreateRetur = [
                'id_detail_penjualan' => $detailPenjualan->id,
                'id_pengguna' => Auth::id(),
                'nomor_retur' => $nomorReturPenjualanOtomatis,
                'jumlah_retur' => $jumlahReturSaatIni,
                'alasan_retur' => $itemReturData['alasan_retur'],
                'catatan_pelanggan' => $itemReturData['catatan_tambahan_item'] ?? null,
                'tanggal_retur' => $tanggalRetur,
                'tindakan_lanjut' => $itemReturData['tindakan_lanjut'],
                'nomor_seri_diretur' => $detailPenjualan->produk->memiliki_serial ? implode(',', $serialsDireturInputCleaned) : null,
            ];

            if (!$catatanGlobalSudahDisimpan && !empty($validated['catatan_global_retur'])) {
                $dataCreateRetur['catatan_internal_retur'] = $validated['catatan_global_retur'];
                $catatanGlobalSudahDisimpan = true;
            }

            $retur = ReturPenjualan::create($dataCreateRetur);

            // Selalu buat log baru untuk mencatat kejadian retur oleh kasir
            if ($detailPenjualan->produk->memiliki_serial && !empty($serialsDireturInputCleaned)) {
                foreach ($serialsDireturInputCleaned as $snRetur) {
                    LogNomorSeri::create([
                        'id_produk' => $detailPenjualan->id_produk,
                        'id_stok_barang_asal' => null,
                        'nomor_seri' => $snRetur,
                        'status_log' => 'DIRETUR_PELANGGAN',
                        'id_referensi' => $retur->id,
                        'tipe_referensi' => ReturPenjualan::class,
                        'tanggal_status' => $tanggalRetur,
                        'catatan' => 'Barang diretur oleh pelanggan, menunggu proses admin.',
                    ]);
                }
            }
        }

        if (!$adaItemYangValidDiretur) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Tidak ada item yang valid untuk diretur.')->withInput();
        }

        DB::commit();
        return redirect()->route('kasir.retur_penjualan.index')->with('success', "Retur awal ({$nomorReturPenjualanOtomatis}) berhasil disimpan dan menunggu proses admin.");

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal menyimpan retur: ' . $e->getMessage())->withInput();
    }
}

    /**
     * Menampilkan daftar retur penjualan yang sudah dibuat.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ReturPenjualan::with([
                            'detailPenjualan.penjualan.pelanggan',
                            'pengguna', // Kasir yang memproses retur
                            'detailPenjualan.produk' // Produk yang diretur
                        ])
                        ->select('retur_penjualan.*') // Hindari ambiguitas
                        ->orderBy('retur_penjualan.tanggal_retur', 'desc')
                        ->orderBy('retur_penjualan.id', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('nomor_penjualan_asal', function($row){
                    return $row->detailPenjualan->penjualan->nomor_penjualan ?? '-';
                })
                ->addColumn('nama_produk', function($row){
                    return $row->detailPenjualan->produk->nama ?? '-';
                })
                ->addColumn('pelanggan', function($row){
                    return $row->detailPenjualan->penjualan->pelanggan->nama ?? 'Umum';
                })
                ->editColumn('tanggal_retur_formatted', function ($row) {
                    return Carbon::parse($row->tanggal_retur)->isoFormat('D MMM YYYY, HH:mm');
                })
                ->addColumn('jumlah_retur_formatted', function ($row) {
                    return $row->jumlah_retur . ' unit';
                })
                ->addColumn('tindakan_lanjut_display', function($row){
                    // Anda bisa buat helper atau array mapping untuk display nama yang lebih baik
                    $options = [
                        'DITERIMA_KEMBALI_PERLU_CEK' => 'Perlu Dicek Admin',
                        'DITERIMA_LANGSUNG_RUSAK' => 'Dicatat Rusak',
                        'KOMPLAIN_KE_SUPPLIER' => 'Komplain Supplier',
                    ];
                    return $options[$row->tindakan_lanjut] ?? ucwords(str_replace('_', ' ', $row->tindakan_lanjut));
                })
                ->addColumn('kasir_proses', function($row){ // Mengganti pengguna.nama
                    return $row->pengguna->nama ?? '-';
                })
                ->addColumn('action', function ($row) {
                    $btnShow = '<a href="' . route('kasir.retur_penjualan.show', $row->id) . '" class="btn btn-info btn-sm me-1" title="Lihat Detail Retur"><i class="bi bi-eye"></i></a>';
                    return $btnShow;
                })
                ->rawColumns(['action', 'tindakan_lanjut_display'])
                ->make(true);
        }
        return view('kasir.retur_penjualan.index');
    }

    /**
     * Menampilkan detail satu retur penjualan.
     */
    public function show(ReturPenjualan $returPenjualan)
    {
        $returPenjualan->load([
            'detailPenjualan.penjualan.pelanggan',
            'detailPenjualan.produk',
            'pengguna'
        ]);
        return view('kasir.retur_penjualan.show', compact('returPenjualan'));
    }

    /**
     * Generate the next retur penjualan number.
     */
    private function generateNextReturPenjualanNumber(Carbon $date): string
    {
        $branchCode = config('app.branch_code', 'KGT');
        $dateFormatted = $date->format('dmy');
        $prefix = "RTRJ-{$branchCode}-{$dateFormatted}-";
        $lastToday = ReturPenjualan::whereDate('tanggal_retur', $date->toDateString())
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
}