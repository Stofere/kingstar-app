<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\Supplier;
use App\Models\Produk;
use App\Models\StokBarang;
use App\Models\LogNomorSeri;
use App\Models\RiwayatPergerakanStok; 
use App\Http\Requests\StorePenerimaanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class PenerimaanController extends Controller
{
    /**
 * Menampilkan daftar PO yang menunggu penerimaan.
 * FINAL FIX for the 'Terima' button logic.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // OPTIMASI: Eager load semua relasi yang dibutuhkan di awal.
            $query = Pembelian::with(['supplier', 'detailPembelian'])
                ->whereIn('status_pembelian', ['DIPESAN', 'PENGIRIMAN', 'TIBA_SEBAGIAN', 'BARANG_PENGGANTI_RETUR']) // Tambah status baru
                ->whereHas('detailPembelian', function ($q) {
                    $q->whereRaw('jumlah > jumlah_diterima');
                })
                ->select('pembelian.*');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('supplier_nama', function ($row) {
                    // Aman karena 'supplier' sudah di-load
                    return $row->supplier->nama ?? '<span class="text-muted">N/A</span>';
                })
                ->editColumn('tanggal_pembelian_formatted', function ($row) {
                    return Carbon::parse($row->tanggal_pembelian)->isoFormat('D MMM YYYY');
                })
                ->addColumn('status_pembelian_badge', function ($row) {
                    $statusMapping = [
                        'DIPESAN' => ['class' => 'info', 'text' => 'Dipesan'],
                        'PENGIRIMAN' => ['class' => 'primary', 'text' => 'Pengiriman'],
                        'TIBA_SEBAGIAN' => ['class' => 'warning', 'text' => 'Tiba Sebagian'],
                        'BARANG_PENGGANTI_RETUR' => ['class' => 'success', 'text' => 'Pengganti Retur'],
                    ];
                    $statusInfo = $statusMapping[$row->status_pembelian] ?? ['class' => 'secondary', 'text' => str_replace('_', ' ', $row->status_pembelian)];
                    return '<span class="badge bg-' . $statusInfo['class'] . '">' . $statusInfo['text'] . '</span>';
                })
                ->addColumn('item_belum_diterima', function ($row) {
                    // Aman karena 'detailPembelian' sudah di-load
                    $belumDiterima = $row->detailPembelian->sum(function($detail) {
                        return $detail->jumlah - $detail->jumlah_diterima;
                    });
                    return $belumDiterima . ' unit';
                })
                ->addColumn('action', function ($row) {
                    $btnProsesPenerimaan = '';
                    $btnShowPO = '<a href="' . route('admin.pembelian.show', $row->id) . '" class="btn btn-info btn-sm" title="Lihat Detail PO" target="_blank"><i class="bi bi-eye"></i></a>';

                    // KARENA 'whereHas' SUDAH MEMASTIKAN HANYA PO DENGAN ITEM TERTENTU YANG MUNCUL,
                    // KITA TIDAK PERLU CEK LAGI. CUKUP CEK STATUSNYA SAJA.
                    // Jika statusnya bukan SELESAI atau DIBATALKAN, berarti masih bisa diproses.
                    if (!in_array($row->status_pembelian, ['SELESAI', 'DIBATALKAN'])) {
                        $btnProsesPenerimaan = '<a href="' . route('gudang.penerimaan.create', ['pembelian' => $row->id]) . '" class="btn btn-success btn-sm me-1" title="Proses Penerimaan Barang">                                                     <i class="bi bi-box-arrow-in-down"></i> Terima                                                 </a>';
                    }

                return $btnProsesPenerimaan . $btnShowPO;
                                })
                                ->rawColumns(['supplier_nama', 'status_pembelian_badge', 'action'])
                                ->make(true);
                        }
                        return view('gudang.penerimaan.index');
    }

    /**
     * Menampilkan form penerimaan.
     * REVISED to handle different 'tipe_penerimaan'.
     */
    public function create(Request $request, Pembelian $pembelian = null)
    {
        // Langkah 1: Cek apakah model $pembelian berhasil di-load dari URL
        if (!$pembelian || !$pembelian->exists) {
            // Jika Anda mengakses /penerimaan/create (tanpa ID), akan masuk ke sini.
            // Ini adalah penerimaan manual. Jika Anda mengakses /create/3 dan masuk ke sini,
            // berarti route model binding gagal.
            // dd('Mode Penerimaan Manual. Tidak ada PO yang dilempar dari route.');
        }

        // Jika kode berlanjut, berarti $pembelian berhasil di-load.

        // Langkah 2: Muat relasinya.
        // $pembelian->load(['detailPembelian.produk']);

        // Langkah 3: Tampilkan SEMUA data yang kita punya.
        // dd() akan menghentikan eksekusi dan menampilkan semua isi variabel $pembelian.
        // dd($pembelian->toArray()); capeee boss


        // =======================================================================
        // Kode di bawah ini tidak akan dieksekusi karena dd() di atas.
        // akan hapus dd() setelah menemukan masalahnya.
        // =======================================================================
        if (!$pembelian || !$pembelian->exists) {
            $tipe_penerimaan = 'MANUAL';
            $selectedPembelian = null;
            $detailItems = [];
        } else {
            // Jika ADA, tentukan tipe berdasarkan statusnya
            if ($pembelian->status_pembelian === 'BARANG_PENGGANTI_RETUR') {
                $tipe_penerimaan = 'RETUR';
            } else {
                $tipe_penerimaan = 'PO';
            }
            
            // =========================================================================
            // ## FIX FINAL: Cara yang lebih pasti untuk memuat dan memeriksa relasi ##
            // =========================================================================

            // 1. Muat relasi yang dibutuhkan
            $pembelian->load([
                'detailPembelian.produk:id,nama,kode_produk,memiliki_serial'
            ]);

            // 2. Filter koleksi detailPembelian yang sudah di-load di memori
            $itemsToReceive = $pembelian->detailPembelian->filter(function ($detail) {
                return $detail->jumlah > $detail->jumlah_diterima;
            });

            // 3. Cek apakah hasil filter ada isinya
            if ($itemsToReceive->isEmpty()) {
                return redirect()->route('gudang.penerimaan.index')->with('info', 'Semua item dari PO ' . $pembelian->nomor_pembelian . ' sudah diterima sepenuhnya.');
            }

            // 4. Jika ada isinya, siapkan data untuk view
            $selectedPembelian = $pembelian;
            $detailItems = [];
            foreach ($itemsToReceive as $detail) {
                if (!$detail->produk) continue;

                $sisaQty = $detail->jumlah - $detail->jumlah_diterima;
                $detailItems[] = [
                    'id_detail_pembelian' => $detail->id,
                    'id_produk' => $detail->id_produk,
                    'nama_produk' => $detail->produk->nama . ($detail->produk->kode_produk ? ' (' . $detail->produk->kode_produk . ')' : ''),
                    'memiliki_serial' => $detail->produk->memiliki_serial,
                    'jumlah_pesan' => $detail->jumlah,
                    'jumlah_sudah_diterima' => $detail->jumlah_diterima,
                    'jumlah_belum_diterima' => $sisaQty,
                    'jumlah_diterima_sekarang' => 0,
                ];
            }
        }

        // Data lain untuk view
        $suppliers = Supplier::where('status', true)->orderBy('nama')->pluck('nama', 'id');
        $lokasiPenyimpanan = ['GUDANG' => 'GUDANG', 'TOKO' => 'TOKO'];
        $tipeGaransi = ['NONE' => 'NONE', 'RESMI' => 'RESMI', 'SELF_SERVICE' => 'SELF_SERVICE'];
        $kondisiBarang = ['BAIK' => 'BAIK'];

        return view('gudang.penerimaan.create', compact(
            'suppliers', 'selectedPembelian', 'detailItems', 'lokasiPenyimpanan',
            'tipeGaransi', 'kondisiBarang', 'tipe_penerimaan'
        ));
    }

    public function store(StorePenerimaanRequest $request)
    {
        $validated = $request->validated();
        $diterimaAt = Carbon::parse($validated['diterima_at']);
        $tipePenerimaan = $validated['tipe_penerimaan'];
        $idPembelian = $validated['id_pembelian'] ?? null;
        $idSupplierUntukStok = null;

        if ($tipePenerimaan === 'PO' || $tipePenerimaan === 'RETUR') {
            $pembelianData = Pembelian::find($idPembelian);
            if ($pembelianData) {
                $idSupplierUntukStok = $pembelianData->id_supplier;
            }
        } elseif ($tipePenerimaan === 'MANUAL') {
            $idSupplierUntukStok = $validated['id_supplier_manual'] ?? null;
        }

        DB::beginTransaction();
        try {
            $adaItemDiterima = false;

            foreach ($validated['items'] as $itemData) {
                $jumlahDiterimaSekarang = (int)($itemData['jumlah_diterima_sekarang'] ?? 0);
                if ($jumlahDiterimaSekarang <= 0) continue;
                
                $adaItemDiterima = true;
                $produk = Produk::find($itemData['id_produk']);
                if (!$produk) throw new \Exception("Produk dengan ID {$itemData['id_produk']} tidak ditemukan.");

                $hargaBeliUntukStok = 0;
                $idDetailPembelian = $itemData['id_detail_pembelian'] ?? null;

                if ($idDetailPembelian) {
                    $detailPO = DetailPembelian::find($idDetailPembelian);
                    if ($detailPO) {
                        $hargaBeliUntukStok = $detailPO->harga_beli;
                    }
                }

                // Buat record stok baru
                $stokBarang = StokBarang::create([
                    'id_produk' => $produk->id, 'id_detail_pembelian' => $idDetailPembelian, 'id_supplier' => $idSupplierUntukStok,
                    'harga_beli' => $hargaBeliUntukStok, 'jumlah' => $jumlahDiterimaSekarang, 'diterima_at' => $diterimaAt,
                    'tipe_garansi' => $itemData['tipe_garansi'], 'tipe_stok' => 'REGULER', 'lokasi' => $itemData['lokasi'],
                    'kondisi' => $itemData['kondisi'],
                ]);

                // Update jumlah_diterima di detail_pembelian
                if ($idDetailPembelian) {
                    DetailPembelian::find($idDetailPembelian)->increment('jumlah_diterima', $jumlahDiterimaSekarang);
                }

                // Proses Log Nomor Seri (Logika lama Anda, biarkan saja)
                if ($produk->memiliki_serial && isset($itemData['nomor_seri']) && is_array($itemData['nomor_seri'])) {
                    foreach ($itemData['nomor_seri'] as $noSeri) {
                        $trimmedSn = trim($noSeri);
                        if (empty($trimmedSn)) { continue; }

                        // 1. Validasi baru: Cek status TERAKHIR dari nomor seri ini
                        $logTerakhir = LogNomorSeri::where('nomor_seri', $trimmedSn)->latest('tanggal_status')->first();

                        // 2. Daftar status yang menandakan barang masih "aktif" di dalam sistem
                        $statusAktif = ['DITERIMA', 'TERJUAL', 'DIRETUR_PELANGGAN', 'MASUK_STOK_RUSAK', 'KEMBALI_KE_STOK_BAIK_ADMIN'];

                        if ($logTerakhir && in_array($logTerakhir->status_log, $statusAktif)) {
                            throw new \Exception("Nomor Seri '{$trimmedSn}' sudah ada di sistem dengan status aktif '{$logTerakhir->status_log}'. Tidak bisa diterima lagi kecuali statusnya HILANG atau DIRETUR_SUPPLIER.");
                        }

                        // 3. Buat Log BARU dengan status 'DITERIMA'
                        LogNomorSeri::create([
                            'id_produk' => $stokBarang->id_produk,
                            'id_stok_barang_asal' => $stokBarang->id,
                            'nomor_seri' => $trimmedSn,
                            'status_log' => 'DITERIMA',
                            'tanggal_status' => $diterimaAt,
                            'id_referensi' => $idDetailPembelian,
                            'tipe_referensi' => DetailPembelian::class,
                            'catatan' => 'Diterima dari supplier.',
                        ]);
                    }
                }
                // =====================================================================
                // ## PENCATATAN BARU KE RIWAYAT PERGERAKAN STOK                      ##
                // =====================================================================
                $keteranganRiwayat = 'Penerimaan ' . ($pembelianData->nomor_pembelian ?? 'Manual');
                if ($produk->memiliki_serial && !empty($itemData['nomor_seri'])) {
                    foreach ($itemData['nomor_seri'] as $sn) {
                        $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->first();
                        $saldoSebelumnya = $saldoTerakhir->saldo_setelah_transaksi ?? 0;
                        RiwayatPergerakanStok::create([
                            'id_produk' => $produk->id, 'id_stok_barang_terkait' => $stokBarang->id,
                            'nomor_seri' => trim($sn), 'tipe_transaksi' => 'PENERIMAAN',
                            'jumlah_masuk' => 1, 'jumlah_keluar' => 0,
                            'saldo_setelah_transaksi' => $saldoSebelumnya + 1,
                            'id_referensi' => $idDetailPembelian, 'tipe_referensi' => DetailPembelian::class,
                            'tanggal_transaksi' => $diterimaAt, 'keterangan' => $keteranganRiwayat,
                            'id_pengguna' => Auth::id(),
                        ]);
                    }
                } else {
                    $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->first();
                    $saldoSebelumnya = $saldoTerakhir->saldo_setelah_transaksi ?? 0;
                    RiwayatPergerakanStok::create([
                        'id_produk' => $produk->id, 'id_stok_barang_terkait' => $stokBarang->id,
                        'tipe_transaksi' => 'PENERIMAAN', 'jumlah_masuk' => $jumlahDiterimaSekarang,
                        'jumlah_keluar' => 0,
                        'saldo_setelah_transaksi' => $saldoSebelumnya + $jumlahDiterimaSekarang,
                        'id_referensi' => $idDetailPembelian, 'tipe_referensi' => DetailPembelian::class,
                        'tanggal_transaksi' => $diterimaAt, 'keterangan' => $keteranganRiwayat,
                        'id_pengguna' => Auth::id(),
                    ]);
                }
                // =====================================================================
                // ## AKHIR PENCATATAN BARU                                         ##
                // =====================================================================
            } // End foreach

            if (!$adaItemDiterima) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tidak ada item yang diinput untuk diterima.')->withInput();
            }

            // Update status PO utama setelah semua item diproses
            if ($idPembelian) {
                $pembelianToUpdate = Pembelian::with('detailPembelian')->find($idPembelian);
                if ($pembelianToUpdate) {
                    $semuaDiterima = $pembelianToUpdate->detailPembelian->every(function ($detail) {
                        return $detail->jumlah <= $detail->jumlah_diterima;
                    });

                    if ($semuaDiterima) {
                        $pembelianToUpdate->status_pembelian = 'SELESAI';
                    } else {
                        $pembelianToUpdate->status_pembelian = 'TIBA_SEBAGIAN';
                    }
                    $pembelianToUpdate->save();
                }
            }

            DB::commit();
            return redirect()->route('gudang.penerimaan.index')->with('success', 'Penerimaan barang berhasil dicatat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * FUNGSI BARU: AJAX untuk mencari produk di form penerimaan manual.
     * Dapat diakses oleh Gudang.
     */
    public function searchProdukForPenerimaanAjax(Request $request)
    {
        $searchTerm = $request->input('q', '');
        $produk = Produk::where('status', true)
            ->where(function ($q) use ($searchTerm) {
                $q->where('nama', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('kode_produk', 'LIKE', "%{$searchTerm}%");
            })
            ->limit(20)->get(['id', 'nama', 'kode_produk', 'memiliki_serial']);

        $results = $produk->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->nama . ($item->kode_produk ? " ({$item->kode_produk})" : ""),
                'has_serial' => (bool)$item->memiliki_serial
            ];
        });

        return response()->json(['results' => $results]);
    }

}