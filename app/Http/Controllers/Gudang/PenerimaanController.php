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
use App\Models\ReturPembelian;

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
        // Inisialisasi variabel
        $sumberData = null; // Satu variabel untuk semua sumber data
        $tipe_penerimaan = 'MANUAL'; // Default

        // ### LOGIKA BARU UNTUK MENANGANI RETUR ###
        if ($request->has('id_retur_pembelian')) {
            $returPembelian = ReturPembelian::with(['supplier', 'detailReturPembelian.stokBarang.produk'])
                                            ->find($request->input('id_retur_pembelian'));
            
            if ($returPembelian) {
                $tipe_penerimaan = 'RETUR';
                
                // Ubah data retur ke format yang sama seperti PO
                $items = $returPembelian->detailReturPembelian->map(function($detail) {
                    if (!$detail->stokBarang || !$detail->stokBarang->produk) return null;
                    return (object)[
                        'id_detail_pembelian' => null, // Tidak ada detail PO
                        'id_produk' => $detail->stokBarang->id_produk,
                        'produk' => $detail->stokBarang->produk,
                        'jumlah_pesan' => $detail->jumlah_retur,
                        'jumlah_sudah_diterima' => 0,
                        'jumlah_belum_diterima' => $detail->jumlah_retur,
                        'harga_beli_awal' => $detail->stokBarang->harga_beli,
                    ];
                })->filter(); // Hapus item yang null

                $sumberData = (object)[
                    'id' => $returPembelian->id, // ID dari Retur Pembelian
                    'nomor_referensi' => $returPembelian->nomor_retur,
                    'supplier' => $returPembelian->supplier,
                    'items' => $items,
                ];
            }
        } 
        // ### LOGIKA LAMA UNTUK PO (TETAP SAMA) ###
        elseif ($pembelian && $pembelian->exists) {
            $pembelian->load('detailPembelian.produk', 'supplier');
            
            // Ubah data PO ke format yang sama
            $itemsToReceive = $pembelian->detailPembelian->filter(fn($d) => $d->jumlah > $d->jumlah_diterima);
            if ($itemsToReceive->isEmpty()) {
                return redirect()->route('gudang.penerimaan.index')->with('info', 'Semua item dari PO sudah diterima.');
            }

            $items = $itemsToReceive->map(function($detail) {
                if (!$detail->produk) return null;
                return (object)[
                    'id_detail_pembelian' => $detail->id,
                    'id_produk' => $detail->id_produk,
                    'produk' => $detail->produk,
                    'jumlah_pesan' => $detail->jumlah,
                    'jumlah_sudah_diterima' => $detail->jumlah_diterima,
                    'jumlah_belum_diterima' => $detail->jumlah - $detail->jumlah_diterima,
                    'harga_beli_awal' => $detail->harga_beli,
                ];
            })->filter();

            $sumberData = (object)[
                'id' => $pembelian->id, // ID dari Pembelian
                'nomor_referensi' => $pembelian->nomor_pembelian,
                'supplier' => $pembelian->supplier,
                'items' => $items,
            ];

            $tipe_penerimaan = 'PO';
        }

        // Data lain untuk view (tetap sama)
        $suppliers = Supplier::where('status', true)->orderBy('nama')->pluck('nama', 'id');
        $lokasiPenyimpanan = ['GUDANG' => 'GUDANG', 'TOKO' => 'TOKO'];
        $tipeGaransi = ['NONE' => 'NONE', 'RESMI' => 'RESMI', 'SELF_SERVICE' => 'SELF SERVICE'];
        $kondisiBarang = ['BAIK' => 'BAIK', 'RUSAK' => 'RUSAK'];

        return view('gudang.penerimaan.create', compact(
        'suppliers', 
        'sumberData', 
        'tipe_penerimaan',
        'lokasiPenyimpanan',
        'tipeGaransi',
        'kondisiBarang'
    ));
    }

    public function store(StorePenerimaanRequest $request)
    {
        $validated = $request->validated();
        $diterimaAt = Carbon::parse($validated['diterima_at'], config('app.timezone'));
        $tipePenerimaan = $validated['tipe_penerimaan'];

        // Inisialisasi variabel referensi
        $pembelianRef = null;
        $returPembelianRef = null;
        $idSupplierUntukStok = null;

        // Tentukan model referensi dan supplier berdasarkan tipe penerimaan
        if ($tipePenerimaan === 'PO') {
            $pembelianRef = Pembelian::find($validated['id_pembelian']);
            $idSupplierUntukStok = $pembelianRef->id_supplier ?? null;
        } elseif ($tipePenerimaan === 'RETUR') {
            $returPembelianRef = ReturPembelian::find($validated['id_retur_pembelian_ref']);
            $idSupplierUntukStok = $returPembelianRef->id_supplier_tujuan ?? null;
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

                // Tentukan tipe stok dan harga beli
                $tipeStok = 'REGULER'; // Default
                $hargaBeliUntukStok = $itemData['harga_beli'] ?? 0;
                $idDetailPembelian = $itemData['id_detail_pembelian'] ?? null;

                if ($tipePenerimaan === 'MANUAL') {
                    $tipeStok = $itemData['tipe_stok'] ?? 'REGULER';
                    // Harga beli untuk manual (termasuk konsinyasi) diinput oleh Admin nanti, jadi 0 di sini.
                    $hargaBeliUntukStok = 0;

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
                    'harga_beli' => $hargaBeliUntukStok, 
                    'jumlah' => $jumlahDiterimaSekarang,
                    'diterima_at' => $diterimaAt,
                    'tipe_garansi' => $itemData['tipe_garansi'],
                    'tipe_stok' => $tipeStok, 
                    'lokasi' => $itemData['lokasi'],
                    'kondisi' => $itemData['kondisi'],
                ]);
                
                // Update jumlah_diterima HANYA jika berasal dari PO
                if ($idDetailPembelian) {
                    DetailPembelian::find($idDetailPembelian)->increment('jumlah_diterima', $jumlahDiterimaSekarang);
                }

                // --- AWAL PERBAIKAN LOGIKA SERIAL ---
                if ($produk->memiliki_serial && isset($itemData['nomor_seri']) && is_array($itemData['nomor_seri'])) {
                    foreach ($itemData['nomor_seri'] as $noSeri) {
                        $trimmedSn = trim($noSeri);
                        if (empty($trimmedSn)) continue;

                        // 1. Dapatkan log pergerakan terakhir untuk nomor seri ini
                        $pergerakanTerakhir = RiwayatPergerakanStok::where('nomor_seri', $trimmedSn)
                                                                ->orderBy('id', 'desc')
                                                                ->first();

                        
                        // 2. Terapkan logika validasi yang lebih cerdas
                        if ($pergerakanTerakhir) {
                            // Kondisi DITOLAK: Jika log terakhir adalah transaksi MASUK (stok bertambah)
                            // DAN jenisnya BUKAN Retur ke Supplier. Artinya, serial ini masih aktif di gudang.
                            if ($pergerakanTerakhir->jumlah_masuk > 0 && $pergerakanTerakhir->tipe_transaksi !== 'RETUR_KE_SUPPLIER') {
                                throw new \Exception("Nomor Seri '{$trimmedSn}' sudah ada di sistem dengan status aktif (Tipe: {$pergerakanTerakhir->tipe_transaksi}). Tidak bisa diterima lagi.");
                            }
                            // Jika log terakhir adalah KELUAR (misal: PENJUALAN) atau MASUK karena RETUR_KE_SUPPLIER (yang statusnya keluar), maka serial ini boleh diterima lagi.
                        }
                    }
                }
                // --- AKHIR PERBAIKAN LOGIKA SERIAL ---

                // ### PERBAIKAN LOGIKA RIWAYAT PERGERAKAN STOK ###
                $referensiModel = $pembelianRef ?? $returPembelianRef; // Tentukan model referensi
                $keteranganRiwayat = 'Penerimaan manual.'; // Default
                $tipeTransaksiRiwayat = 'PENERIMAAN_MANUAL'; // Default
                
                if ($referensiModel instanceof Pembelian) {
                    $keteranganRiwayat = 'Penerimaan dari PO: ' . $referensiModel->nomor_pembelian;
                    $tipeTransaksiRiwayat = 'PENERIMAAN_PO';
                } elseif ($referensiModel instanceof ReturPembelian) {
                    $keteranganRiwayat = 'Penerimaan barang pengganti untuk Retur No: ' . $referensiModel->nomor_retur;
                    $tipeTransaksiRiwayat = 'PENERIMAAN_PENGGANTI_RETUR';
                } elseif ($tipePenerimaan === 'MANUAL') {
                    $supplierManual = $idSupplierUntukStok ? Supplier::find($idSupplierUntukStok) : null;
                    $keteranganRiwayat = "Penerimaan manual dari " . ($supplierManual->nama ?? 'N/A');
                    $tipeTransaksiRiwayat = ($itemData['tipe_stok'] === 'KONSINYASI') ? 'PENERIMAAN_KONSINYASI' : 'PENERIMAAN_MANUAL';
                }

                // Pencatatan ke riwayat
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
                            'id_referensi' => $referensiModel ? $referensiModel->id : null,
                            'tipe_referensi' => $referensiModel ? get_class($referensiModel) : null,
                            'tanggal_transaksi' => $diterimaAt,
                            'keterangan' => $keteranganRiwayat,
                            'id_pengguna' => Auth::id(),
                        ]);
                    }
                } else { // Non-serial
                    $saldoBerjalan += $jumlahDiterimaSekarang;
                    RiwayatPergerakanStok::create([
                        'id_produk' => $produk->id,
                        'id_stok_barang_terkait' => $stokBarang->id,
                        'nomor_seri' => null,
                        'tipe_transaksi' => $tipeTransaksiRiwayat,
                        'jumlah_masuk' => $jumlahDiterimaSekarang,
                        'jumlah_keluar' => 0,
                        'saldo_setelah_transaksi' => $saldoBerjalan,
                        'id_referensi' => $referensiModel ? $referensiModel->id : null,
                        'tipe_referensi' => $referensiModel ? get_class($referensiModel) : null,
                        'tanggal_transaksi' => $diterimaAt,
                        'keterangan' => $keteranganRiwayat,
                        'id_pengguna' => Auth::id(),
                    ]);
                }
            } // End foreach

            if (!$adaItemDiterima) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tidak ada item yang diinput untuk diterima.')->withInput();
            }

            // ================================================================
            // === UPDATE STATUS PO UTAMA ---
            // ================================================================
            // Update status PO utama jika berasal dari PO (logika ini tetap sama)
            if ($pembelianRef) {
                $pembelianRef->refresh(); // Reload data dari DB
                $semuaItemSudahDiterima = $pembelianRef->detailPembelian->every(fn($detail) => $detail->jumlah <= $detail->jumlah_diterima);
                if ($semuaItemSudahDiterima) {
                    $pembelianRef->status_pembelian = 'SELESAI';
                } else {
                    $pembelianRef->status_pembelian = 'TIBA_SEBAGIAN';
                }
                $pembelianRef->save();
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