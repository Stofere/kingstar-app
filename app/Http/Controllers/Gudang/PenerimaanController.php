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
        $tipeGaransi = ['NONE' => 'NONE', 'RESMI' => 'RESMI', 'SELF_SERVICE' => 'SELF SERVICE'];
        $kondisiBarang = ['BAIK' => 'BAIK', 'RUSAK' => 'RUSAK'];

        return view('gudang.penerimaan.create', compact(
            'suppliers', 'selectedPembelian', 'detailItems', 'lokasiPenyimpanan',
            'tipeGaransi', 'kondisiBarang', 'tipe_penerimaan'
        ));
    }

    public function store(StorePenerimaanRequest $request)
    {
        $validated = $request->validated();
        $diterimaAt = Carbon::parse($validated['diterima_at'], config('app.timezone'));
        $tipePenerimaan = $validated['tipe_penerimaan'];
        $idPembelian = $validated['id_pembelian'] ?? null;
        $idSupplierUntukStok = null;
        $pembelianData = null; // Inisialisasi variabel

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

                // ================================================================
                // === LOGIKA BARU UNTUK HARGA BELI & TIPE STOK ---
                // ================================================================
                $tipeStok = 'REGULER'; // Default untuk penerimaan dari PO
                $hargaBeliUntukStok = 0; // Default harga beli
                $idDetailPembelian = $itemData['id_detail_pembelian'] ?? null;

                if ($tipePenerimaan === 'MANUAL') {
                    $tipeStok = $itemData['tipe_stok'] ?? 'REGULER';
                    // Untuk penerimaan manual (baik REGULER atau KONSINYASI),
                    // harga beli akan 0 karena Gudang tidak menginputnya. Admin akan mengisi nanti.
                } else { // Untuk PO atau RETUR
                    if ($idDetailPembelian) {
                        $detailPO = DetailPembelian::find($idDetailPembelian);
                        if ($detailPO) {
                            $hargaBeliUntukStok = $detailPO->harga_beli;
                        }
                    }
                }
                // Hasilnya:
                // - Penerimaan PO: tipe_stok = REGULER, harga_beli = dari PO
                // - Penerimaan Manual Konsinyasi: tipe_stok = KONSINYASI, harga_beli = 0
                // - Penerimaan Manual Reguler: tipe_stok = REGULER, harga_beli = 0

                // Buat record stok baru
                $stokBarang = StokBarang::create([
                    'id_produk' => $produk->id,
                    'id_detail_pembelian' => $idDetailPembelian,
                    'id_supplier' => $idSupplierUntukStok,
                    'harga_beli' => $hargaBeliUntukStok, // << Logika baru diterapkan di sini
                    'jumlah' => $jumlahDiterimaSekarang,
                    'diterima_at' => $diterimaAt,
                    'tipe_garansi' => $itemData['tipe_garansi'],
                    'tipe_stok' => $tipeStok, // << Logika baru diterapkan di sini
                    'lokasi' => $itemData['lokasi'],
                    'kondisi' => $itemData['kondisi'],
                ]);
                // ================================================================
                // === AKHIR LOGIKA BARU ---
                // ================================================================

                // Update jumlah_diterima di detail_pembelian (tidak berubah)
                if ($idDetailPembelian) {
                    DetailPembelian::find($idDetailPembelian)->increment('jumlah_diterima', $jumlahDiterimaSekarang);
                }

                // --- AWAL PERBAIKAN LOGIKA SERIAL ---
                if ($produk->memiliki_serial && isset($itemData['nomor_seri']) && is_array($itemData['nomor_seri'])) {
                    foreach ($itemData['nomor_seri'] as $noSeri) {
                        $trimmedSn = trim($noSeri);
                        if (empty($trimmedSn)) continue;

                        // 1. Validasi BARU menggunakan riwayat_pergerakan_stok
                        $pergerakanTerakhir = RiwayatPergerakanStok::where('nomor_seri', $trimmedSn)
                                                                ->latest('tanggal_transaksi')
                                                                ->latest('id') // Untuk menangani waktu yang sama persis
                                                                ->first();
                        
                        // 2. Cek apakah serial ini boleh diterima
                        // Serial dianggap "tidak tersedia" jika log terakhirnya adalah transaksi MASUK.
                        if ($pergerakanTerakhir && $pergerakanTerakhir->jumlah_masuk > 0) {
                            throw new \Exception("Nomor Seri '{$trimmedSn}' sudah ada di sistem dengan status aktif (Tipe: {$pergerakanTerakhir->tipe_transaksi}). Tidak bisa diterima lagi.");
                        }
                    }
                }
                // --- AKHIR PERBAIKAN LOGIKA SERIAL ---

                $keteranganRiwayat = '';
                if ($tipePenerimaan === 'PO' || $tipePenerimaan === 'RETUR') {
                    $pembelianData->loadMissing('supplier');
                    $supplierNama = $pembelianData->supplier->nama ?? 'N/A';
                    $keteranganRiwayat = "Penerimaan barang dari Supplier {$supplierNama}";
                } elseif ($tipePenerimaan === 'MANUAL') {
                    if ($idSupplierUntukStok) {
                        $supplierManual = Supplier::find($idSupplierUntukStok);
                        $keteranganRiwayat = "Penerimaan manual dari " . ($supplierManual->nama ?? 'Supplier Tidak Diketahui');
                    } else {
                        $keteranganRiwayat = "Penerimaan manual (tanpa supplier)";
                    }
                }
                // --- AWAL PERUBAHAN TIPE TRANSAKSI ---
                $tipeStokItemIni = $itemData['tipe_stok'] ?? 'REGULER';
                $tipeTransaksiRiwayat = 'PENERIMAAN_PO'; // Default

                if ($tipePenerimaan === 'RETUR') {
                    $tipeTransaksiRiwayat = 'PENERIMAAN_PENGGANTI_RETUR';
                } elseif ($tipePenerimaan === 'MANUAL') {
                    if ($tipeStokItemIni === 'KONSINYASI') {
                        $tipeTransaksiRiwayat = 'PENERIMAAN_KONSINYASI'; // Tipe baru yang spesifik!
                    } else {
                        $tipeTransaksiRiwayat = 'PENERIMAAN_MANUAL';
                    }
                }
                // --- AKHIR PERUBAHAN TIPE TRANSAKSI ---

                $serialsDiterima = ($produk->memiliki_serial && !empty($itemData['nomor_seri'])) ? array_filter(array_map('trim', $itemData['nomor_seri'])) : [];
                $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->lockForUpdate()->latest('id')->value('saldo_setelah_transaksi') ?? 0;
                $saldoBerjalan = $saldoTerakhir;

                if (!empty($serialsDiterima)) {
                    foreach ($serialsDiterima as $sn) {
                        $saldoBerjalan += 1;
                        RiwayatPergerakanStok::create([
                            'id_produk' => $produk->id,
                            'id_stok_barang_terkait' => $stokBarang->id,
                            'nomor_seri' => $sn,
                            'tipe_transaksi' => $tipeTransaksiRiwayat,
                            'jumlah_masuk' => 1,
                            'jumlah_keluar' => 0,
                            'saldo_setelah_transaksi' => $saldoBerjalan,
                            'id_referensi' => $pembelianData->id ?? null,
                            'tipe_referensi' => $pembelianData ? Pembelian::class : null,
                            'tanggal_transaksi' => $diterimaAt,
                            'keterangan' => $keteranganRiwayat,
                            'id_pengguna' => Auth::id(),
                        ]);
                    }
                } else {
                    $saldoBerjalan += $jumlahDiterimaSekarang;
                    RiwayatPergerakanStok::create([
                        'id_produk' => $produk->id,
                        'id_stok_barang_terkait' => $stokBarang->id,
                        'nomor_seri' => null,
                        'tipe_transaksi' => $tipeTransaksiRiwayat,
                        'jumlah_masuk' => $jumlahDiterimaSekarang,
                        'jumlah_keluar' => 0,
                        'saldo_setelah_transaksi' => $saldoBerjalan,
                        'id_referensi' => $pembelianData->id ?? null,
                        'tipe_referensi' => $pembelianData ? Pembelian::class : null,
                        'tanggal_transaksi' => $diterimaAt,
                        'keterangan' => $keteranganRiwayat,
                        'id_pengguna' => Auth::id(),
                    ]);
                }
                // ## AKHIR PENCATATAN BARU ##
            } // End foreach

            if (!$adaItemDiterima) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tidak ada item yang diinput untuk diterima.')->withInput();
            }

            // ================================================================
            // === UPDATE STATUS PO UTAMA (LOGIKA BARU) ---
            // ================================================================
            if ($idPembelian) {
                // Kita perlu me-reload data pembelian untuk mendapatkan jumlah diterima terbaru
                $pembelianToUpdate = Pembelian::with('detailPembelian')->find($idPembelian);

                if ($pembelianToUpdate) {
                    $semuaItemSudahDiterima = $pembelianToUpdate->detailPembelian->every(function ($detail) {
                        return $detail->jumlah <= $detail->jumlah_diterima;
                    });

                    if ($semuaItemSudahDiterima) {
                        // Jika semua barang sudah diterima, cek status pembayaran
                        if ($pembelianToUpdate->status_pembayaran === 'LUNAS') {
                            // Jika sudah lunas, barulah transaksi benar-benar Selesai
                            $pembelianToUpdate->status_pembelian = 'SELESAI';
                        } else {
                            // Jika belum lunas, gunakan status baru yang lebih deskriptif
                            // Status ini menandakan bahwa dari segi barang sudah beres, tinggal bayar.
                            $pembelianToUpdate->status_pembelian = 'MENUNGGU_PEMBAYARAN';
                        }
                    } else {
                        // Jika belum semua diterima, statusnya tetap TIBA_SEBAGIAN
                        $pembelianToUpdate->status_pembelian = 'TIBA_SEBAGIAN';
                    }
                    $pembelianToUpdate->save();
                }
            }
            // ================================================================
            // === AKHIR UPDATE STATUS PO ---
            // ================================================================

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