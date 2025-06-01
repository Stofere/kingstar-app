<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Models\DetailPenjualanStokAlokasi; // Model untuk alokasi stok terkait penjualan
use App\Models\StokBarang; // Model untuk stok barang
use App\Models\LogNomorSeri; // Model untuk log nomor seri terkait penjualan
use App\Models\DetailPenjualan; // Model untuk detail penjualan yang berisi informasi produk, jumlah, harga, dll.


class KasirPesanBarangController extends Controller
{
    /**
     * Menampilkan daftar Pesan Barang yang menunggu pelunasan atau pengambilan.
     */
    public function index()
    {
        $pesananMenungguPenyelesaian = Penjualan::where('tipe_transaksi', 'PESAN_BARANG')
            ->whereIn('status_penjualan', ['MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL']) // Status yang relevan untuk Kasir
            ->with(['pelanggan', 'pengguna']) // Eager load relasi dasar
            ->orderBy('tanggal_penjualan', 'asc') // Tampilkan yang paling lama dulu
            ->paginate(15); // Gunakan paginasi

        return view('kasir.pesan_barang_selesai.index', compact('pesananMenungguPenyelesaian'));
    }

    /**
     * Menampilkan form untuk menyelesaikan (pelunasan/pengambilan) satu Pesan Barang.
     */
    public function showSelesaikanForm(Penjualan $penjualan) // Menggunakan Route Model Binding
    {
        // Validasi apakah pesanan ini memang boleh diproses oleh Kasir
        if ($penjualan->tipe_transaksi !== 'PESAN_BARANG' ||
            !in_array($penjualan->status_penjualan, ['MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL'])) {
            return redirect()->route('kasir.pesan_barang_selesai.index')
                             ->with('error', 'Status pesanan tidak valid untuk diproses atau sudah selesai.');
        }

        // Eager load semua relasi yang dibutuhkan untuk ditampilkan di form
        $penjualan->load([
            'pelanggan',
            'pengguna', // Kasir yang membuat pesanan awal
            'detailPenjualan' => function ($queryDetail) {
                $queryDetail->with([
                    'produk',
                    'stokAlokasi' => function ($queryAlokasi) {
                        // Hanya ambil pra-alokasi yang relevan (yang dibuat Admin)
                        $queryAlokasi->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
                                     ->with('stokBarang'); // Ambil juga detail batchnya
                    }
                ]);
            }
        ]);

        // Siapkan data untuk dropdown metode pembayaran (bisa sama dengan form penjualan biasa)
        $metodePembayaran = [
            'TUNAI' => 'Tunai', 'QRIS' => 'QRIS', 'TRANSFER_BCA' => 'Transfer BCA',
            'TRANSFER_MANDIRI' => 'Transfer Mandiri', 'DEBIT_BCA' => 'Debit BCA',
            'DEBIT_MANDIRI' => 'Debit Mandiri', 'KARTU_KREDIT' => 'Kartu Kredit',
        ];

        $namaKasirSaatIni = Auth::user()->nama; // Kasir yang memproses penyelesaian

        return view('kasir.pesan_barang_selesai.form', compact(
            'penjualan',
            'metodePembayaran',
            'namaKasirSaatIni'
        ));
    }

    /**
     * Menyimpan proses penyelesaian Pesan Barang (pelunasan dan pengambilan).
     * (Ini akan kita implementasikan nanti setelah form selesai)
     */
    public function storeSelesaikan(Request $request, Penjualan $penjualan)
    {
        // TODO: Validasi input (metode pembayaran pelunasan, uang bayar jika ada sisa)
        // TODO: Proses inti pengurangan stok, update log serial, update status alokasi,
        //       update status penjualan, hitung garansi, dll. (seperti yang sudah kita diskusikan)

        Log::info("Kasir mencoba menyelesaikan Pesan Barang ID: {$penjualan->id}", $request->all());
        $user = Auth::user(); // Kasir yang memproses penyelesaian

        // --- SIMULASI PENYELESAIAN (GANTI DENGAN LOGIKA SEBENARNYA) ---
        DB::beginTransaction();
        try {
            // 1. Ambil semua record DetailPenjualanStokAlokasi dengan tipe_alokasi 'DIALOKASIKAN_PESANAN'
            $praAlokasiItems = DetailPenjualanStokAlokasi::whereHas('detailPenjualan', function ($q) use ($penjualan) {
                $q->where('id_penjualan', $penjualan->id);
            })
            ->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
            ->with('stokBarang', 'detailPenjualan.produk') // Eager load
            ->get();

            if ($praAlokasiItems->isEmpty() && $penjualan->detailPenjualan->sum('jumlah') > 0) {
                 // Ini aneh jika ada item tapi tidak ada pra-alokasi
                 throw new \Exception("Tidak ditemukan data pra-alokasi stok untuk pesanan ini. Hubungi Admin.");
            }

            foreach ($praAlokasiItems as $alokasi) {
                $stokBarang = StokBarang::lockForUpdate()->find($alokasi->id_stok_barang);
                $detailPenjualan = $alokasi->detailPenjualan;
                $produk = $detailPenjualan->produk;

                if (!$stokBarang || $stokBarang->jumlah < $alokasi->jumlah_diambil) {
                    throw new \Exception("Stok fisik untuk Batch ID {$stokBarang->id} (Produk: {$produk->nama}) tidak mencukupi saat penyelesaian.");
                }

                // A. Kurangi Stok Fisik
                $stokBarang->decrement('jumlah', $alokasi->jumlah_diambil);

                // B. Update Log Nomor Seri jika ada
                if ($produk->memiliki_serial && !empty($alokasi->nomor_seri_terkait)) {
                    $serialsUntukDetailIni = explode(',', $alokasi->nomor_seri_terkait);
                    foreach ($serialsUntukDetailIni as $sn) {
                        $logSerial = LogNomorSeri::where('id_stok_barang_asal', $stokBarang->id)
                                                ->where('nomor_seri', trim($sn))
                                                // Idealnya status masih DITERIMA, tapi bisa jadi sudah TERJUAL jika ada kesalahan alokasi oleh admin
                                                // atau bisa juga sudah DIALOKASIKAN_KE_PESANAN_LAIN
                                                // Untuk amannya, kita cari yang DITERIMA, jika tidak ada, log error tapi lanjutkan.
                                                ->where('status_log', 'DITERIMA') 
                                                ->first();
                        if ($logSerial) {
                            $logSerial->update([
                                'status_log' => 'TERJUAL',
                                'id_referensi' => $detailPenjualan->id,
                                'tipe_referensi' => DetailPenjualan::class,
                                'tanggal_status' => now(),
                            ]);
                        } else {
                            Log::warning("LogNomorSeri tidak ditemukan/status bukan DITERIMA untuk SN: {$sn}, Batch: {$stokBarang->id} saat penyelesaian Pesanan ID: {$penjualan->id}");
                        }
                    }
                    // Update nomor_seri_terjual di detail_penjualan
                    // Gabungkan dari semua alokasi untuk satu detail penjualan jika satu detail bisa dari banyak alokasi pra
                     $currentSerials = !empty($detailPenjualan->nomor_seri_terjual) ? explode(',', $detailPenjualan->nomor_seri_terjual) : [];
                     $newSerials = array_unique(array_merge($currentSerials, $serialsUntukDetailIni));
                     $detailPenjualan->nomor_seri_terjual = implode(',', $newSerials);
                }
                // C. Update tipe_alokasi di DetailPenjualanStokAlokasi
                $alokasi->tipe_alokasi = 'STOK_KELUAR_PESANAN';
                $alokasi->save();
            }

            // D. Update Detail Penjualan (Garansi, Konsinyasi) setelah semua alokasi diproses
            foreach($penjualan->detailPenjualan as $dp) {
                $produkDp = $dp->produk;
                $isKonsinyasiItem = false;
                $tipeGaransiTerpilihUntukPelanggan = 'NONE';

                // Cek dari batch yang benar-benar diambil untuk item ini
                $alokasiUntukDp = DetailPenjualanStokAlokasi::where('id_detail_penjualan', $dp->id)
                                        ->where('tipe_alokasi', 'STOK_KELUAR_PESANAN') // Yang baru saja diupdate
                                        ->with('stokBarang')
                                        ->get();
                foreach($alokasiUntukDp as $alok) {
                    if ($alok->stokBarang->tipe_stok === 'KONSINYASI') {
                        $isKonsinyasiItem = true;
                    }
                    if ($alok->stokBarang->tipe_garansi === 'RESMI') {
                        $tipeGaransiTerpilihUntukPelanggan = 'RESMI';
                    } elseif ($tipeGaransiTerpilihUntukPelanggan !== 'RESMI' && $alok->stokBarang->tipe_garansi === 'SELF_SERVICE') {
                        $tipeGaransiTerpilihUntukPelanggan = 'SELF_SERVICE';
                    }
                }

                $updateDataDetail = [];
                $updateDataDetail['status_bayar_konsinyasi'] = $isKonsinyasiItem ? 'BELUM_DIBAYAR_SUPPLIER' : 'BELUM_RELEVAN';

                if ($tipeGaransiTerpilihUntukPelanggan === 'RESMI' && $produkDp->durasi_garansi_standar_bulan > 0) {
                    $updateDataDetail['customer_garansi_mulai_at'] = $penjualan->tanggal_penjualan->copy()->toDateString(); // Atau now() jika garansi mulai saat barang diambil
                    $updateDataDetail['customer_garansi_berakhir_at'] = Carbon::parse($penjualan->tanggal_penjualan)->addMonths($produkDp->durasi_garansi_standar_bulan)->toDateString();
                } elseif ($tipeGaransiTerpilihUntukPelanggan === 'SELF_SERVICE') {
                    $updateDataDetail['customer_garansi_mulai_at'] = $penjualan->tanggal_penjualan->copy()->toDateString();
                    $updateDataDetail['customer_garansi_berakhir_at'] = Carbon::parse($penjualan->tanggal_penjualan)->addWeeks(1)->toDateString();
                } else {
                    $updateDataDetail['customer_garansi_mulai_at'] = null;
                    $updateDataDetail['customer_garansi_berakhir_at'] = null;
                }
                if (!empty($updateDataDetail)) {
                    $dp->update($updateDataDetail);
                }
            }


            // E. Update Penjualan Utama
            $penjualan->status_penjualan = 'SELESAI';
            $penjualan->status_pembayaran = 'LUNAS'; // Pastikan lunas
            $penjualan->dibayar_at = $penjualan->dibayar_at ?? now(); // Tanggal lunas penuh (jika DP, ini tanggal pelunasan)
            $penjualan->sisa_pembayaran = 0;
            // Simpan kasir yang memproses penyelesaian jika perlu field terpisah
            // $penjualan->id_pengguna_selesai = $user->id;
            $penjualan->save();

            DB::commit();

            session()->flash('last_penjualan_id_for_nota', $penjualan->id);
            session()->flash('last_penjualan_nomor', $penjualan->nomor_penjualan);

            return redirect()->route('kasir.pesan_barang_selesai.index')
                             ->with('success', "Pesan Barang {$penjualan->nomor_penjualan} berhasil diselesaikan. Nota akan terbuka di tab baru.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error storeSelesaikan Pesan Barang ID {$penjualan->id}: " . $e->getMessage() . " - Line: " . $e->getLine());
            return redirect()->back()->with('error', 'Gagal menyelesaikan pesanan: ' . $e->getMessage())->withInput();
        }
    }
}