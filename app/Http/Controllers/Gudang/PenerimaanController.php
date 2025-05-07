<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\Supplier;
use App\Models\Produk;
use App\Models\StokBarang;
use App\Models\LogNomorSeri;
use App\Http\Requests\StorePenerimaanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class PenerimaanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Pembelian::with(['supplier', 'detailPembelian'])
                ->whereIn('status_pembelian', ['DIPESAN', 'PENGIRIMAN', 'TIBA_SEBAGIAN'])
                ->whereHas('detailPembelian', function ($q) {
                    $q->whereRaw('jumlah > jumlah_diterima');
                })
                ->select('pembelian.*');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('supplier_nama', function ($row) {
                    return $row->supplier->nama ?? '<span class="text-muted">N/A</span>';
                })
                ->editColumn('tanggal_pembelian_formatted', function ($row) {
                    return Carbon::parse($row->tanggal_pembelian)->isoFormat('D MMM YYYY');
                })
                ->addColumn('status_pembelian_badge', function ($row) {
                    $statusClass = 'secondary'; // Default
                    if ($row->status_pembelian == 'DIPESAN') $statusClass = 'info';
                    elseif ($row->status_pembelian == 'PENGIRIMAN') $statusClass = 'primary';
                    elseif ($row->status_pembelian == 'TIBA_SEBAGIAN') $statusClass = 'warning';
                    return '<span class="badge bg-' . $statusClass . '">' . str_replace('_', ' ', $row->status_pembelian) . '</span>';
                })
                ->addColumn('item_belum_diterima', function ($row) {
                    $totalDipesan = $row->detailPembelian->sum('jumlah');
                    $totalSudahDiterima = $row->detailPembelian->sum('jumlah_diterima');
                    $belumDiterima = $totalDipesan - $totalSudahDiterima;
                    return $belumDiterima . ' unit';
                })
                ->addColumn('action', function ($row) {
                    $btnProsesPenerimaan = '';
                    $masihBisaDiterima = $row->detailPembelian()->whereRaw('jumlah > jumlah_diterima')->exists();

                    if ($masihBisaDiterima) {
                        $btnProsesPenerimaan = '<a href="' . route('gudang.penerimaan.create', ['pembelian' => $row->id]) . '" class="btn btn-success btn-sm me-1" title="Proses Penerimaan Barang">
                                                    <i class="bi bi-box-arrow-in-down"></i> Terima
                                                </a>';
                    }
                    $btnShowPO = '<a href="' . route('admin.pembelian.show', $row->id) . '" class="btn btn-info btn-sm" title="Lihat Detail PO"><i class="bi bi-eye"></i></a>';

                    return $btnProsesPenerimaan . $btnShowPO;
                })
                ->rawColumns(['supplier_nama', 'status_pembelian_badge', 'action'])
                ->make(true);
        }
        return view('gudang.penerimaan.index');
    }

    /**
     * Show the form for creating a new resource.
     * Can optionally accept a Pembelian model via route model binding.
     *
     * @param Pembelian|null $pembelian
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function create(Pembelian $pembelian = null)
    {
        $suppliers = Supplier::where('status', true)->orderBy('nama')->pluck('nama', 'id');
        $selectedPembelian = null;
        $detailItems = [];
        $tipe_penerimaan = 'MANUAL'; // Default

        if ($pembelian) {
            // 1. Load relasi detailPembelian dengan constraint
            $pembelian->load(['detailPembelian' => function ($query) {
                $query->whereRaw('jumlah > jumlah_diterima');
            }]);

            // $pembelian->detailPembelian sekarang hanya berisi item yang belum diterima sepenuhnya.
            // 2. Eager load 'produk' untuk detailPembelian yang sudah terfilter tersebut.
            if ($pembelian->detailPembelian->isNotEmpty()) {
                $pembelian->detailPembelian->load('produk'); // Load produk untuk koleksi detailPembelian
            }

            // Cek apakah setelah filtering masih ada detail pembelian yang valid
            if ($pembelian->detailPembelian->isNotEmpty()) {
                $selectedPembelian = $pembelian;
                $tipe_penerimaan = 'PO';

                foreach ($pembelian->detailPembelian as $detail) {
                    // $detail sekarang adalah item dari detailPembelian yang jumlah > jumlah_diterima
                    // dan relasi produk-nya sudah dimuat.
                    if (!$detail->produk) {
                        // Kasus jika produk terkait dengan detail ini terhapus atau tidak valid.
                        // Anda bisa log error ini atau skip item ini.
                        // Log::warning("Produk tidak ditemukan untuk DetailPembelian ID: {$detail->id} pada Pembelian ID: {$pembelian->id}");
                        continue;
                    }

                    $sisaQty = $detail->jumlah - $detail->jumlah_diterima;
                    // Sebenarnya kondisi $sisaQty > 0 sudah dijamin oleh constraint eager loading,
                    // tapi tidak ada salahnya dicek lagi untuk keamanan.
                    if ($sisaQty > 0) {
                        $detailItems[] = [
                            'id_detail_pembelian' => $detail->id,
                            'id_produk' => $detail->id_produk,
                            'nama_produk' => $detail->produk->nama . ($detail->produk->kode_produk ? ' (' . $detail->produk->kode_produk . ')' : ''),
                            'memiliki_serial' => $detail->produk->memiliki_serial,
                            'jumlah_pesan' => $detail->jumlah,
                            'jumlah_sudah_diterima' => $detail->jumlah_diterima,
                            'jumlah_belum_diterima' => $sisaQty,
                            'jumlah_diterima_sekarang' => 0, // Default ke 0, gudang input manual
                        ];
                    }
                }

                // Jika setelah iterasi tidak ada item valid yang bisa diproses (misal karena produk hilang)
                if (empty($detailItems)) {
                    return redirect()->route('gudang.penerimaan.index')->with('info', 'Tidak ada item yang valid untuk diterima dari PO ' . $pembelian->nomor_pembelian . '. Periksa data produk terkait.');
                }
            } else {
                 // Jika semua item dari PO ini sudah diterima sepenuhnya (hasil dari $pembelian->load(['detailPembelian' => ...]) kosong)
                return redirect()->route('gudang.penerimaan.index')->with('info', 'Semua item dari PO ' . $pembelian->nomor_pembelian . ' sudah diterima sepenuhnya.');
            }
        }

        $lokasiPenyimpanan = ['GUDANG' => 'GUDANG', 'TOKO' => 'TOKO'];
        $tipeGaransi = ['NONE' => 'NONE', 'RESMI' => 'RESMI', 'SELF_SERVICE' => 'SELF_SERVICE'];
        $kondisiBarang = [
            'BAIK' => 'BAIK',
            'RUSAK_MINOR' => 'RUSAK MINOR',
            'RUSAK' => 'RUSAK',
            'SERVIS' => 'SERVIS',
            'KOMPLAIN_SUPPLIER' => 'KOMPLAIN SUPPLIER',
        ];

        return view('gudang.penerimaan.create', compact(
            'suppliers',
            'selectedPembelian',
            'detailItems',
            'lokasiPenyimpanan',
            'tipeGaransi',
            'kondisiBarang',
            'tipe_penerimaan'
        ));
    }

    public function store(StorePenerimaanRequest $request)
    {
        $validated = $request->validated();
        $diterimaAt = Carbon::parse($validated['diterima_at']);
        $tipePenerimaan = $validated['tipe_penerimaan'];
        $idPembelian = $validated['id_pembelian'] ?? null;
        $idSupplierUntukStok = null;

        if ($tipePenerimaan === 'PO' && $idPembelian) {
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

                if ($jumlahDiterimaSekarang <= 0) {
                    continue;
                }
                $adaItemDiterima = true;

                $produk = Produk::find($itemData['id_produk']);
                if (!$produk) {
                    throw new \Exception("Produk dengan ID {$itemData['id_produk']} tidak ditemukan.");
                }

                $hargaBeliUntukStok = 0;
                $idDetailPembelian = null;

                if ($tipePenerimaan === 'PO' && isset($itemData['id_detail_pembelian'])) {
                    $detailPO = DetailPembelian::find($itemData['id_detail_pembelian']);
                    if ($detailPO) {
                        $hargaBeliUntukStok = $detailPO->harga_beli;
                        $idDetailPembelian = $detailPO->id;
                    }
                }

                $stokBarang = StokBarang::create([
                    'id_produk' => $produk->id,
                    'id_detail_pembelian' => $idDetailPembelian,
                    'id_supplier' => $idSupplierUntukStok,
                    'harga_beli' => $hargaBeliUntukStok,
                    'jumlah' => $jumlahDiterimaSekarang,
                    'diterima_at' => $diterimaAt,
                    'tipe_garansi' => $itemData['tipe_garansi'],
                    'tipe_stok' => 'REGULER',
                    'lokasi' => $itemData['lokasi'],
                    'kondisi' => $itemData['kondisi'],
                ]);

                if ($idDetailPembelian) {
                    $detailPOToUpdate = DetailPembelian::find($idDetailPembelian);
                    if ($detailPOToUpdate) {
                        $detailPOToUpdate->increment('jumlah_diterima', $jumlahDiterimaSekarang);
                    }
                }

                if ($produk->memiliki_serial && isset($itemData['nomor_seri']) && is_array($itemData['nomor_seri'])) {
                    foreach ($itemData['nomor_seri'] as $noSeri) {
                        if (!empty(trim($noSeri))) {
                            $existingSerial = LogNomorSeri::where('id_produk', $produk->id)
                                                          ->where('nomor_seri', trim($noSeri))
                                                          ->first();
                            if ($existingSerial && !in_array($existingSerial->status_log, ['DIRETUR_SUPPLIER', 'HILANG'])) {
                                DB::rollBack();
                                return redirect()->back()
                                    ->with('error', "Nomor Seri '{$noSeri}' untuk produk '{$produk->nama}' sudah ada di sistem dengan status '{$existingSerial->status_log}'.")
                                    ->withInput();
                            }

                            LogNomorSeri::create([
                                'id_produk' => $stokBarang->id_produk,
                                'id_stok_barang_asal' => $stokBarang->id,
                                'nomor_seri' => trim($noSeri),
                                'status_log' => 'DITERIMA',
                                'tanggal_status' => $diterimaAt,
                            ]);
                        }
                    }
                }
            }

            if (!$adaItemDiterima) {
                DB::rollBack();
                return redirect()->back()
                                 ->with('error', 'Tidak ada item yang diinput untuk diterima.')
                                 ->withInput();
            }

            if ($tipePenerimaan === 'PO' && $idPembelian) {
                $pembelianToUpdate = Pembelian::with('detailPembelian')->find($idPembelian);
                if ($pembelianToUpdate) {
                    $semuaDiterima = true;
                    $adaYangDiterimaSebagian = false;
                    foreach ($pembelianToUpdate->detailPembelian as $detail) {
                        if ($detail->jumlah > $detail->jumlah_diterima) {
                            $semuaDiterima = false;
                        }
                        if ($detail->jumlah_diterima > 0 && $detail->jumlah_diterima < $detail->jumlah) {
                            $adaYangDiterimaSebagian = true;
                        }
                    }

                    if ($semuaDiterima) {
                        $pembelianToUpdate->status_pembelian = 'SELESAI';
                    } elseif ($pembelianToUpdate->detailPembelian()->where('jumlah_diterima', '>', 0)->exists() || $adaYangDiterimaSebagian) {
                        if ($pembelianToUpdate->status_pembelian !== 'SELESAI') {
                             $pembelianToUpdate->status_pembelian = 'TIBA_SEBAGIAN';
                        }
                    }
                    $pembelianToUpdate->save();
                }
            }

            DB::commit();
            return redirect()->route('gudang.penerimaan.index')
                             ->with('success', 'Penerimaan barang berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Error store penerimaan: '. $e->getMessage() . ' - Trace: ' . $e->getTraceAsString()); // Tambahkan trace untuk debug
            return redirect()->back()
                             ->with('error', 'Terjadi kesalahan saat mencatat penerimaan: ' . $e->getMessage())
                             ->withInput();
        }
    }
}