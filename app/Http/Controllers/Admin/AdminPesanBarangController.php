<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\StokBarang;
use App\Models\RiwayatPergerakanStok;
use App\Models\Produk;
use App\Models\DetailPenjualanStokAlokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        ]);
        $idStokBarang = $request->input('id_stok_barang');
        
        // 1. Ambil semua kandidat nomor seri yang PERNAH tercatat masuk ke batch ini.
        $candidateSerials = RiwayatPergerakanStok::where('id_stok_barang_terkait', $idStokBarang)
            ->where('jumlah_masuk', '>', 0)
            ->whereNotNull('nomor_seri')
            ->distinct()
            ->pluck('nomor_seri');

        if ($candidateSerials->isEmpty()) {
            return response()->json(['success' => true, 'serials' => []]);
        }

        // 2. Dari semua kandidat, cari ID record pergerakan TERAKHIR untuk setiap serial.
        $latestMovementIds = RiwayatPergerakanStok::select(DB::raw('MAX(id) as id'))
            ->whereIn('nomor_seri', $candidateSerials)
            ->groupBy('nomor_seri')
            ->pluck('id');

        // 3. Ambil semua serial dari pergerakan terakhir yang:
        //    a. Merupakan transaksi MASUK (artinya stok ada).
        //    b. Benar-benar milik BATCH INI.
        $availableSerials = RiwayatPergerakanStok::whereIn('id', $latestMovementIds)
            ->where('jumlah_masuk', '>', 0) 
            ->where('id_stok_barang_terkait', $idStokBarang)
            ->pluck('nomor_seri')
            ->values()
            ->all();
        
        // 4. Ambil nomor seri dari batch ini yang sudah di-pra-alokasikan ke pesanan LAIN
        $bookedSerials = DetailPenjualanStokAlokasi::where('id_stok_barang', $idStokBarang)
            ->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
            ->whereNotNull('nomor_seri_terkait')
            ->whereHas('detailPenjualan.penjualan', function ($queryPenjualan) {
                $queryPenjualan->whereIn('status_penjualan', ['MENUNGGU_BARANG', 'MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL']);
            })
            ->pluck('nomor_seri_terkait')
            ->flatMap(fn($s) => explode(',', $s))
            ->map(fn($s) => trim($s))
            ->filter()->unique()->values();

        // 5. Kurangi serial yang tersedia dengan yang sudah di-booking
        $finalAvailableSerials = array_values(array_diff($availableSerials, $bookedSerials->all()));

        return response()->json([
            'success' => true,
            'serials' => $finalAvailableSerials
        ]);
    }


    protected function prepareAlokasiItems(Request $request)
    {
        $input = $request->all();
        if (isset($input['alokasi_items']) && is_array($input['alokasi_items'])) {
            foreach ($input['alokasi_items'] as $key => $itemAlokasi) {
                if (isset($itemAlokasi['alokasi_batch']) && is_string($itemAlokasi['alokasi_batch'])) {
                    $jsonString = $itemAlokasi['alokasi_batch'];
                    

                    $decoded = json_decode($jsonString, true);
                    $jsonError = json_last_error();

                    

                    if ($jsonError === JSON_ERROR_NONE && is_array($decoded)) {
                        $input['alokasi_items'][$key]['alokasi_batch'] = $decoded;
                       
                    } else {
                        $input['alokasi_items'][$key]['alokasi_batch'] = []; // Tetap jadi array kosong jika gagal
                        
                    }
                } elseif (!isset($itemAlokasi['alokasi_batch'])) {
                    $input['alokasi_items'][$key]['alokasi_batch'] = [];
                   
                } elseif (!is_array($itemAlokasi['alokasi_batch'])) {
                    $input['alokasi_items'][$key]['alokasi_batch'] = [];
                   
                }
            }
            $request->replace($input);
           
        }
    }


    /**
     * Menyimpan alokasi stok untuk Pesan Barang.
     */
    public function storeAlokasi(Request $request, Penjualan $penjualan)
{

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


        foreach ($penjualan->detailPenjualan as $detailPesan) { // Loop berdasarkan item di pesanan
           
            $totalQtyDipesanUntukItemIni = $detailPesan->jumlah;
            $totalQtyBaruDialokasikanUntukItemIni = 0;
            $produkItem = $detailPesan->produk; // Produk dari item pesanan

            // Cari data alokasi untuk detailPesan ini dari input yang sudah divalidasi
            $dataAlokasiUntukDetailIni = null;
            foreach ($validated['alokasi_items'] as $itemAlokasiInput) {
                if ($itemAlokasiInput['id_detail_penjualan'] == $detailPesan->id) {
                    $dataAlokasiUntukDetailIni = $itemAlokasiInput;
                   
                    break;
                }
            }

            // Jika tidak ada alokasi_batch yang dikirim untuk item ini, tetapi item ini ada di pesanan
            if (!$dataAlokasiUntukDetailIni) {
                
                if ($totalQtyDipesanUntukItemIni > 0) {
                    $semuaItemTargetTeralokasiPenuh = false;
                    
                }
                continue;
            }

            if (empty($dataAlokasiUntukDetailIni['alokasi_batch'])) {
                if ($totalQtyDipesanUntukItemIni > 0) {
                    $semuaItemTargetTeralokasiPenuh = false;
                }
                continue;
            }

          


            foreach ($dataAlokasiUntukDetailIni['alokasi_batch'] as $batchAlokasi) {
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
                        // Cek status terakhir serial ini
                            $lastMovement = RiwayatPergerakanStok::where('nomor_seri', $trimmedSn)->latest('id')->first();

                            // Serial tidak valid jika:
                            // 1. Tidak pernah ada di riwayat
                            // 2. Pergerakan terakhirnya adalah KELUAR
                            // 3. Pergerakan terakhirnya adalah MASUK, tapi bukan di batch yang sedang dipilih
                            if (!$lastMovement || $lastMovement->jumlah_masuk == 0 || $lastMovement->id_stok_barang_terkait != $stokBarang->id) {
                                throw new \Exception("Nomor Seri '{$trimmedSn}' tidak tersedia atau bukan milik Batch ID {$stokBarang->id}.");
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
               
                DetailPenjualanStokAlokasi::create([
                    'id_detail_penjualan' => $detailPesan->id,
                    'id_stok_barang' => $stokBarang->id,
                    'jumlah_diambil' => $qtyDialokasikanDariBatchIni,
                    'nomor_seri_terkait' => $produkItem->memiliki_serial && !empty($serialsTerpilihUntukBatchIni) ? implode(',', $serialsTerpilihUntukBatchIni) : null,
                    'tipe_alokasi' => 'DIALOKASIKAN_PESANAN',
                    'dialokasikan_oleh' => Auth::id(),
                    'dialokasikan_at' => now(),
                ]);
                
            } // end foreach alokasi_batch untuk satu item detail

            
            if ($totalQtyBaruDialokasikanUntukItemIni < $totalQtyDipesanUntukItemIni) {
                $semuaItemTargetTeralokasiPenuh = false;
               
            } else {
              
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
        
        $penjualan->save();

        DB::commit();
        return redirect()->route('admin.pesan_barang_alokasi.index')
                         ->with('success', "Alokasi stok untuk pesanan {$penjualan->nomor_penjualan} berhasil disimpan. Status pesanan kini: {$penjualan->status_penjualan}.");

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        
        return redirect()->back()->withErrors($e->errors())->withInput();
    } catch (\Exception $e) {
        DB::rollBack();
       
        return redirect()->back()->with('error', 'Gagal menyimpan alokasi: ' . $e->getMessage())->withInput();
    }
}
}
