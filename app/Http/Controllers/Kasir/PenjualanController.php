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
// Pastikan model DetailPenjualanStokAlokasi ada jika Anda menggunakannya,
// Namun dalam logika store di bawah, kita akan create langsung dari relasi DetailPenjualan
// use App\Models\DetailPenjualanStokAlokasi;


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
            'qty_dibutuhkan' => 'required|integer|min:1', // qty_dibutuhkan kini hanya untuk info di modal
        ]);

        $idProduk = $request->input('id_produk');
        $qtyDibutuhkan = $request->input('qty_dibutuhkan'); // Untuk dikirim kembali ke JS
        $produk = Produk::find($idProduk);

        if (!$produk) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        $batches = StokBarang::where('id_produk', $idProduk)
                            ->where('jumlah', '>', 0)
                            ->where('kondisi', 'BAIK')
                            ->whereNull('id_penjualan_alokasi') // Hanya batch yang BELUM dialokasikan
                            ->where('lokasi', 'TOKO')           // Hanya batch yang ada di lokasi TOKO (sesuaikan jika perlu)
                            ->orderBy('diterima_at', 'asc')     // FIFO
                            ->get();

        $formattedBatches = $batches->map(function($batch) {
            return [
                'id' => $batch->id,
                'jumlah_tersedia' => $batch->jumlah,
                'diterima_at_formatted' => Carbon::parse($batch->diterima_at)->isoFormat('D MMM YYYY, HH:mm'),
                'harga_beli_formatted' => 'Rp ' . number_format($batch->harga_beli, 0, ',', '.'), // Contoh format harga beli
                'lokasi' => $batch->lokasi,
                'tipe_garansi' => $batch->tipe_garansi, // Kirim kode aslinya
                'tipe_garansi_display' => ucwords(str_replace('_', ' ', $batch->tipe_garansi)),
                'tipe_stok' => $batch->tipe_stok,
                'tipe_stok_display' => ucwords(str_replace('_', ' ', $batch->tipe_stok)),
            ];
        });

        $totalStokTersediaDiSemuaBatch = $batches->sum('jumlah');

        return response()->json([
            'success' => true,
            'memiliki_serial' => (bool) $produk->memiliki_serial,
            'durasi_garansi_standar_bulan_produk' => $produk->durasi_garansi_standar_bulan,
            'batches_data' => $formattedBatches,
            'total_stok_tersedia' => $totalStokTersediaDiSemuaBatch,
            'qty_diminta' => $qtyDibutuhkan,
        ]);
    }

    public function getAvailableSerialsAjax(Request $request)
    {
        $request->validate([
            'id_stok_barang' => 'required|integer|exists:stok_barang,id',
        ]);

        $idStokBarang = $request->input('id_stok_barang');

        $serials = LogNomorSeri::where('id_stok_barang_asal', $idStokBarang)
                                ->where('status_log', 'DITERIMA')
                                ->pluck('nomor_seri')
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
                if (!$produk) {
                    DB::rollBack();
                    return redirect()->back()->with('error', "Produk dengan ID {$itemData['id_produk']} tidak ditemukan.")->withInput();
                }

                $subtotal = (int)$itemData['jumlah'] * (float)$itemData['harga_jual'];

                $detailPenjualan = DetailPenjualan::create([
                    'id_penjualan' => $penjualan->id,
                    'id_produk' => $itemData['id_produk'],
                    'jumlah' => $itemData['jumlah'],
                    'harga_jual' => $itemData['harga_jual'],
                    'nama_produk_snapshot' => $produk->nama,
                    'kode_produk_snapshot' => $produk->kode_produk,
                    'subtotal' => $subtotal,
                    'status_bayar_konsinyasi' => 'BELUM_RELEVAN', // Default
                    // nomor_seri_terjual dan garansi akan di-handle di bawah jika bukan PESAN_BARANG
                ]);

                // HANYA PROSES ALOKASI STOK, SERIAL, GARANSI JIKA TIPE TRANSAKSI 'BIASA'
                if ($validated['tipe_transaksi'] === 'BIASA') {
                    // Pastikan stok_allocations ada dan valid (meskipun sudah divalidasi di request)
                    if (!isset($itemData['stok_allocations'])) {
                         DB::rollBack();
                         return redirect()->back()->with('error', "Data alokasi stok tidak ditemukan untuk produk {$produk->nama} pada transaksi biasa.")->withInput();
                    }
                    $stokAllocations = json_decode($itemData['stok_allocations'], true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($stokAllocations) || empty($stokAllocations)) {
                        DB::rollBack();
                        return redirect()->back()->with('error', "Format data alokasi stok tidak valid atau kosong untuk produk {$produk->nama} pada transaksi biasa.")->withInput();
                    }

                    $allSerialsForItem = [];
                    $isKonsinyasiItem = false;
                    $tipeGaransiTerpilihUntukPelanggan = 'NONE';

                    foreach ($stokAllocations as $alloc) {
                        if (!isset($alloc['id_stok_barang']) || !isset($alloc['qty_allocated'])) {
                            DB::rollBack();
                            return redirect()->back()->with('error', "Data alokasi batch tidak lengkap untuk produk {$produk->nama}.")->withInput();
                        }

                        $stokBarang = StokBarang::lockForUpdate()->find($alloc['id_stok_barang']);
                        if (!$stokBarang || $stokBarang->jumlah < $alloc['qty_allocated']) {
                            DB::rollBack();
                            return redirect()->back()->with('error', "Stok untuk Batch ID {$alloc['id_stok_barang']} (Produk: {$produk->nama}) tidak mencukupi atau tidak valid saat proses penyimpanan.")->withInput();
                        }

                        $stokBarang->decrement('jumlah', $alloc['qty_allocated']);

                        $detailPenjualan->stokAlokasi()->create([
                            'id_stok_barang' => $stokBarang->id,
                            'jumlah_diambil' => $alloc['qty_allocated']
                        ]);

                        if ($stokBarang->tipe_stok === 'KONSINYASI') {
                            $isKonsinyasiItem = true;
                        }

                        if ($stokBarang->tipe_garansi === 'RESMI') {
                            $tipeGaransiTerpilihUntukPelanggan = 'RESMI';
                        } elseif ($tipeGaransiTerpilihUntukPelanggan !== 'RESMI' && $stokBarang->tipe_garansi === 'SELF_SERVICE') {
                            $tipeGaransiTerpilihUntukPelanggan = 'SELF_SERVICE';
                        }

                        if ($produk->memiliki_serial && isset($alloc['serials_selected']) && is_array($alloc['serials_selected'])) {
                            foreach ($alloc['serials_selected'] as $serialNumber) {
                                $logSerial = LogNomorSeri::where('id_stok_barang_asal', $stokBarang->id)
                                                        ->where('nomor_seri', trim($serialNumber))
                                                        ->where('status_log', 'DITERIMA')
                                                        ->first();
                                if ($logSerial) {
                                    $logSerial->update([
                                        'status_log' => 'TERJUAL',
                                        'id_referensi' => $detailPenjualan->id,
                                        'tipe_referensi' => DetailPenjualan::class,
                                        'tanggal_status' => $tanggalPenjualan,
                                    ]);
                                    $allSerialsForItem[] = trim($serialNumber);
                                } else {
                                    DB::rollBack();
                                    return redirect()->back()->with('error', "Nomor Seri '{$serialNumber}' untuk Produk {$produk->nama} dari Batch ID {$stokBarang->id} tidak ditemukan atau statusnya tidak valid.")->withInput();
                                }
                            }
                        }
                    } // End loop alokasi batch

                    // Update DetailPenjualan dengan info gabungan untuk transaksi BIASA
                    $updateDataDetail = [];
                    if (!empty($allSerialsForItem)) {
                        $updateDataDetail['nomor_seri_terjual'] = implode(',', array_unique($allSerialsForItem));
                    }

                    $updateDataDetail['status_bayar_konsinyasi'] = $isKonsinyasiItem ? 'BELUM_DIBAYAR_SUPPLIER' : 'BELUM_RELEVAN';

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
    
    public function showNota($id)
    {
        $penjualan = Penjualan::with([
            'pelanggan',
            'pengguna',
            'detailPenjualan' => function ($query) {
                $query->with(['produk', 'stokAlokasi.stokBarang']); //eager load produk dan batch asal
            }
        ])->findOrFail($id);
        // informasi garansi dan serial spesifik
        foreach ($penjualan->detailPenjualan as $detail) {
            // informasi garansi sdh ada di $detail->customer_garansi_mulai_at dan berakhir at
            // tipe garansi bisa diambil dari batch pertama yang resmi

        }

        $namaToko = "KINGSTAR ELEKTRONIK";
        $alamatToko = "Pasar Genteng Baru Lt. 2 Blok N no. 20, Surabaya";
        $teleponToko = "081290808046";
        
        return view('kasir.penjualan.nota', compact(
            'penjualan',
            'namaToko',
            'alamatToko',
            'teleponToko'
        ));
    }
    
}