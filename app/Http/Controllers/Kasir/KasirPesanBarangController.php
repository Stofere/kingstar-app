<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Models\DetailPenjualanStokAlokasi;
use App\Models\StokBarang; 
use App\Models\RiwayatPergerakanStok;
use App\Models\DetailPenjualan; 

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
        Log::info("Kasir memulai proses penyelesaian untuk Pesanan ID: {$penjualan->id}", $request->all());

        // =========================================================================
        // BLOK BARU: MEMBERSIHKAN INPUT SEBELUM VALIDASI
        // =========================================================================
        if ($request->has('uang_bayar_pelunasan')) {
            // Panggil method helper yang ada di dalam class ini
            $cleanedValue = $this->cleanRupiahInput($request->input('uang_bayar_pelunasan'));
            $request->merge(['uang_bayar_pelunasan' => $cleanedValue]);
        }
        // =========================================================================
        // BLOK 1: VALIDASI INPUT PELUNASAN
        // =========================================================================
        $sisaPembayaran = (float)($penjualan->sisa_pembayaran ?? 0);
        if ($sisaPembayaran > 0) {
            $request->validate([
                'metode_pembayaran_pelunasan' => 'required|string',
                'uang_bayar_pelunasan' => 'required|numeric|min:' . $sisaPembayaran,
            ], [
                'metode_pembayaran_pelunasan.required' => 'Metode pembayaran untuk pelunasan wajib dipilih.',
                'uang_bayar_pelunasan.required' => 'Uang bayar untuk pelunasan wajib diisi.',
                'uang_bayar_pelunasan.min' => 'Uang bayar pelunasan tidak boleh kurang dari sisa pembayaran.',
            ]);
        }

        DB::beginTransaction();
        try {
            // =========================================================================
            // BLOK 2: PROSES SETIAP ITEM YANG DIALOKASIKAN
            // =========================================================================
            
            // Ambil semua item yang sudah dialokasikan oleh Admin
            $praAlokasiItems = DetailPenjualanStokAlokasi::whereHas('detailPenjualan', fn($q) => $q->where('id_penjualan', $penjualan->id))
                ->where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
                ->with('stokBarang', 'detailPenjualan.produk')
                ->get();

            if ($praAlokasiItems->isEmpty() && $penjualan->detailPenjualan->sum('jumlah') > 0) {
                throw new \Exception("Tidak ada data alokasi stok dari Admin untuk pesanan ini.");
            }

            foreach ($praAlokasiItems as $alokasi) {
                $stokBarang = StokBarang::lockForUpdate()->find($alokasi->id_stok_barang);
                $detailPenjualan = $alokasi->detailPenjualan;
                $produk = $detailPenjualan->produk;

                if (!$stokBarang || $stokBarang->jumlah < $alokasi->jumlah_diambil) {
                    throw new \Exception("Stok fisik untuk Batch ID {$stokBarang->id} (Produk: {$produk->nama}) tidak mencukupi.");
                }

                // A. Kurangi Stok Fisik dari Batch
                $stokBarang->decrement('jumlah', $alokasi->jumlah_diambil);

                // B. (BARU) Catat di Riwayat Pergerakan Stok
                $this->catatRiwayatStokKeluar($penjualan, $detailPenjualan, $stokBarang, $alokasi);
                
                // C. (BARU & DIPERBAIKI) Update Nomor Seri di Detail Penjualan
                if ($produk->memiliki_serial && !empty($alokasi->nomor_seri_terkait)) {
                    $currentSerials = !empty($detailPenjualan->nomor_seri_terjual) ? explode(',', $detailPenjualan->nomor_seri_terjual) : [];
                    $newSerials = array_unique(array_merge($currentSerials, explode(',', $alokasi->nomor_seri_terkait)));
                    $detailPenjualan->nomor_seri_terjual = implode(',', $newSerials);
                    $detailPenjualan->save(); // Simpan perubahan nomor seri
                }

                // D. Update tipe_alokasi di tabel junction menjadi final
                $alokasi->update(['tipe_alokasi' => 'STOK_KELUAR_PESANAN']);
            }
            
            // =========================================================================
            // BLOK 3: UPDATE INFORMASI FINAL DI DETAIL PENJUALAN (GARANSI DLL)
            // =========================================================================
            $this->updateDetailPenjualanFinal($penjualan);
            
            // =========================================================================
            // BLOK 4: UPDATE STATUS PENJUALAN UTAMA
            // =========================================================================
            $penjualan->status_penjualan = 'SELESAI';
            $penjualan->status_pembayaran = 'LUNAS';
            $penjualan->dibayar_at = now();
            $penjualan->sisa_pembayaran = 0;
            // Jika ada pelunasan, catat metode pembayaran terakhir
            if ($sisaPembayaran > 0) {
                $penjualan->metode_pembayaran = $request->input('metode_pembayaran_pelunasan');
            }
            $penjualan->save();

            DB::commit();

            session()->flash('last_penjualan_id_for_nota', $penjualan->id);
            session()->flash('last_penjualan_nomor', $penjualan->nomor_penjualan);

            return redirect()->route('kasir.pesan_barang_selesai.index')
                            ->with('success', "Pesanan {$penjualan->nomor_penjualan} berhasil diselesaikan. Nota akan terbuka di tab baru.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat menyelesaikan pesanan ID {$penjualan->id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal menyelesaikan pesanan: ' . $e->getMessage())->withInput();
        }
    }

     /**
     * Membersihkan input yang diformat sebagai Rupiah.
     * @param string|null $value
     * @return int
     */
    protected function cleanRupiahInput($value): int
    {
        if (is_null($value)) {
            return 0;
        }
        return (int) preg_replace('/[^0-9]/', '', $value);
    }

    /**
     * Method helper untuk mencatat pergerakan stok keluar.
     */
    private function catatRiwayatStokKeluar($penjualan, $detailPenjualan, $stokBarang, $alokasi)
    {
        $saldoSebelumnya = RiwayatPergerakanStok::where('id_produk', $detailPenjualan->id_produk)->latest('id')->value('saldo_setelah_transaksi') ?? 0;
        $keterangan = 'Penyelesaian Pesanan Barang untuk: ' . ($penjualan->pelanggan->nama ?? 'Umum');

        if ($detailPenjualan->produk->memiliki_serial && !empty($alokasi->nomor_seri_terkait)) {
            $serials = explode(',', $alokasi->nomor_seri_terkait);
            $saldoBerjalan = $saldoSebelumnya;
            foreach ($serials as $sn) {
                $saldoBerjalan--; // Kurangi satu per satu untuk setiap serial
                RiwayatPergerakanStok::create([
                    'id_produk' => $detailPenjualan->id_produk,
                    'id_stok_barang_terkait' => $stokBarang->id,
                    'nomor_seri' => trim($sn),
                    'tipe_transaksi' => 'PENJUALAN_PESANAN_BARANG',
                    'jumlah_masuk' => 0,
                    'jumlah_keluar' => 1,
                    'saldo_setelah_transaksi' => $saldoBerjalan,
                    'id_referensi' => $penjualan->id,
                    'tipe_referensi' => Penjualan::class,
                    'tanggal_transaksi' => now(),
                    'keterangan' => $keterangan,
                    'id_pengguna' => Auth::id(),
                ]);
            }
        } else { // Non-serial
            RiwayatPergerakanStok::create([
                'id_produk' => $detailPenjualan->id_produk,
                'id_stok_barang_terkait' => $stokBarang->id,
                'nomor_seri' => null,
                'tipe_transaksi' => 'PENJUALAN_PESANAN_BARANG',
                'jumlah_masuk' => 0,
                'jumlah_keluar' => $alokasi->jumlah_diambil,
                'saldo_setelah_transaksi' => $saldoSebelumnya - $alokasi->jumlah_diambil,
                'id_referensi' => $penjualan->id,
                'tipe_referensi' => Penjualan::class,
                'tanggal_transaksi' => now(),
                'keterangan' => $keterangan,
                'id_pengguna' => Auth::id(),
            ]);
        }
    }

    /**
     * Method helper untuk mengupdate data final di detail penjualan (garansi, dll).
     */
    private function updateDetailPenjualanFinal(Penjualan $penjualan)
    {
        foreach ($penjualan->detailPenjualan as $dp) {
            $produkDp = $dp->produk;
            $isKonsinyasiItem = false;
            $tipeGaransiTerpilih = 'NONE';

            // Ambil semua alokasi yang sudah final untuk item detail ini
            $alokasiFinal = $dp->stokAlokasi()->where('tipe_alokasi', 'STOK_KELUAR_PESANAN')->with('stokBarang')->get();
            
            foreach ($alokasiFinal as $alok) {
                if ($alok->stokBarang->tipe_stok === 'KONSINYASI') $isKonsinyasiItem = true;
                if ($alok->stokBarang->tipe_garansi === 'RESMI') $tipeGaransiTerpilih = 'RESMI';
                elseif ($tipeGaransiTerpilih !== 'RESMI' && $alok->stokBarang->tipe_garansi === 'SELF_SERVICE') {
                    $tipeGaransiTerpilih = 'SELF_SERVICE';
                }
            }

            // Siapkan data untuk diupdate
            $updateData = ['status_bayar_konsinyasi' => $isKonsinyasiItem ? 'BELUM_DIBAYAR_SUPPLIER' : 'BELUM_RELEVAN'];
            
            if ($tipeGaransiTerpilih === 'RESMI' && $produkDp->durasi_garansi_standar_bulan > 0) {
                $updateData['customer_garansi_mulai_at'] = now()->toDateString();
                $updateData['customer_garansi_berakhir_at'] = now()->addMonths($produkDp->durasi_garansi_standar_bulan)->toDateString();
            } elseif ($tipeGaransiTerpilih === 'SELF_SERVICE') {
                $updateData['customer_garansi_mulai_at'] = now()->toDateString();
                $updateData['customer_garansi_berakhir_at'] = now()->addWeeks(1)->toDateString();
            }
            
            $dp->update($updateData);
        }
    }
}