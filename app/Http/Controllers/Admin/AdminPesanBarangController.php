<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\StokBarang;
use App\Models\LogNomorSeri;
use App\Models\Produk;
use App\Models\DetailPenjualanStokAlokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class AdminPesanBarangController extends Controller
{
    /**
     * Menampilkan daftar Pesan Barang yang menunggu alokasi.
     */
    public function index()
    {
        $pesananMenungguAlokasi = Penjualan::where('tipe_transaksi', 'PESAN_BARANG')
            ->where('status_penjualan', 'MENUNGGU_BARANG')
            ->with(['pelanggan', 'pengguna', 'detailPenjualan' => function ($query) {
                $query->with('produk');
            }])
            ->orderBy('tanggal_penjualan', 'asc')
            ->paginate(15);

        return view('admin.pesan_barang_alokasi.index', compact('pesananMenungguAlokasi'));
    }

    /**
     * Menampilkan form untuk mengalokasikan stok ke satu Pesan Barang.
     */
    public function showAlokasiForm(Penjualan $penjualan) // Menggunakan Route Model Binding
    {
        if ($penjualan->tipe_transaksi !== 'PESAN_BARANG' || $penjualan->status_penjualan !== 'MENUNGGU_BARANG') {
            return redirect()->route('admin.pesan_barang_alokasi.index')
                             ->with('error', 'Status pesanan tidak valid atau sudah dialokasikan sepenuhnya.');
        }

        // Eager load relasi yang dibutuhkan
        $penjualan->load([
            'pelanggan',
            'pengguna',
            'detailPenjualan' => function ($query) {
                $query->with(['produk', 'stokAlokasi' => function ($qAlokasi) {
                    // Hanya ambil alokasi yang masih relevan (misal, yang statusnya DIALOKASIKAN_PESANAN)
                    $qAlokasi->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN');
                }]);
            }
        ]);

        // Hitung jumlah yang sudah dialokasikan untuk setiap item detail
        foreach ($penjualan->detailPenjualan as $detail) {
            $detail->jumlah_sudah_dialokasikan = $detail->stokAlokasi // Ini adalah collection dari DetailPenjualanStokAlokasi
                                                       ->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
                                                       ->sum('jumlah_diambil');
            $detail->jumlah_kurang_dialokasikan = $detail->jumlah - $detail->jumlah_sudah_dialokasikan;
        }

        return view('admin.pesan_barang_alokasi.form', compact('penjualan'));
    }

    /**
     * AJAX endpoint untuk mendapatkan batch yang tersedia untuk Admin.
     */
    public function getAdminAvailableBatchesAjax(Request $request)
    {
        $request->validate([
            'id_produk' => 'required|integer|exists:produk,id',
            'id_penjualan_current' => 'sometimes|integer|exists:penjualan,id', // Opsional, untuk skip alokasi pesanan ini sendiri
        ]);
        $idProduk = $request->input('id_produk');
        $idPenjualanCurrentlyAllocating = $request->input('id_penjualan_current', null);

        $produk = Produk::find($idProduk);
        if (!$produk) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        $batches = StokBarang::where('id_produk', $idProduk)
            ->where('kondisi', 'BAIK')
            ->where('jumlah', '>', 0) // Pastikan stok fisik masih ada
            // ->whereIn('lokasi', ['GUDANG', 'TOKO']) // Sesuaikan dengan lokasi yang bisa diakses Admin untuk alokasi
            ->orderBy('diterima_at', 'asc') // FIFO
            ->get()
            ->map(function ($batch) use ($idPenjualanCurrentlyAllocating) {
                // Hitung total kuantitas dari batch ini yang sudah di-pra-alokasikan
                // ke pesanan LAIN yang masih aktif (statusnya DIALOKASIKAN_PESANAN)
                $qtySudahPraAlokasiKeLain = DetailPenjualanStokAlokasi::where('id_stok_barang', $batch->id)
                    ->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
                    ->whereHas('detailPenjualan.penjualan', function ($q) use ($idPenjualanCurrentlyAllocating) {
                        if ($idPenjualanCurrentlyAllocating) {
                            $q->where('id', '!=', $idPenjualanCurrentlyAllocating);
                        }
                        $q->whereIn('status_penjualan', ['MENUNGGU_BARANG', 'MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL']);
                    })
                    ->sum('jumlah_diambil');

                $stokEfektifTersedia = $batch->jumlah - $qtySudahPraAlokasiKeLain;

                if ($stokEfektifTersedia > 0) {
                    return [
                        'id' => $batch->id,
                        'jumlah_tersedia' => $stokEfektifTersedia,
                        'jumlah_fisik_batch' => $batch->jumlah,
                        'diterima_at_formatted' => Carbon::parse($batch->diterima_at)->isoFormat('D MMM YYYY, HH:mm'),
                        'harga_beli_formatted' => 'Rp ' . number_format($batch->harga_beli, 0, ',', '.'),
                        'lokasi' => $batch->lokasi,
                        'tipe_garansi_batch' => $batch->tipe_garansi,
                        'tipe_garansi_display' => ucwords(str_replace('_', ' ', $batch->tipe_garansi)),
                        'tipe_stok_display' => ucwords(str_replace('_', ' ', $batch->tipe_stok)),
                    ];
                }
                return null;
            })->filter()->values(); // Hapus null dan re-index

        return response()->json([
            'success' => true,
            'memiliki_serial' => (bool) $produk->memiliki_serial,
            'batches_data' => $batches,
        ]);
    }

    /**
     * AJAX endpoint untuk mendapatkan serial yang tersedia untuk Admin.
     */
    public function getAdminAvailableSerialsAjax(Request $request)
    {
        $request->validate([
            'id_stok_barang' => 'required|integer|exists:stok_barang,id',
            'id_penjualan_current' => 'sometimes|nullable|integer|exists:penjualan,id', // Dibuat nullable
        ]);
        $idStokBarang = $request->input('id_stok_barang');
        $idPenjualanCurrentlyAllocating = $request->input('id_penjualan_current', null);

        // 1. Ambil semua nomor seri yang statusnya DITERIMA dari batch ini
        $allSerialsInBatch = LogNomorSeri::where('id_stok_barang_asal', $idStokBarang)
                            ->where('status_log', 'DITERIMA')
                            ->pluck('nomor_seri')
                            ->toArray();

        if (empty($allSerialsInBatch)) {
            return response()->json(['success' => true, 'serials' => []]);
        }

        // 2. Ambil nomor seri dari batch ini yang sudah di-pra-alokasikan (DIALOKASIKAN_PESANAN)
        //    ke pesanan LAIN yang masih aktif
        $queryPraAlokasiKeLain = DetailPenjualanStokAlokasi::where('id_stok_barang', $idStokBarang)
            ->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
            ->whereNotNull('nomor_seri_terkait')
            ->whereHas('detailPenjualan.penjualan', function ($queryPenjualan) use ($idPenjualanCurrentlyAllocating) {
                $queryPenjualan->whereIn('status_penjualan', ['MENUNGGU_BARANG', 'MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL']);
                if ($idPenjualanCurrentlyAllocating) {
                    $queryPenjualan->where('id', '!=', $idPenjualanCurrentlyAllocating);
                }
            });

        $bookedSerialsForOtherOrders = $queryPraAlokasiKeLain
            ->pluck('nomor_seri_terkait')
            ->flatMap(function ($serialString) {
                return explode(',', $serialString);
            })
            ->map(function ($serial) {
                return trim($serial);
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $availableSerials = array_diff($allSerialsInBatch, $bookedSerialsForOtherOrders);

        return response()->json([
            'success' => true,
            'serials' => array_values($availableSerials)
        ]);
    }


    protected function prepareAlokasiItems(Request $request)
    {
        Log::info('prepareAlokasiItems - Input Awal:', $request->all());
        $input = $request->all();
        if (isset($input['alokasi_items']) && is_array($input['alokasi_items'])) {
            foreach ($input['alokasi_items'] as $key => $itemAlokasi) {
                Log::info("prepareAlokasiItems - Processing item key: {$key}", $itemAlokasi);
                if (isset($itemAlokasi['alokasi_batch']) && is_string($itemAlokasi['alokasi_batch'])) {
                    $jsonString = $itemAlokasi['alokasi_batch'];
                    Log::info("prepareAlokasiItems - JSON string untuk key {$key}: " . $jsonString);

                    $decoded = json_decode($jsonString, true);
                    $jsonError = json_last_error();

                    Log::info("prepareAlokasiItems - Hasil json_decode untuk key {$key}:", is_array($decoded) ? $decoded : ['result_type' => gettype($decoded)]);
                    Log::info("prepareAlokasiItems - json_last_error() untuk key {$key}: " . $jsonError . " (" . json_last_error_msg() . ")");

                    if ($jsonError === JSON_ERROR_NONE && is_array($decoded)) {
                        $input['alokasi_items'][$key]['alokasi_batch'] = $decoded;
                        Log::info("prepareAlokasiItems - BERHASIL decode untuk key {$key}.");
                    } else {
                        $input['alokasi_items'][$key]['alokasi_batch'] = []; // Tetap jadi array kosong jika gagal
                        Log::error("prepareAlokasiItems - GAGAL decode JSON atau bukan array untuk key {$key}. String asli: " . $jsonString . ". Error: " . json_last_error_msg());
                    }
                } elseif (!isset($itemAlokasi['alokasi_batch'])) {
                    $input['alokasi_items'][$key]['alokasi_batch'] = [];
                    Log::info("prepareAlokasiItems - Key 'alokasi_batch' TIDAK ADA untuk item key: {$key}. Di-set ke array kosong.");
                } elseif (!is_array($itemAlokasi['alokasi_batch'])) {
                    $input['alokasi_items'][$key]['alokasi_batch'] = [];
                    Log::info("prepareAlokasiItems - 'alokasi_batch' BUKAN ARRAY untuk item key: {$key}. Di-set ke array kosong. Tipe asli: " . gettype($itemAlokasi['alokasi_batch']));
                }
            }
            $request->replace($input);
            Log::info('prepareAlokasiItems - Input Setelah Transformasi:', $request->all());
        }
    }


    /**
     * Menyimpan alokasi stok untuk Pesan Barang.
     */
    public function storeAlokasi(Request $request, Penjualan $penjualan)
{
    Log::info("RAW Request Data for Alokasi Penjualan ID: {$penjualan->id}", $request->all()); // Log data mentah
    $this->prepareAlokasiItems($request); // Panggil transformasi di awal

    $validated = $request->validate([
        'alokasi_items' => 'required|array',
        // Pastikan id_detail_penjualan ini benar-benar milik $penjualan yang di-pass
        'alokasi_items.*.id_detail_penjualan' => [
            'required',
            'integer',
            Rule::exists('detail_penjualan', 'id')->where(function ($query) use ($penjualan) {
                $query->where('id_penjualan', $penjualan->id);
            }),
        ],
        'alokasi_items.*.alokasi_batch' => 'present|array', // 'present' berarti field harus ada, 'array' berarti boleh array kosong
        'alokasi_items.*.alokasi_batch.*.id_stok_barang' => 'required|integer|exists:stok_barang,id',
        'alokasi_items.*.alokasi_batch.*.qty_dialokasikan' => 'required|integer|min:1',
        'alokasi_items.*.alokasi_batch.*.serials_selected' => 'present|array', // Boleh array kosong jika produk non-serial
    ],[
        'alokasi_items.*.id_detail_penjualan.exists' => 'Detail penjualan yang dipilih tidak valid untuk pesanan ini.',
        'alokasi_items.*.alokasi_batch.*.id_stok_barang.required' => 'ID Batch Stok wajib diisi untuk setiap alokasi.',
        'alokasi_items.*.alokasi_batch.*.qty_dialokasikan.required' => 'Kuantitas alokasi wajib diisi.',
        'alokasi_items.*.alokasi_batch.*.qty_dialokasikan.min' => 'Kuantitas alokasi minimal 1.',
    ]);

    Log::info("Data Alokasi Admin yang Divalidasi untuk Penjualan ID: {$penjualan->id}", $validated);

    if ($penjualan->tipe_transaksi !== 'PESAN_BARANG' || !in_array($penjualan->status_penjualan, ['MENUNGGU_BARANG', 'MENUNGGU_PELUNASAN'])) {
        // Jika status sudah SIAP_DIAMBIL atau SELESAI, mungkin tidak boleh dialokasi ulang tanpa proses lain
        return redirect()->route('admin.pesan_barang_alokasi.index')
                         ->with('error', 'Tidak dapat mengalokasikan stok. Status pesanan saat ini adalah: ' . $penjualan->status_penjualan);
    }

    DB::beginTransaction();
    try {
        // Hapus semua pra-alokasi LAMA (status DIALOKASIKAN_PESANAN) untuk detail penjualan dalam pesanan ini.
        // Ini menyederhanakan logika: setiap kali admin submit form ini, dianggap sebagai set alokasi yang baru & final untuk pesanan tsb.
        $idDetailPenjualanInThisOrder = $penjualan->detailPenjualan()->pluck('id');
        DetailPenjualanStokAlokasi::whereIn('id_detail_penjualan', $idDetailPenjualanInThisOrder)
                                    ->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
                                    ->delete();

        $semuaItemTargetTeralokasiPenuh = true; // Flag untuk mengecek apakah semua item di pesanan sudah dipenuhi alokasinya
        Log::info("Memulai loop detail pesanan. Jumlah detail: " . $penjualan->detailPenjualan->count());

        foreach ($penjualan->detailPenjualan as $detailPesan) { // Loop berdasarkan item di pesanan
            Log::info("Processing DetailPesan ID: {$detailPesan->id}, Produk ID: {$detailPesan->produk->id}, Jml Dipesan: {$detailPesan->jumlah}");
            $totalQtyDipesanUntukItemIni = $detailPesan->jumlah;
            $totalQtyBaruDialokasikanUntukItemIni = 0;
            $produkItem = $detailPesan->produk; // Produk dari item pesanan

            // Cari data alokasi untuk detailPesan ini dari input yang sudah divalidasi
            $dataAlokasiUntukDetailIni = null;
            foreach ($validated['alokasi_items'] as $itemAlokasiInput) {
                if ($itemAlokasiInput['id_detail_penjualan'] == $detailPesan->id) {
                    $dataAlokasiUntukDetailIni = $itemAlokasiInput;
                    Log::info("Data alokasi ditemukan untuk DetailPesan ID {$detailPesan->id}", $dataAlokasiUntukDetailIni);
                    break;
                }
            }

            // Jika tidak ada alokasi_batch yang dikirim untuk item ini, tetapi item ini ada di pesanan
            if (!$dataAlokasiUntukDetailIni) {
                Log::warning("Tidak ada data alokasi dari input untuk DetailPesan ID {$detailPesan->id}.");
                if ($totalQtyDipesanUntukItemIni > 0) {
                    $semuaItemTargetTeralokasiPenuh = false;
                    Log::info("Item DetailPesan ID {$detailPesan->id} belum teralokasi penuh (tidak ada data alokasi).");
                }
                continue;
            }

            if (empty($dataAlokasiUntukDetailIni['alokasi_batch'])) {
                Log::warning("Array 'alokasi_batch' kosong untuk DetailPesan ID {$detailPesan->id}.");
                if ($totalQtyDipesanUntukItemIni > 0) {
                    $semuaItemTargetTeralokasiPenuh = false;
                    Log::info("Item DetailPesan ID {$detailPesan->id} belum teralokasi penuh (alokasi_batch kosong).");
                }
                continue;
            }

            Log::info("Memulai loop alokasi_batch untuk DetailPesan ID {$detailPesan->id}. Jumlah batch dialokasikan: " . count($dataAlokasiUntukDetailIni['alokasi_batch']));


            foreach ($dataAlokasiUntukDetailIni['alokasi_batch'] as $batchAlokasi) {
                Log::info("Processing batchAlokasi:", $batchAlokasi);
                $stokBarang = StokBarang::find($batchAlokasi['id_stok_barang']); // Tidak perlu lockForUpdate di sini, karena kita tidak mengurangi stok fisik

                if (!$stokBarang || $stokBarang->id_produk !== $produkItem->id) {
                    throw new \Exception("Batch stok ID {$batchAlokasi['id_stok_barang']} tidak valid atau bukan milik produk {$produkItem->nama}.");
                }

                $qtyDialokasikanDariBatchIni = (int)$batchAlokasi['qty_dialokasikan'];
                if ($qtyDialokasikanDariBatchIni <= 0) continue; // Lewati jika qty 0 atau negatif

                $totalQtyBaruDialokasikanUntukItemIni += $qtyDialokasikanDariBatchIni;

                // Validasi ketersediaan stok efektif (stok fisik - yg sudah DIALOKASIKAN_PESANAN ke pesanan LAIN)
                $qtySudahPraAlokasiKeLain = DetailPenjualanStokAlokasi::where('id_stok_barang', $stokBarang->id)
                    ->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
                    ->whereHas('detailPenjualan.penjualan', function ($q) use ($penjualan) {
                        $q->where('id', '!=', $penjualan->id); // Kecualikan pesanan ini sendiri
                        $q->whereIn('status_penjualan', ['MENUNGGU_BARANG', 'MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL']);
                    })
                    ->sum('jumlah_diambil');
                $stokEfektifBatch = $stokBarang->jumlah - $qtySudahPraAlokasiKeLain;

                if ($qtyDialokasikanDariBatchIni > $stokEfektifBatch) {
                    throw new \Exception("Stok efektif di Batch ID {$stokBarang->id} (Tersedia: {$stokEfektifBatch}) tidak mencukupi untuk alokasi {$qtyDialokasikanDariBatchIni} unit produk {$produkItem->nama}. Mungkin sudah dialokasikan oleh admin lain saat Anda memproses.");
                }

                $serialsTerpilihUntukBatchIni = $batchAlokasi['serials_selected'] ?? [];
                if ($produkItem->memiliki_serial) {
                    if (count($serialsTerpilihUntukBatchIni) !== $qtyDialokasikanDariBatchIni) {
                        throw new \Exception("Jumlah nomor seri (".count($serialsTerpilihUntukBatchIni).") tidak sesuai dengan kuantitas ({$qtyDialokasikanDariBatchIni}) yang dialokasikan dari Batch ID {$stokBarang->id}, produk {$produkItem->nama}.");
                    }

                    // Validasi Ketersediaan Setiap Serial yang Dipilih (PENTING)
                    foreach($serialsTerpilihUntukBatchIni as $sn) {
                        $trimmedSn = trim($sn);
                        $logSn = LogNomorSeri::where('id_stok_barang_asal', $stokBarang->id)
                                            ->where('nomor_seri', $trimmedSn)
                                            ->where('status_log', 'DITERIMA')
                                            ->first();
                        if (!$logSn) { // Jika tidak ditemukan atau statusnya bukan DITERIMA
                            throw new \Exception("Nomor Seri '{$trimmedSn}' tidak valid/ditemukan sebagai 'DITERIMA' dari Batch ID {$stokBarang->id}.");
                        }

                        // Cek apakah serial ini sudah di-pra-alokasikan ke pesanan LAIN
                        $isBookedByOtherOrder = DetailPenjualanStokAlokasi::where('id_stok_barang', $stokBarang->id)
                            ->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
                            ->whereRaw("FIND_IN_SET(?, nomor_seri_terkait) > 0", [$trimmedSn]) // Cek di dalam string comma-separated
                            ->whereHas('detailPenjualan.penjualan', function ($q) use ($penjualan) {
                                $q->where('id', '!=', $penjualan->id);
                                $q->whereIn('status_penjualan', ['MENUNGGU_BARANG', 'MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL']);
                            })
                            ->exists();

                        if ($isBookedByOtherOrder) {
                            throw new \Exception("Nomor Seri '{$trimmedSn}' dari Batch ID {$stokBarang->id} sudah dialokasikan ke pesanan lain yang aktif.");
                        }
                    }
                }
                Log::info("Akan membuat DetailPenjualanStokAlokasi untuk DetailPesan ID {$detailPesan->id} dari Batch ID {$stokBarang->id} Qty {$qtyDialokasikanDariBatchIni}");
                DetailPenjualanStokAlokasi::create([
                    'id_detail_penjualan' => $detailPesan->id,
                    'id_stok_barang' => $stokBarang->id,
                    'jumlah_diambil' => $qtyDialokasikanDariBatchIni,
                    'nomor_seri_terkait' => $produkItem->memiliki_serial && !empty($serialsTerpilihUntukBatchIni) ? implode(',', $serialsTerpilihUntukBatchIni) : null,
                    'tipe_alokasi' => 'DIALOKASIKAN_PESANAN',
                    'dialokasikan_oleh' => Auth::id(),
                    'dialokasikan_at' => now(),
                ]);
                Log::info("BERHASIL membuat DetailPenjualanStokAlokasi.");
            } // end foreach alokasi_batch untuk satu item detail

            Log::info("Selesai loop alokasi_batch untuk DetailPesan ID {$detailPesan->id}. Total baru dialokasikan: {$totalQtyBaruDialokasikanUntukItemIni}");
            if ($totalQtyBaruDialokasikanUntukItemIni < $totalQtyDipesanUntukItemIni) {
                $semuaItemTargetTeralokasiPenuh = false;
                Log::info("Item DetailPesan ID {$detailPesan->id} TIDAK teralokasi penuh.");
            } else {
                Log::info("Item DetailPesan ID {$detailPesan->id} SUDAH teralokasi penuh.");
            }
        } // end foreach detail_penjualan dari pesanan

        // Update status penjualan utama
        if ($semuaItemTargetTeralokasiPenuh) {
            // Cek apakah DP sudah lunas penuh
            if ($penjualan->uang_muka >= $penjualan->total_harga) {
                $penjualan->status_penjualan = 'SIAP_DIAMBIL';
                // Jika lunas saat DP, status pembayaran sudah LUNAS dari awal
                if ($penjualan->status_pembayaran !== 'LUNAS') { // Double check
                    $penjualan->status_pembayaran = 'LUNAS';
                    $penjualan->dibayar_at = $penjualan->dibayar_at ?? $penjualan->tanggal_penjualan; // Atau now() jika ingin update
                }
            } else {
                $penjualan->status_penjualan = 'MENUNGGU_PELUNASAN';
            }
        } else {
            // Jika tidak semua item teralokasi penuh, status penjualan kembali/tetap 'MENUNGGU_BARANG'
            // Ini menandakan Admin perlu melengkapi alokasi
            $penjualan->status_penjualan = 'MENUNGGU_BARANG';
            // Beri pesan bahwa alokasi belum penuh jika redirect
            session()->flash('warning', "Alokasi stok untuk pesanan {$penjualan->nomor_penjualan} belum lengkap. Beberapa item belum terpenuhi.");
        }
        Log::info("Final flag semuaItemTargetTeralokasiPenuh: " . ($semuaItemTargetTeralokasiPenuh ? 'true' : 'false'));
        $penjualan->save();

        DB::commit();
        return redirect()->route('admin.pesan_barang_alokasi.index')
                         ->with('success', "Alokasi stok untuk pesanan {$penjualan->nomor_penjualan} berhasil disimpan. Status pesanan kini: {$penjualan->status_penjualan}.");

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        Log::error("Validation Error storeAlokasi Admin untuk Penjualan ID {$penjualan->id}: ", $e->errors());
        return redirect()->back()->withErrors($e->errors())->withInput();
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error storeAlokasi Admin untuk Penjualan ID {$penjualan->id}: " . $e->getMessage() . " - Line: " . $e->getLine() . " - File: " . $e->getFile());
        return redirect()->back()->with('error', 'Gagal menyimpan alokasi: ' . $e->getMessage())->withInput();
    }
}
}
