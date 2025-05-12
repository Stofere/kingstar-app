<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenjualanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Pelanggan; 
use App\Models\Produk;  
use App\Models\StokBarang;
use App\Models\LogNomorSeri;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\DetailPenjualanStokAlokasi;


class PenjualanController extends Controller
{
    // ... (method create yang sudah ada) ...
    public function create()
    {
        $namaKasir = Auth::user()->nama;
        $tanggalSekarang = Carbon::now();

        $metodePembayaran = [
            'TUNAI' => 'Tunai', 'QRIS' => 'QRIS', 'TRANSFER_BCA' => 'Transfer BCA',
            'TRANSFER_MANDIRI' => 'Transfer Mandiri', 'DEBIT_BCA' => 'Debit BCA',
            'DEBIT_MANDIRI' => 'Debit Mandiri', 'KARTU_KREDIT' => 'Kartu Kredit',
        ];
        $kanalTransaksi = [
            'TOKO' => 'Toko Fisik', 'TOKOPEDIA' => 'Tokopedia (Manual)', 'SHOPEE' => 'Shopee (Manual)',
        ];
        $tipeTransaksi = [
            'BIASA' => 'Biasa', 'PRE_ORDER' => 'Pre-Order',
        ];

        return view('kasir.penjualan.create', compact(
            'namaKasir',
            'tanggalSekarang',
            'metodePembayaran',
            'kanalTransaksi',
            'tipeTransaksi'
        ));
    }


    /**
     * Handle AJAX request to search for customers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchPelangganAjax(Request $request)
    {
        $searchTerm = $request->input('q');
        $page = $request->input('page', 1);
        $limit = 15; // Jumlah item per halaman

        $query = Pelanggan::where('status', true) // Hanya pelanggan aktif
                          ->where(function ($q) use ($searchTerm) {
                              $q->where('nama', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('telepon', 'LIKE', "%{$searchTerm}%");
                          });

        $pelanggan = $query->orderBy('nama')
                           ->paginate($limit, ['id', 'nama', 'telepon'], 'page', $page);

        $results = $pelanggan->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->nama . ($item->telepon ? " ({$item->telepon})" : '')
            ];
        });

        return response()->json([
            'items' => $results,
            'total_count' => $pelanggan->total()
        ]);
    }

    /**
     * Handle AJAX request to search for products for sale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProdukAjax(Request $request)
    {
        $searchTerm = $request->input('q');
        $page = $request->input('page', 1);
        $limit = 15;

        $query = Produk::where('status', true) // Hanya produk aktif
                       ->where(function ($q) use ($searchTerm) {
                           $q->where('nama', 'LIKE', "%{$searchTerm}%")
                             ->orWhere('kode_produk', 'LIKE', "%{$searchTerm}%");
                       });
        
        // Jika ada parameter 'for_sale', kita bisa tambahkan filter
        // untuk produk yang memiliki stok (ini bisa jadi lebih kompleks,
        // untuk sekarang kita biarkan dulu, fokus di pencarian nama/kode)
        // if ($request->has('for_sale')) {
        //     $query->whereHas('stokBarang', function($qStok){
        //         $qStok->where('jumlah', '>', 0)->where('kondisi', 'BAIK'); // Contoh filter stok
        //     });
        // }


        $produk = $query->orderBy('nama')
                        ->paginate($limit, ['id', 'nama', 'kode_produk', 'harga_jual_standart', 'memiliki_serial'], 'page', $page);

        $results = $produk->map(function ($item) {
            // Cek ketersediaan stok sederhana (bisa dioptimalkan nanti)
            $stokTersedia = StokBarang::where('id_produk', $item->id)
                                        ->where('jumlah', '>', 0)
                                        ->where('kondisi', 'BAIK') // Hanya stok kondisi baik
                                        ->sum('jumlah');
            return [
                'id' => $item->id,
                'text' => $item->nama . ($item->kode_produk ? " ({$item->kode_produk})" : ''),
                'harga_jual_standar' => $item->harga_jual_standart,
                'memiliki_serial' => (bool) $item->memiliki_serial,
                'stok_tersedia' => $stokTersedia // Info stok bisa diambil saat pemilihan batch
            ];
        });

        return response()->json([
            'items' => $results,
            'total_count' => $produk->total()
        ]);
    }

    public function getAvailableStockAjax(Request $request)
    {
        $request->validate([
            'id_produk' => 'required|integer|exists:produk,id',
            'qty_dibutuhkan' => 'required|integer|min:1',
        ]);

        $idProduk = $request->input('id_produk');
        $qtyDibutuhkan = $request->input('qty_dibutuhkan');
        $produk = Produk::find($idProduk);

        if (!$produk) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        $batches = StokBarang::where('id_produk', $idProduk)
                            ->where('jumlah', '>', 0)
                            ->where('kondisi', 'BAIK')
                            ->orderBy('diterima_at', 'asc') // FIFO
                            ->get();

        $formattedBatches = $batches->map(function($batch) {
            return [
                'id' => $batch->id,
                'jumlah_tersedia' => $batch->jumlah,
                'diterima_at_formatted' => Carbon::parse($batch->diterima_at)->isoFormat('D MMM YYYY, HH:mm'),
                'lokasi' => $batch->lokasi,
                'tipe_garansi' => str_replace('_', ' ', $batch->tipe_garansi),
                // Tambahkan field lain jika perlu di frontend
            ];
        });

        $totalStokTersediaDiSemuaBatch = $batches->sum('jumlah');

        return response()->json([
            'success' => true,
            'memiliki_serial' => (bool) $produk->memiliki_serial,
            'batches_data' => $formattedBatches,
            'total_stok_tersedia' => $totalStokTersediaDiSemuaBatch,
            'qty_diminta' => $qtyDibutuhkan, // Kirim kembali qty yg diminta untuk referensi di JS
        ]);
    }


    /**
     * Handle AJAX request to get available serial numbers for a specific stock batch.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableSerialsAjax(Request $request)
    {
        $request->validate([
            'id_stok_barang' => 'required|integer|exists:stok_barang,id',
        ]);

        $idStokBarang = $request->input('id_stok_barang');

        // Ambil nomor seri yang statusnya 'DITERIMA' dan berasal dari batch ini,
        // dan BELUM TERJUAL atau BELUM DIRETUR_SUPPLIER (atau status lain yang membuatnya tidak tersedia)
        // Untuk kesederhanaan awal, kita hanya cek yang 'DITERIMA' dan belum ada di transaksi penjualan lain.
        

        // Kita cari log nomor seri yang id_stok_barang_asalnya adalah batch ini,
        // dan status terakhirnya adalah DITERIMA (atau status lain yang menunjukkan tersedia)
        // Ini asumsi bahwa 'DITERIMA' berarti tersedia untuk dijual dari batch tersebut.
        // Jika sbuah serial sudah terjual, statusnya akan menjadi 'TERJUAL'.

        $serials = LogNomorSeri::where('id_stok_barang_asal', $idStokBarang)
                                ->where('status_log', 'DITERIMA') // Asumsi serial 'DITERIMA' siap jual
                                // Kita mungkin perlu menambahkan kondisi untuk memastikan serial ini belum terjual
                                // dari transaksi lain yang mungkin belum selesai diproses (jika ada skenario seperti itu)
                                // Contoh: ->whereDoesntHave('penjualanDetail') // Jika ada relasi langsung
                                ->pluck('nomor_seri') // Ambil hanya kolom nomor_seri
                                ->toArray();

        if (empty($serials)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada nomor seri yang tersedia untuk batch ini atau semua sudah teralokasi.',
                'serials' => []
            ]);
        }

        return response()->json([
            'success' => true,
            'serials' => $serials
        ]);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePenjualanRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePenjualanRequest $request)
    {
        Log::info('Store Penjualan Request Data:', $request->all());
        $validated = $request->validated();
        $user = Auth::user();
        $tanggalPenjualan = Carbon::parse($validated['tanggal_penjualan']);

        DB::beginTransaction();
        try {
            // 1. Handle Pelanggan (Baru atau Lama)
            $pelangganId = $validated['id_pelanggan'] ?? null;
            if (empty($pelangganId) && !empty($validated['pelanggan_baru_nama'])) {
                $pelanggan = Pelanggan::create([
                    'nama' => $validated['pelanggan_baru_nama'],
                    'telepon' => $validated['pelanggan_baru_telepon'] ?? null,
                    'alamat' => $validated['pelanggan_baru_alamat'] ?? null,
                    'status' => true, // Default aktif
                ]);
                $pelangganId = $pelanggan->id;
            }

            // 2. Generate Nomor Invoice
            $nomorInvoice = $this->generateNextInvoiceNumber($tanggalPenjualan);

            // 3. Tentukan Status Pembayaran Awal
            $statusPembayaran = 'LUNAS'; // Default untuk transaksi biasa
            $dibayarAt = $tanggalPenjualan->copy(); // Asumsi lunas saat transaksi jika tidak PO

            if ($validated['tipe_transaksi'] === 'PRE_ORDER') {
                $totalHarga = (float)$validated['total_harga'];
                $uangMuka = (float)($validated['uang_muka'] ?? 0);

                if ($uangMuka < $totalHarga) {
                    $statusPembayaran = 'DP';
                    $dibayarAt = null; // Belum lunas penuh
                } else { // DP >= Total (dianggap lunas)
                    $statusPembayaran = 'LUNAS';
                    // dibayarAt tetap tanggal transaksi jika lunas DP
                }
            } else { // Transaksi BIASA
                // Uang bayar sudah divalidasi >= total_harga
            }


            // 4. Buat Record Penjualan Utama
            $penjualan = Penjualan::create([
                'id_pelanggan' => $pelangganId,
                'id_pengguna' => $user->id,
                'nomor_penjualan' => $nomorInvoice,
                'tanggal_penjualan' => $tanggalPenjualan,
                'total_harga' => $validated['total_harga'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'kanal_transaksi' => $validated['kanal_transaksi'],
                'tipe_transaksi' => $validated['tipe_transaksi'],
                'uang_muka' => ($validated['tipe_transaksi'] === 'PRE_ORDER') ? ($validated['uang_muka'] ?? 0) : null,
                'sisa_pembayaran' => ($validated['tipe_transaksi'] === 'PRE_ORDER') ? (max(0, (float)$validated['total_harga'] - (float)($validated['uang_muka'] ?? 0))) : null,
                'estimasi_kirim_at' => ($validated['tipe_transaksi'] === 'PRE_ORDER') ? ($validated['estimasi_kirim_at'] ?? null) : null,
                'status_pembayaran' => $statusPembayaran,
                'dibayar_at' => $dibayarAt,
                'status_penjualan' => ($validated['tipe_transaksi'] === 'PRE_ORDER' && $statusPembayaran !== 'LUNAS') ? 'MENUNGGU_BARANG' : 'SELESAI', // Atau 'PROSES' jika ada alur pengiriman
                'catatan' => $validated['catatan'] ?? null,
                'status' => true, // Default aktif
            ]);

            // 5. Proses Detail Penjualan dan Stok
            foreach ($validated['items'] as $itemData) {
                $produk = Produk::find($itemData['id_produk']);
                if (!$produk) continue; // Seharusnya tidak terjadi karena validasi

                $stokAllocations = json_decode($itemData['stok_allocations'], true);
                $allSerialsForItem = []; // Kumpulkan semua serial untuk item ini

                // Buat DetailPenjualan dulu untuk mendapatkan ID-nya
                $detailPenjualan = $penjualan->detailPenjualan()->create([
                    'jumlah' => $itemData['jumlah'],
                    'harga_jual' => $itemData['harga_jual'],
                    // Field lain akan diisi setelah loop alokasi
                ]);

                $isKonsinyasiItem = false; // Flag untuk item konsinyasi
                $tipeGaransiTerpilihUntukPelanggan = 'NONE'; // Default, akan diambil dari batch pertama 'RESMI' jika ada

                foreach ($stokAllocations as $alloc) {
                    $stokBarang = StokBarang::lockForUpdate()->find($alloc['id_stok_barang']);
                    if (!$stokBarang || $stokBarang->jumlah < $alloc['qty_allocated']) {
                        DB::rollBack();
                        return redirect()->back()->with('error', "Stok untuk Batch ID {$alloc['id_stok_barang']} (Produk: {$produk->nama}) tidak mencukupi atau tidak valid saat proses penyimpanan. Transaksi dibatalkan.")->withInput();
                    }

                    // Kurangi stok batch
                    $stokBarang->decrement('jumlah', $alloc['qty_allocated']);

                    // Simpan alokasi stok
                    $detailPenjualan->stokAlokasi()->create([
                        'id_stok_barang' => $stokBarang->id,
                        'jumlah_diambil' => $alloc['qty_allocated']
                    ]);

                    // Cek apakah ini barang konsinyasi
                    if ($stokBarang->tipe_stok === 'KONSINYASI') {
                        $isKonsinyasiItem = true;
                    }

                    // Tentukan tipe garansi untuk pelanggan (prioritaskan RESMI dari batch manapun)
                    if ($stokBarang->tipe_garansi === 'RESMI') {
                        $tipeGaransiTerpilihUntukPelanggan = 'RESMI';
                    } elseif ($tipeGaransiTerpilihUntukPelanggan !== 'RESMI' && $stokBarang->tipe_garansi === 'SELF_SERVICE') {
                        // Jika belum ada RESMI, ambil SELF_SERVICE
                        $tipeGaransiTerpilihUntukPelanggan = 'SELF_SERVICE';
                    }

                    // Update Log Nomor Seri jika produk berserial
                    if ($produk->memiliki_serial && isset($alloc['serials_selected']) && is_array($alloc['serials_selected'])) {
                        foreach ($alloc['serials_selected'] as $serialNumber) {
                            $logSerial = LogNomorSeri::where('id_stok_barang_asal', $stokBarang->id)
                                                    ->where('nomor_seri', trim($serialNumber))
                                                    ->where('status_log', 'DITERIMA') // Pastikan update yang statusnya DITERIMA
                                                    ->first();
                            if ($logSerial) {
                                $logSerial->update([
                                    'status_log' => 'TERJUAL',
                                    'id_referensi' => $detailPenjualan->id, // FK ke detail_penjualan
                                    'tipe_referensi' => DetailPenjualan::class, // Polymorphic type
                                    'tanggal_status' => $tanggalPenjualan,
                                ]);
                                $allSerialsForItem[] = trim($serialNumber);
                            } else {
                                // Serial tidak ditemukan atau statusnya bukan DITERIMA (seharusnya dicegah validasi request)
                                DB::rollBack();
                                return redirect()->back()->with('error', "Nomor Seri '{$serialNumber}' untuk Produk {$produk->nama} dari Batch ID {$stokBarang->id} tidak ditemukan atau statusnya tidak valid saat penyimpanan. Transaksi dibatalkan.")->withInput();
                            }
                        }
                    }
                } // End loop alokasi batch

                // Update DetailPenjualan dengan info gabungan
                $updateDataDetail = [];
                if (!empty($allSerialsForItem)) {
                    $updateDataDetail['nomor_seri_terjual'] = implode(',', array_unique($allSerialsForItem));
                }

                if ($isKonsinyasiItem) {
                    $updateDataDetail['status_bayar_konsinyasi'] = 'BELUM_DIBAYAR_SUPPLIER';
                } else {
                    $updateDataDetail['status_bayar_konsinyasi'] = 'BELUM_RELEVAN';
                }

                // Hitung Garansi Pelanggan
                if ($tipeGaransiTerpilihUntukPelanggan === 'RESMI' && $produk->durasi_garansi_standar_bulan > 0) {
                    $updateDataDetail['customer_garansi_mulai_at'] = $tanggalPenjualan->copy()->toDateString();
                    $updateDataDetail['customer_garansi_berakhir_at'] = $tanggalPenjualan->copy()->addMonths($produk->durasi_garansi_standar_bulan)->toDateString();
                } elseif ($tipeGaransiTerpilihUntukPelanggan === 'SELF_SERVICE') { // Contoh garansi self-service 1 minggu
                    $updateDataDetail['customer_garansi_mulai_at'] = $tanggalPenjualan->copy()->toDateString();
                    $updateDataDetail['customer_garansi_berakhir_at'] = $tanggalPenjualan->copy()->addWeeks(1)->toDateString();
                } else {
                    $updateDataDetail['customer_garansi_mulai_at'] = null;
                    $updateDataDetail['customer_garansi_berakhir_at'] = null;
                }

                if (!empty($updateDataDetail)) {
                    $detailPenjualan->update($updateDataDetail);
                }

            } // End loop items

            // Jika transaksi dari marketplace dan ada potensi stok tidak cukup (validasi di frontend mungkin tidak 100% real-time)
            // Kita bisa menambahkan logika di sini untuk menandai penjualan dengan status 'STOK_TIDAK_CUKUP'
            // jika saat proses pengurangan stok ternyata ada yang gagal (meski lockForUpdate harusnya mencegah ini).
            // Untuk sekarang, kita asumsikan validasi request dan lockForUpdate sudah cukup.
            // Jika kanal marketplace dan ada item yang stoknya jadi minus (seharusnya tidak terjadi), set status penjualan.
            if (in_array($validated['kanal_transaksi'], ['TOKOPEDIA', 'SHOPEE'])) {
                // Cek ulang apakah ada stok yang menjadi negatif setelah transaksi ini (sebagai fallback)
                // Ini lebih kompleks dan mungkin tidak perlu jika lockForUpdate bekerja dengan baik.
                // Jika terdeteksi, $penjualan->status_penjualan = 'STOK_TIDAK_CUKUP'; $penjualan->save();
            }


            DB::commit();

            // TODO: Redirect ke halaman cetak nota atau halaman sukses dengan ID penjualan
            return redirect()->route('kasir.penjualan.create') // Ganti dengan route yang sesuai
                             ->with('success', "Transaksi Penjualan ({$nomorInvoice}) berhasil disimpan.");

        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Error store penjualan: '. $e->getMessage() . ' - Line: ' . $e->getLine() . ' - File: ' . $e->getFile());
            return redirect()->back()
                             ->with('error', 'Terjadi kesalahan fatal saat menyimpan transaksi: ' . $e->getMessage())
                             ->withInput();
        }
    }

    /**
     * Generate the next invoice number.
     */
    private function generateNextInvoiceNumber(Carbon $date): string
    {
        // Format: INV-[KODE_CABANG]-[ddmmyy]-[NOMOR_URUT_HARIAN]
        // kita sudah punya implementasi untuk PO, ini bisa mirip
        $branchCode = config('app.branch_code', 'SBY'); // Ambil dari config
        $dateFormatted = $date->format('dmy');
        $prefix = "INV-{$branchCode}-{$dateFormatted}-";

        $lastToday = Penjualan::where('tanggal_penjualan', '>=', $date->copy()->startOfDay())
                                ->where('tanggal_penjualan', '<=', $date->copy()->endOfDay())
                                ->where('nomor_penjualan', 'LIKE', $prefix . '%')
                                ->orderBy('nomor_penjualan', 'desc')
                                ->first();
        $nextSequence = 1;
        if ($lastToday) {
            $lastSequence = (int) substr($lastToday->nomor_penjualan, strlen($prefix));
            $nextSequence = $lastSequence + 1;
        }
        return $prefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT); // Asumsi 3 digit urutan
    }
}