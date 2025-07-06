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
use App\Models\RiwayatPergerakanStok;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Pembelian;



class PenjualanController extends Controller
{
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
            'TOKO' => 'Toko Fisik - Pasar Genteng', 'TOKOPEDIA' => 'Toko Online - Tokopedia', 'SHOPEE' => 'Toko Online - Shopee',
        ];
        $tipeTransaksi = [
            'BIASA' => 'Biasa',
            'PESAN_BARANG' => 'Pesan Barang (DP)', // Nama yang lebih deskriptif
        ];

        return view('kasir.penjualan.create', compact(
            'namaKasir',
            'tanggalSekarang',
            'metodePembayaran',
            'kanalTransaksi',
            'tipeTransaksi'
        ));
    }

    public function searchPelangganAjax(Request $request)
    {
        $searchTerm = $request->input('q');
        $page = $request->input('page', 1);
        $limit = 15;

        $query = Pelanggan::where('status', true)
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

    public function searchProdukAjax(Request $request)
    {
        $searchTerm = $request->input('q');
        $page = $request->input('page', 1);
        $limit = 15;

        $query = Produk::where('status', true)
                       ->where(function ($q) use ($searchTerm) {
                           $q->where('nama', 'LIKE', "%{$searchTerm}%")
                             ->orWhere('kode_produk', 'LIKE', "%{$searchTerm}%");
                       });

        $produk = $query->orderBy('nama')
                        ->paginate($limit, ['id', 'nama', 'kode_produk', 'harga_jual_standart', 'memiliki_serial', 'durasi_garansi_standar_bulan'], 'page', $page);

        $results = $produk->map(function ($item) {
            // Ketersediaan stok akan dicek lebih detail saat pemilihan batch
            return [
                'id' => $item->id,
                'text' => $item->nama . ($item->kode_produk ? " ({$item->kode_produk})" : ''),
                'harga_jual_standar' => $item->harga_jual_standart,
                'memiliki_serial' => (bool) $item->memiliki_serial,
                'durasi_garansi_standar_bulan' => $item->durasi_garansi_standar_bulan,
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
            return response()->json(['success' => false, 'message' => 'No results found'], 404);
        }

        $query = StokBarang::where('id_produk', $idProduk)
                            ->where('jumlah', '>', 0)
                            ->where('kondisi', 'BAIK')
                            ->whereNull('id_penjualan_alokasi')
                            ->where('lokasi', 'TOKO')
                            ->withCount(['detailPenjualanAlokasiPesanBarangAktif as qty_dipesan']) // Hitung qty yg sudah di-booking
                            ->orderBy('diterima_at', 'asc');

        // ================================================================
        // === FILTER BARU UNTUK KONSINYASI ---
        // ================================================================
        $query->where(function ($q) {
            // Kondisi 1: Ambil semua stok yang tipenya bukan KONSINYASI (misal: REGULER)
            $q->where('tipe_stok', '!=', 'KONSINYASI')
              // Kondisi 2: ATAU jika tipenya KONSINYASI, pastikan harga belinya sudah diisi (lebih dari 0)
              ->orWhere(function ($subQ) {
                  $subQ->where('tipe_stok', '=', 'KONSINYASI')
                       ->where('harga_beli', '>', 0);
              });
        });
        // ================================================================
        // === AKHIR FILTER BARU ---
        // ================================================================

        $batches = $query->orderBy('diterima_at', 'asc')->get();

        $formattedBatches = $batches->map(function($batch) {
            // Stok siap jual adalah stok fisik dikurangi yang sudah di-booking
            $stokSiapJual = $batch->jumlah - $batch->qty_dipesan;
            // Hanya tampilkan batch yang stok siap jualnya masih ada
        if ($stokSiapJual > 0) {
            return [
                'id' => $batch->id,
                'jumlah_tersedia' => $stokSiapJual, // Kirim stok efektif ke frontend
                'diterima_at_formatted' => Carbon::parse($batch->diterima_at)->isoFormat('D MMM YYYY, HH:mm'),
                'harga_beli_formatted' => 'Rp ' . number_format($batch->harga_beli, 0, ',', '.'),
                'lokasi' => $batch->lokasi,
                'tipe_garansi' => $batch->tipe_garansi,
                'tipe_garansi_display' => ucwords(str_replace('_', ' ', $batch->tipe_garansi)),
                'tipe_stok' => $batch->tipe_stok,
                'tipe_stok_display' => ucwords(str_replace('_', ' ', $batch->tipe_stok)),
            ];
        }
        return null; // Return null untuk batch yang stok efektifnya 0 atau kurang
        })->filter()->values(); // Hapus nilai null dari koleksi dan re-index array

        $totalStokTersediaDiSemuaBatch = $formattedBatches->sum('jumlah_tersedia');

        return response()->json([
            'success' => true,
            'memiliki_serial' => (bool) $produk->memiliki_serial,
            'durasi_garansi_standar_bulan_produk' => $produk->durasi_garansi_standar_bulan,
            'batches_data' => $formattedBatches,
            'total_stok_tersedia' => $totalStokTersediaDiSemuaBatch,
            'qty_diminta' => (int) $request->input('qty_dibutuhkan', 1),
        ]);
    }

    public function getAvailableSerialsAjax(Request $request)
    {
        $request->validate([
            'id_stok_barang' => 'required|integer|exists:stok_barang,id',
        ]);

        $idStokBarang = $request->input('id_stok_barang');
        $batch = StokBarang::with('produk')->find($idStokBarang);

        if (!$batch || !$batch->produk->memiliki_serial) {
            return response()->json(['success' => false, 'message' => 'Batch tidak ditemukan atau produk tidak berserial.', 'serials' => []]);
        }
        
        // =========================================================================
        // ## LOGIKA BARU MENGGUNAKAN 'riwayat_pergerakan_stok' (LEBIH BAIK) ##
        // =========================================================================
        
        // Langkah 1: Dapatkan semua nomor seri yang PERNAH tercatat masuk ke batch ini sebagai kandidat.
        $candidateSerials = RiwayatPergerakanStok::where('id_stok_barang_terkait', $idStokBarang)
            ->where('jumlah_masuk', '>', 0)
            ->whereNotNull('nomor_seri')
            ->distinct()
            ->pluck('nomor_seri');

        if ($candidateSerials->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tidak ada catatan nomor seri masuk untuk batch ini.', 'serials' => []]);
        }

        // Langkah 2: Dari semua kandidat, cari tahu ID record pergerakan TERAKHIR untuk setiap serial.
        // Ini adalah cara paling efisien untuk menentukan status terkini.
        $latestMovementIds = RiwayatPergerakanStok::select(DB::raw('MAX(id) as id'))
            ->whereIn('nomor_seri', $candidateSerials)
            ->groupBy('nomor_seri')
            ->pluck('id');

        // Langkah 3: Ambil semua serial dari pergerakan terakhir yang:
        // a. Merupakan transaksi MASUK (bukan keluar).
        // b. Benar-benar milik batch yang sedang kita cek.
        $availableSerials = RiwayatPergerakanStok::whereIn('id', $latestMovementIds)
            ->where('jumlah_masuk', '>', 0) 
            ->where('id_stok_barang_terkait', $idStokBarang)
            ->pluck('nomor_seri')
            ->values()
            ->all();

        if (empty($availableSerials)) {
            return response()->json([
                'success' => false,
                'message' => 'Semua nomor seri untuk batch ini sudah teralokasi atau tidak tersedia.',
                'serials' => []
            ]);
        }

        return response()->json([
            'success' => true,
            'serials' => $availableSerials
        ]);
    }

    public function store(StorePenjualanRequest $request)
    {
        Log::info('Store Penjualan Request Data:', $request->all());
        $validated = $request->validated();
        $user = Auth::user();
        $tanggalPenjualan = Carbon::parse($validated['tanggal_penjualan']);

        DB::beginTransaction();
        try {
            // 1. Handle Pelanggan
            $pelangganId = $validated['id_pelanggan'] ?? null;
            if (empty($pelangganId) && !empty($validated['pelanggan_baru_nama'])) {
                $pelanggan = Pelanggan::create([
                    'nama' => $validated['pelanggan_baru_nama'],
                    'telepon' => $validated['pelanggan_baru_telepon'] ?? null,
                    'alamat' => $validated['pelanggan_baru_alamat'] ?? null,
                    'status' => true,
                ]);
                $pelangganId = $pelanggan->id;
            }

            // 2. Generate Nomor Invoice
            $nomorInvoice = $this->generateNextInvoiceNumber($tanggalPenjualan);

            // 3. Tentukan Status Pembayaran & Penjualan Awal
            $statusPembayaran = 'LUNAS';
            $statusPenjualan = 'SELESAI'; // Default untuk transaksi BIASA
            $dibayarAt = $tanggalPenjualan->copy();
            $uangMuka = null;
            $sisaPembayaran = null;
            $estimasiKirimAt = null;

            if ($validated['tipe_transaksi'] === 'PESAN_BARANG') {
                $totalHarga = (float)$validated['total_harga'];
                $uangMuka = (float)($validated['uang_muka'] ?? 0); // Sudah divalidasi 'required'
                $estimasiKirimAt = $validated['estimasi_kirim_at'] ?? null;

                if ($uangMuka < $totalHarga) {
                    $statusPembayaran = 'DP';
                    $dibayarAt = null; // Lunas penuh belum terjadi
                } else { // DP >= Total (dianggap lunas saat pesan)
                    $statusPembayaran = 'LUNAS';
                    // dibayarAt tetap tanggal transaksi jika lunas saat DP
                }
                $statusPenjualan = 'MENUNGGU_BARANG'; // Untuk PESAN_BARANG, selalu menunggu barang
                $sisaPembayaran = max(0, $totalHarga - $uangMuka);
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
                'uang_muka' => $uangMuka,
                'sisa_pembayaran' => $sisaPembayaran,
                'estimasi_kirim_at' => $estimasiKirimAt,
                'status_pembayaran' => $statusPembayaran,
                'dibayar_at' => $dibayarAt,
                'status_penjualan' => $statusPenjualan,
                'catatan' => $validated['catatan'] ?? null,
                'status' => true,
            ]);

            // 5. Proses Detail Penjualan dan Stok
        foreach ($validated['items'] as $itemData) {
            $produk = Produk::find($itemData['id_produk']);
            if (!$produk) { throw new \Exception("Produk dengan ID {$itemData['id_produk']} tidak ditemukan."); }

            $detailPenjualan = $penjualan->detailPenjualan()->create([
                'id_penjualan' => $penjualan->id,
                'id_produk' => $itemData['id_produk'],
                'jumlah' => $itemData['jumlah'],
                'harga_jual' => $itemData['harga_jual'],
                'nama_produk_snapshot' => $produk->nama,
                'kode_produk_snapshot' => $produk->kode_produk,
                'subtotal' => (int)$itemData['jumlah'] * (float)$itemData['harga_jual'],
            ]);

          if ($validated['tipe_transaksi'] === 'BIASA') {
                $stokAllocations = json_decode($itemData['stok_allocations'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Format data alokasi stok tidak valid untuk produk {$produk->nama}.");
                }

                $allSerialsForItem = [];
                $tipeGaransiTerpilihUntukPelanggan = 'NONE';

                foreach ($stokAllocations as $alloc) {
                    $stokBarang = StokBarang::lockForUpdate()->find($alloc['id_stok_barang']);
                    if (!$stokBarang) {
                        throw new \Exception("Batch stok dengan ID {$alloc['id_stok_barang']} tidak ditemukan.");
                    }
                    if ($stokBarang->jumlah < $alloc['qty_allocated']) {
                        throw new \Exception("Stok untuk Batch ID {$alloc['id_stok_barang']} tidak mencukupi.");
                    }

                    $serialsTerjualDiAlokasiIni = $alloc['serials_selected'] ?? [];
                    if ($produk->memiliki_serial) {
                        if (count($serialsTerjualDiAlokasiIni) !== (int)$alloc['qty_allocated']) {
                            throw new \Exception("Jumlah nomor seri tidak cocok dengan kuantitas untuk produk {$produk->nama}.");
                        }
                        
                        foreach ($serialsTerjualDiAlokasiIni as $serialNumber) {
                            $pergerakanTerakhir = RiwayatPergerakanStok::where('nomor_seri', trim($serialNumber))->latest('id')->first();
                            if (!$pergerakanTerakhir || $pergerakanTerakhir->jumlah_masuk == 0 || $pergerakanTerakhir->id_stok_barang_terkait != $stokBarang->id) {
                                throw new \Exception("Nomor Seri '{$serialNumber}' tidak valid atau tidak tersedia di Batch ID {$stokBarang->id}.");
                            }
                        }
                    }
                    
                    // Kurangi stok fisik
                    $stokBarang->decrement('jumlah', $alloc['qty_allocated']);
                    $detailPenjualan->stokAlokasi()->create(['id_stok_barang' => $stokBarang->id, 'jumlah_diambil' => $alloc['qty_allocated']]);

                    // === LOGIKA PO OTOMATIS KONSINYASI (Sudah Benar) ===
                    if ($stokBarang->tipe_stok === 'KONSINYASI') {
                        $poKonsinyasi = Pembelian::create([
                            'id_supplier'       => $stokBarang->id_supplier,
                            'id_pengguna'       => Auth::id(),
                            'nomor_pembelian'   => $this->generateNextKonsinyasiPONumber(now()),
                            'tanggal_pembelian' => now(),
                            'total_harga'       => $alloc['qty_allocated'] * $stokBarang->harga_beli,
                            'status_pembayaran' => 'BELUM_LUNAS',
                            'status_pembelian'  => 'SELESAI',
                            'catatan'           => 'Pembelian otomatis dari penjualan barang konsinyasi. Ref. Invoice: ' . $penjualan->nomor_penjualan,
                            'status'            => true,
                        ]);
                        $poKonsinyasi->detailPembelian()->create([
                            'id_produk'         => $produk->id,
                            'jumlah'            => $alloc['qty_allocated'],
                            'harga_beli'        => $stokBarang->harga_beli,
                            'jumlah_diterima'   => $alloc['qty_allocated'],
                        ]);
                        $detailPenjualan->update(['catatan' => 'Pembelian konsinyasi terkait: ' . $poKonsinyasi->nomor_pembelian]);
                    }
                    // =========================================================================
                    // === AKHIR LOGIKA BARU ---
                    // =========================================================================
                    if ($stokBarang->tipe_garansi === 'RESMI') { $tipeGaransiTerpilihUntukPelanggan = 'RESMI'; } 
                    elseif ($tipeGaransiTerpilihUntukPelanggan !== 'RESMI' && $stokBarang->tipe_garansi === 'SELF_SERVICE') { $tipeGaransiTerpilihUntukPelanggan = 'SELF_SERVICE'; }

                    
                    $keteranganRiwayat = 'Terjual ke ' . ($penjualan->pelanggan->nama ?? 'Pelanggan Umum');

                    if ($produk->memiliki_serial) {
                        foreach ($serialsTerjualDiAlokasiIni as $serialNumber) {
                            $trimmedSn = trim($serialNumber);
                            $saldoSebelumnya = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->value('saldo_setelah_transaksi') ?? 0;
                            RiwayatPergerakanStok::create([
                                'id_produk' => $produk->id,
                                'id_stok_barang_terkait' => $stokBarang->id,
                                'nomor_seri' => $trimmedSn,
                                'tipe_transaksi' => 'PENJUALAN',
                                'jumlah_masuk' => 0,
                                'jumlah_keluar' => 1,
                                'saldo_setelah_transaksi' => $saldoSebelumnya - 1,
                                'id_referensi' => $penjualan->id,
                                'tipe_referensi' => get_class($penjualan),
                                'tanggal_transaksi' => $tanggalPenjualan,
                                'keterangan' => $keteranganRiwayat,
                                'id_pengguna' => Auth::id(),
                            ]);
                            $allSerialsForItem[] = $trimmedSn;
                        }

                   } else { // Non-serial
                        $saldoSebelumnya = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->value('saldo_setelah_transaksi') ?? 0;

                        RiwayatPergerakanStok::create([
                            'id_produk' => $produk->id,
                            'id_stok_barang_terkait' => $stokBarang->id,
                            'nomor_seri' => null, // Tidak ada serial
                            'tipe_transaksi' => 'PENJUALAN',
                            'jumlah_masuk' => 0,
                            'jumlah_keluar' => $alloc['qty_allocated'],
                            'saldo_setelah_transaksi' => $saldoSebelumnya - $alloc['qty_allocated'],
                            'id_referensi' => $penjualan->id,
                            'tipe_referensi' => Penjualan::class,
                            'tanggal_transaksi' => $tanggalPenjualan,
                            'keterangan' => $keteranganRiwayat,
                            'id_pengguna' => Auth::id(),
                        ]);
                    }
                    // =====================================================================
                    // ## AKHIR PENCATATAN BARU (PERBAIKAN FINAL)                         ##
                    // =====================================================================
                } // End loop alokasi batch

                    // Update detail penjualan dengan serial dan garansi
                    $updateDataDetail = [];
                    if (!empty($allSerialsForItem)) {
                        $updateDataDetail['nomor_seri_terjual'] = implode(',', array_unique($allSerialsForItem));
                    }

                    if ($tipeGaransiTerpilihUntukPelanggan === 'RESMI' && $produk->durasi_garansi_standar_bulan > 0) {
                        $updateDataDetail['customer_garansi_mulai_at'] = $tanggalPenjualan->copy()->toDateString();
                        $updateDataDetail['customer_garansi_berakhir_at'] = $tanggalPenjualan->copy()->addMonths($produk->durasi_garansi_standar_bulan)->toDateString();
                    } elseif ($tipeGaransiTerpilihUntukPelanggan === 'SELF_SERVICE') {
                        // Asumsi garansi self-service toko adalah 7 hari (1 minggu)
                        // Ini bisa dibuat lebih dinamis jika perlu, misal ada setting durasi self-service per produk
                        $updateDataDetail['customer_garansi_mulai_at'] = $tanggalPenjualan->copy()->toDateString();
                        $updateDataDetail['customer_garansi_berakhir_at'] = $tanggalPenjualan->copy()->addWeeks(1)->toDateString();
                    } else {
                        $updateDataDetail['customer_garansi_mulai_at'] = null;
                        $updateDataDetail['customer_garansi_berakhir_at'] = null;
                    }

                    if (!empty($updateDataDetail)) {
                        $detailPenjualan->update($updateDataDetail);
                    }
                } // Akhir dari blok if ($validated['tipe_transaksi'] === 'BIASA')
            } // End loop items

            // Logika tambahan jika dari marketplace dan ada item yang stoknya jadi minus (setelah validasi request dan lockForUpdate).
            // Ini lebih sebagai safety net, seharusnya tidak sering terjadi jika validasi awal ketat.
            if (in_array($validated['kanal_transaksi'], ['TOKOPEDIA', 'SHOPEE'])) {
                $penjualanSudahSelesai = true; // Asumsi awal
                foreach ($penjualan->detailPenjualan as $detail) {
                    // Jika ada DetailPenjualanStokAlokasi, cek stok batch terkait
                    if ($detail->stokAlokasi()->exists()) {
                        foreach($detail->stokAlokasi as $alokasi) {
                            if ($alokasi->stokBarang->jumlah < 0) { // Jika setelah decrement jadi negatif
                                $penjualanSudahSelesai = false;
                                $penjualan->status_penjualan = 'STOK_TIDAK_CUKUP';
                                $penjualan->save();
                                Log::warning("Stok menjadi negatif untuk penjualan marketplace: {$penjualan->nomor_penjualan}, Produk ID: {$detail->id_produk}, Batch ID: {$alokasi->id_stok_barang}");
                                break 2; // Keluar dari kedua loop
                            }
                        }
                    }
                }
                // Jika transaksi marketplace dan bukan PESAN_BARANG dan status masih 'SELESAI' (tidak diubah jadi STOK_TIDAK_CUKUP)
                if ($penjualan->status_penjualan === 'SELESAI' && $validated['tipe_transaksi'] === 'BIASA') {
                    // Tidak ada tindakan khusus, status 'SELESAI' sudah tepat
                }
            }

            DB::commit();
            // Simpan ID Penjualan di session untuk diakses dihalaman create
            session()->flash('last_penjualan_id_for_nota', $penjualan->id);
            session()->flash('last_penjualan_nomor', $penjualan->nomor_penjualan);

            return redirect()->route('kasir.penjualan.create') // Atau ke halaman detail penjualan jika ada
                             ->with('success', "Transaksi Penjualan ({$penjualan->nomor_penjualan}) berhasil disimpan. Nota akan terbuka di tab baru.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store penjualan: '. $e->getMessage() . ' - Line: ' . $e->getLine() . ' - File: ' . $e->getFile() . ' - Trace: ' . $e->getTraceAsString());
            return redirect()->back()
                             ->with('error', 'Terjadi kesalahan fatal saat menyimpan transaksi: ' . $e->getMessage())
                             ->withInput();
        }
    }

    private function generateNextInvoiceNumber(Carbon $date): string
    {
        $branchCode = config('app.branch_code', 'KGT'); // Kingstar Genteng
        $dateFormatted = $date->format('dmy'); // Format ddmmyy
        $prefix = "INV-{$branchCode}-{$dateFormatted}-";

        // Cari nomor invoice terakhir untuk tanggal hari ini
        $lastToday = Penjualan::where('tanggal_penjualan', '>=', $date->copy()->startOfDay())
                                ->where('tanggal_penjualan', '<=', $date->copy()->endOfDay())
                                ->where('nomor_penjualan', 'LIKE', $prefix . '%')
                                ->orderBy('nomor_penjualan', 'desc') // Urutkan dari yang terbesar
                                ->first();
        $nextSequence = 1;
        if ($lastToday) {
            // Ambil bagian nomor urut dari nomor invoice terakhir
            // Contoh: INV-KGT-010824-005 -> ambil 005
            $lastSequencePart = substr($lastToday->nomor_penjualan, strlen($prefix));
            if (is_numeric($lastSequencePart)) {
                $nextSequence = (int)$lastSequencePart + 1;
            }
            // Jika tidak numerik (seharusnya tidak terjadi dengan format ini), tetap mulai dari 1
        }
        return $prefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT); // Urutan 3 digit, misal 001, 002, ... 010, ... 100
    }

    // --- METHOD HELPER BARU UNTUK NOMOR PO KONSINYASI ---
    private function generateNextKonsinyasiPONumber(Carbon $date): string
    {
        $branchCode = config('app.branch_code', 'KGT');
        $dateFormatted = $date->format('dmy');
        // Gunakan prefix berbeda agar mudah diidentifikasi, misal 'POC' untuk PO Consignment ato konsinyasi artinea
        $prefix = "POC-{$branchCode}-{$dateFormatted}-"; 
        
        $lastToday = Pembelian::where('nomor_pembelian', 'LIKE', $prefix . '%')
                                ->orderBy('nomor_pembelian', 'desc')
                                ->first();
        $nextSequence = 1;
        if ($lastToday) {
            $lastSequencePart = substr($lastToday->nomor_pembelian, strlen($prefix));
            if (is_numeric($lastSequencePart)) {
                $nextSequence = (int)$lastSequencePart + 1;
            }
        }
        return $prefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    }

    
    /**
     * Menampilkan halaman nota penjualan untuk dicetak.
     *
     * @param  int  $id ID Penjualan
     * @return \Illuminate\View\View
     */
    public function showNota($id)
    {
        // Menggunakan eager loading untuk memuat semua relasi yang dibutuhkan dalam satu query
        $penjualan = \App\Models\Penjualan::with([
            'pelanggan',
            'pengguna',
            'detailPenjualan' => function ($query) {
                // Eager load relasi turunan dari detailPenjualan
                $query->with(['produk', 'stokAlokasi.stokBarang']); 
            },
            // Muat relasi header retur, dan dari situ muat detail item retur,
            // dan dari detail item retur, muat lagi detail penjualan asalnya untuk dapat nama produk.
            'retur' => function ($query) {
                $query->with(['detailReturPenjualan.detailPenjualanAsal.produk']);
            }
        ])->findOrFail($id);

        // Variabel statis untuk informasi toko
        $namaToko = "KINGSTAR ELEKTRONIK";
        $alamatToko = "Pasar Genteng Baru Lt. 2 Blok N no. 20, Surabaya";
        $teleponToko = "081290808046";
        
        // Mengirim data ke view
        return view('kasir.penjualan.nota', compact(
            'penjualan',
            'namaToko',
            'alamatToko',
            'teleponToko'
        ));
    }
    
}