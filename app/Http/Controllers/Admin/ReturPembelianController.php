<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturPembelian;
use App\Models\StokBarang;
use App\Models\LogNomorSeri;
use App\Models\Supplier; 
use App\Models\Produk;   
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class ReturPembelianController extends Controller
{
    // ... (method index, show, dll ) ...

    /**
     * Menampilkan form untuk membuat retur pembelian baru.
     */
    public function create()
    {
        // Data untuk dropdown di form (opsional, tergantung desain form Anda)
        // $suppliers = Supplier::where('status', true)->orderBy('nama')->pluck('nama', 'id');
        // $produkList = Produk::where('status', true)->orderBy('nama')->pluck('nama', 'id'); // Mungkin lebih baik AJAX

        $alasanReturOptions = [
            'BARANG_RUSAK_DARI_SUPPLIER' => 'Barang Rusak dari Supplier (Saat Terima/Cek)',
            'SALAH_KIRIM_SUPPLIER' => 'Salah Kirim Barang oleh Supplier',
            'KELEBIHAN_KIRIM_SUPPLIER' => 'Kelebihan Kirim oleh Supplier',
            'KUALITAS_TIDAK_SESUAI' => 'Kualitas Tidak Sesuai Pesanan',
            'RETUR_PELANGGAN_CACAT_PRODUKSI' => 'Retur Pelanggan (Cacat Produksi dari Supplier)',
            'LAINNYA' => 'Lainnya',
        ];

        // Ini status yang akan kita ajukan/harapkan dari supplier
        $tindakanLanjutSupplierOptions = [
            'MENUNGGU_RESPONS_SUPPLIER' => 'Menunggu Respons Supplier',
            'PROSES_PENGGANTIAN_BARANG' => 'Proses Penggantian Barang',
            'PROSES_REFUND_UANG' => 'Proses Refund Uang',
            // Status "SELESAI_DIGANTI", "SELESAI_DIREFUND", "DITOLAK_SUPPLIER" akan diupdate nanti
        ];


        return view('admin.retur_pembelian.create', compact('alasanReturOptions', 'tindakanLanjutSupplierOptions'));
    }

    /**
     * AJAX untuk mencari batch StokBarang yang bisa diretur.
     * Bisa dicari berdasarkan ID Produk, Nama Produk, Kode Produk, ID Batch, atau Nama Supplier.
     */
    public function searchBatchStokAjax(Request $request)
    {
        $searchTerm = $request->input('q', '');
        $page = $request->input('page', 1);
        $limit = 15;

        $query = StokBarang::with(['produk', 'supplier'])
            ->where('jumlah', '>', 0) // Hanya batch yang masih ada stoknya
            // ->where('kondisi', '!=', 'DIRETUR_KE_SUPPLIER') // Contoh jika ada kondisi khusus
            ->where(function ($q) use ($searchTerm) {
                $q->where('id', 'LIKE', "%{$searchTerm}%") // Cari berdasarkan ID Batch
                  ->orWhereHas('produk', function ($prodQ) use ($searchTerm) {
                      $prodQ->where('nama', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('kode_produk', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('supplier', function ($supQ) use ($searchTerm) {
                      $supQ->where('nama', 'LIKE', "%{$searchTerm}%");
                  });
            });

        $batches = $query->orderBy('diterima_at', 'desc')->orderBy('id', 'desc') // Batch terbaru dulu
                         ->paginate($limit);

        $results = $batches->map(function ($batch) {
            $produkText = $batch->produk ? ($batch->produk->nama . ($batch->produk->kode_produk ? " ({$batch->produk->kode_produk})" : "")) : "Produk Tidak Diketahui";
            $supplierText = $batch->supplier ? $batch->supplier->nama : "Supplier Tidak Diketahui";
            $text = "Batch ID: {$batch->id} - {$produkText} | Supplier: {$supplierText} | Terima: " . Carbon::parse($batch->diterima_at)->isoFormat('D MMM YYYY') . " | Sisa: {$batch->jumlah}";
            return [
                'id' => $batch->id, // ID StokBarang (batch)
                'text' => $text,
                'id_produk' => $batch->id_produk,
                'nama_produk' => $produkText,
                'memiliki_serial' => $batch->produk ? (bool)$batch->produk->memiliki_serial : false,
                'sisa_stok_batch' => $batch->jumlah,
                'id_supplier' => $batch->id_supplier, // Untuk info di form
                'nama_supplier' => $supplierText,   // Untuk info di form
            ];
        });

        return response()->json([
            'items' => $results,
            'total_count' => $batches->total()
        ]);
    }

    /**
     * AJAX untuk mendapatkan nomor seri yang tersedia (status DITERIMA) dari satu batch.
     */
    public function getSerialsFromBatchAjax(Request $request)
    {
        $request->validate(['id_stok_barang' => 'required|integer|exists:stok_barang,id']);
        $idStokBarang = $request->input('id_stok_barang');

        $batch = StokBarang::find($idStokBarang);
        if (!$batch || !$batch->produk || !$batch->produk->memiliki_serial) {
            return response()->json(['success' => false, 'serials' => [], 'message' => 'Batch tidak ditemukan atau produk tidak berserial.']);
        }

        // Ambil serial yang statusnya DITERIMA dan berasal dari batch ini,
        // DAN belum pernah diretur ke supplier dari batch ini
        $serialsSudahDireturDariBatchIni = ReturPembelian::where('id_stok_barang', $idStokBarang)
                                            ->whereNotNull('nomor_seri_diretur')
                                            ->pluck('nomor_seri_diretur')
                                            ->flatMap(fn($s) => array_map('trim', explode(',', $s)))
                                            ->unique()->values()->all();

        $serialsTersedia = LogNomorSeri::where('id_stok_barang_asal', $idStokBarang)
                                ->where('status_log', 'DITERIMA') // Hanya yang statusnya masih di stok
                                ->whereNotIn('nomor_seri', $serialsSudahDireturDariBatchIni) // Kecualikan yang sudah diretur dari batch ini
                                ->pluck('nomor_seri')
                                ->toArray();

        return response()->json(['success' => true, 'serials' => $serialsTersedia]);
    }
    
    /**
     * Menyimpan data retur pembelian baru.
     */
    public function store(Request $request)
    {
       

        $validated = $request->validate([
            'tanggal_retur' => 'required|date_format:Y-m-d\TH:i',
            'id_supplier_tujuan' => 'required|integer|exists:supplier,id', // Supplier tujuan harus ada
            'items_retur' => 'required|array|min:1',
            'items_retur.*.id_stok_barang' => ['required', 'integer', Rule::exists('stok_barang', 'id')],
            'items_retur.*.id_produk_retur' => ['required', 'integer', Rule::exists('produk', 'id')], // Untuk validasi tambahan
            'items_retur.*.jumlah_retur' => 'required|integer|min:1',
            'items_retur.*.alasan_retur' => 'required|string|max:255',
            'items_retur.*.tindakan_lanjut_supplier' => 'required|string|max:100',
            'items_retur.*.nomor_seri_diretur' => 'nullable|array',
            'items_retur.*.nomor_seri_diretur.*' => 'nullable|string|max:255',
            'items_retur.*.catatan_ke_supplier_item' => 'nullable|string|max:500',
            'catatan_global_retur_pembelian' => 'nullable|string|max:1000',
        ], [
            'id_supplier_tujuan.required' => 'Supplier tujuan retur wajib dipilih (biasanya terisi otomatis dari batch pertama).',
            'items_retur.required' => 'Minimal harus ada satu item/batch yang diretur.',
            'items_retur.*.id_stok_barang.required' => 'Batch stok wajib dipilih untuk setiap item retur.',
            'items_retur.*.id_produk_retur.required' => 'ID Produk pada item retur tidak valid.',
            'items_retur.*.jumlah_retur.min' => 'Jumlah retur minimal 1 untuk item yang dipilih.',
            'items_retur.*.alasan_retur.required' => 'Alasan retur wajib diisi untuk setiap item.',
            'items_retur.*.tindakan_lanjut_supplier.required' => 'Tindak lanjut diharapkan dari supplier wajib dipilih.',
        ]);

      

        DB::beginTransaction();
       
        try {
            $tanggalRetur = Carbon::parse($validated['tanggal_retur']);
            $nomorReturPembelianOtomatis = $this->generateNextReturPembelianNumber($tanggalRetur);
           

            $adaItemYangValidDiretur = false;

            foreach ($validated['items_retur'] as $index => $itemReturData) {
              

                $jumlahReturSaatIni = (int)($itemReturData['jumlah_retur'] ?? 0);
                if ($jumlahReturSaatIni <= 0) {
                  
                    continue;
                }
                $adaItemYangValidDiretur = true;

                $stokBarang = StokBarang::with('produk')->lockForUpdate()->find($itemReturData['id_stok_barang']);
                if (!$stokBarang) {
                    throw new \Exception("Batch Stok dengan ID {$itemReturData['id_stok_barang']} tidak ditemukan.");
                }
                // Validasi tambahan: pastikan id_produk dari form cocok dengan id_produk di batch
                if ($stokBarang->id_produk != $itemReturData['id_produk_retur']) {
                    throw new \Exception("Produk pada Batch ID {$stokBarang->id} tidak cocok dengan produk yang dipilih untuk diretur.");
                }
                // Validasi supplier: pastikan batch ini milik supplier tujuan
                if ($stokBarang->id_supplier != $validated['id_supplier_tujuan']) {
                    // Ini bisa jadi warning atau error tergantung kebijakan. Untuk sekarang kita log saja.
                  
                }


                $produk = $stokBarang->produk; // Produk dari batch yang akan diretur
                

                if ($jumlahReturSaatIni > $stokBarang->jumlah) {
                    throw new \Exception("Jumlah retur ({$jumlahReturSaatIni}) untuk produk '{$produk->nama}' dari Batch ID {$stokBarang->id} melebihi sisa stok ({$stokBarang->jumlah}).");
                }

                $serialsDireturInputCleaned = collect($itemReturData['nomor_seri_diretur'] ?? [])->map(fn($sn) => trim($sn))->filter()->unique()->values()->all();
                

                if ($produk->memiliki_serial && count($serialsDireturInputCleaned) !== $jumlahReturSaatIni) {
                    throw new \Exception("Jumlah nomor seri (".count($serialsDireturInputCleaned).") tidak sesuai jumlah retur ({$jumlahReturSaatIni}) untuk produk '{$produk->nama}' dari Batch ID {$stokBarang->id}.");
                }

                // Validasi serial yang diretur harus ada di batch tersebut dan statusnya 'DITERIMA'
                if ($produk->memiliki_serial && !empty($serialsDireturInputCleaned)) {
                    $serialsSudahDireturDariBatchIni = ReturPembelian::where('id_stok_barang', $stokBarang->id)
                                                        ->whereNotNull('nomor_seri_diretur')
                                                        ->pluck('nomor_seri_diretur')
                                                        ->flatMap(fn($s) => array_map('trim', explode(',', $s)))
                                                        ->unique()->values()->all();

                    foreach ($serialsDireturInputCleaned as $snRetur) {
                        $logSeri = LogNomorSeri::where('id_stok_barang_asal', $stokBarang->id)
                                            ->where('nomor_seri', $snRetur)
                                            ->where('status_log', 'DITERIMA') // Pastikan statusnya masih DITERIMA di batch ini
                                            ->first();
                        if (!$logSeri) {
                            throw new \Exception("Nomor Seri '{$snRetur}' tidak ditemukan dengan status 'DITERIMA' pada Batch ID {$stokBarang->id} untuk produk '{$produk->nama}'.");
                        }
                        if (in_array($snRetur, $serialsSudahDireturDariBatchIni)) {
                            throw new \Exception("Nomor Seri '{$snRetur}' dari Batch ID {$stokBarang->id} sudah pernah diretur sebelumnya.");
                        }
                    }
                }


                $dataCreateRetur = [
                    'id_stok_barang' => $stokBarang->id,
                    'id_pengguna' => Auth::id(),
                    'nomor_retur' => $nomorReturPembelianOtomatis,
                    'jumlah_retur' => $jumlahReturSaatIni,
                    'nomor_seri_diretur' => ($produk->memiliki_serial && !empty($serialsDireturInputCleaned)) ? implode(',', $serialsDireturInputCleaned) : null,
                    'alasan_retur' => $itemReturData['alasan_retur'],
                    'catatan_ke_supplier' => $itemReturData['catatan_ke_supplier_item'] ?? null,
                    'tindakan_lanjut_supplier' => $itemReturData['tindakan_lanjut_supplier'],
                    'catatan_internal_retur' => ($index === 0 && isset($validated['catatan_global_retur_pembelian'])) ? $validated['catatan_global_retur_pembelian'] : null,
                    'tanggal_retur' => $tanggalRetur,
                    // status_retur tidak ada lagi, dianggap langsung diproses
                ];

                
                $retur = ReturPembelian::create($dataCreateRetur);
               

                // 1. Kurangi Stok Fisik pada Batch
                $stokBarang->decrement('jumlah', $jumlahReturSaatIni);
               

                // 2. Update LogNomorSeri -> status 'DIRETUR_SUPPLIER'
                if ($produk->memiliki_serial && !empty($serialsDireturInputCleaned)) {
                    foreach ($serialsDireturInputCleaned as $snRetur) {
                        
                        LogNomorSeri::where('nomor_seri', $snRetur)
                            ->where('id_produk', $produk->id)
                            ->where('id_stok_barang_asal', $stokBarang->id) // Pastikan dari batch ini
                            ->where('status_log', 'DITERIMA')       // Harus yang statusnya DITERIMA
                            ->update([
                                'status_log' => 'DIRETUR_SUPPLIER',
                                'id_referensi' => $retur->id,
                                'tipe_referensi' => ReturPembelian::class,
                                'tanggal_status' => $tanggalRetur,
                                // id_stok_barang_asal tetap, menandakan asal-usulnya sebelum diretur
                            ]);
                       
                    }
                }
              
            } // End foreach items_retur

            if (!$adaItemYangValidDiretur) {
               
                DB::rollBack();
              
                return redirect()->back()->with('error', 'Tidak ada item yang diretur. Harap masukkan jumlah retur minimal 1 pada salah satu item.')->withInput();
            }

            DB::commit();
            

            return redirect()->route('admin.retur_pembelian.index')
                             ->with('success', "Retur Pembelian ({$nomorReturPembelianOtomatis}) ke supplier berhasil disimpan.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
           
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
          
            return redirect()->back()->with('error', 'Gagal menyimpan retur pembelian: ' . $e->getMessage())->withInput();
        }
    }

    // Method untuk generate nomor retur pembelian (mirip dengan retur penjualan)
    private function generateNextReturPembelianNumber(Carbon $date): string
    {
        $branchCode = config('app.branch_code', 'KGT');
        $dateFormatted = $date->format('dmy');
        $prefix = "RTRB-{$branchCode}-{$dateFormatted}-"; // RTRB untuk Retur Beli
        $lastToday = ReturPembelian::whereDate('tanggal_retur', $date->toDateString())
                                ->where('nomor_retur', 'LIKE', $prefix . '%')
                                ->orderBy('nomor_retur', 'desc')
                                ->first();
        $nextSequence = 1;
        if ($lastToday) {
            $lastSequencePart = substr($lastToday->nomor_retur, strlen($prefix));
            if (is_numeric($lastSequencePart)) {
                $nextSequence = (int)$lastSequencePart + 1;
            }
        }
        return $prefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Menampilkan daftar retur pembelian yang sudah dibuat.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ReturPembelian::with([
                            'stokBarang.produk', // Produk dari batch yang diretur
                            'stokBarang.supplier', // Supplier dari batch yang diretur (supplier asal batch)
                            'pengguna' // Admin yang memproses retur
                        ])
                        ->select('retur_pembelian.*')
                        ->orderBy('retur_pembelian.tanggal_retur', 'desc')
                        ->orderBy('retur_pembelian.id', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('nama_produk', function($row){
                    return $row->stokBarang->produk->nama ?? '-';
                })
                ->addColumn('supplier_asal_batch', function($row){ // Supplier dari batch yang diretur
                    return $row->stokBarang->supplier->nama ?? 'N/A (Batch)';
                })
                ->editColumn('tanggal_retur_formatted', function ($row) {
                    return Carbon::parse($row->tanggal_retur)->isoFormat('D MMM YYYY, HH:mm');
                })
                ->addColumn('jumlah_retur_formatted', function ($row) {
                    return $row->jumlah_retur . ' unit';
                })
                ->addColumn('tindakan_lanjut_supplier_display', function($row){
                    // Anda bisa buat helper atau array mapping untuk display nama yang lebih baik
                    // Ini adalah opsi yang dipilih saat retur, bukan status update dari supplier
                    $options = [
                        'MENUNGGU_RESPONS_SUPPLIER' => 'Menunggu Respons Supplier',
                        'PROSES_PENGGANTIAN_BARANG' => 'Diajukan Penggantian',
                        'PROSES_REFUND_UANG' => 'Diajukan Refund',
                    ];
                    return $options[$row->tindakan_lanjut_supplier] ?? ucwords(str_replace('_', ' ', $row->tindakan_lanjut_supplier));
                })
                ->addColumn('admin_proses', function($row){
                    return $row->pengguna->nama ?? '-';
                })
                ->addColumn('action', function ($row) {
                    $btnShow = '<a href="' . route('admin.retur_pembelian.show', $row->id) . '" class="btn btn-info btn-sm me-1" title="Lihat Detail Retur"><i class="bi bi-eye"></i></a>';
                    // Jika ada status update dari supplier, mungkin ada tombol edit status di sini
                    return $btnShow;
                })
                ->rawColumns(['action', 'tindakan_lanjut_supplier_display'])
                ->make(true);
        }
        return view('admin.retur_pembelian.index');
    }

    /**
     * Menampilkan detail satu retur pembelian.
     */
    public function show(ReturPembelian $returPembelian) // Route model binding
    {
        $returPembelian->load([
            'stokBarang.produk',
            'stokBarang.supplier', // Supplier asal batch
            'pengguna',
            // Jika retur pembelian terkait dengan PO asal, Anda mungkin ingin menampilkan info PO tersebut
            // Ini memerlukan relasi dari StokBarang -> DetailPembelian -> Pembelian
            'stokBarang.detailPembelian.pembelian'
        ]);
        return view('admin.retur_pembelian.show', compact('returPembelian'));
    }

    public function edit(ReturPembelian $returPembelian) // Route Model Binding
    {
        // Validasi apakah retur ini boleh diupdate statusnya
        $statusFinal = ['SELESAI_DIGANTI', 'SELESAI_DIREFUND', 'DITOLAK_SUPPLIER'];
        if (in_array($returPembelian->tindakan_lanjut_supplier, $statusFinal)) {
            return redirect()->route('admin.retur_pembelian.index')->with('info', 'Retur pembelian ini sudah memiliki status tindak lanjut final dan tidak dapat diedit.');
        }

        $returPembelian->load(['stokBarang.produk', 'stokBarang.supplier', 'pengguna']);

        // Opsi status tindak lanjut final dari supplier
        $tindakanLanjutSupplierFinalOptions = [
            'MENUNGGU_RESPONS_SUPPLIER' => 'Menunggu Respons Supplier', // Mungkin ini status awal
            'PROSES_PENGGANTIAN_BARANG' => 'Diajukan/Proses Penggantian Barang',
            'PROSES_REFUND_UANG' => 'Diajukan/Proses Refund Uang',
            'SELESAI_DIGANTI' => 'SELESAI - Barang Sudah Diganti Supplier',
            'SELESAI_DIREFUND' => 'SELESAI - Sudah Direfund Supplier',
            'DITOLAK_SUPPLIER' => 'DITOLAK oleh Supplier',
        ];

        return view('admin.retur_pembelian.edit', compact('returPembelian', 'tindakanLanjutSupplierFinalOptions'));
    }

    public function update(Request $request, ReturPembelian $returPembelian)
    {
        $statusFinalSebelumnya = ['SELESAI_DIGANTI', 'SELESAI_DIREFUND', 'DITOLAK_SUPPLIER'];
        if (in_array($returPembelian->tindakan_lanjut_supplier, $statusFinalSebelumnya)) {
            return redirect()->route('admin.retur_pembelian.index')->with('info', 'Retur pembelian ini sudah memiliki status tindak lanjut final dan tidak dapat diedit.');
        }

        $validated = $request->validate([
            'tindakan_lanjut_supplier' => 'required|string|max:100', // Validasi dengan key dari $tindakanLanjutSupplierFinalOptions
            'catatan_ke_supplier' => 'nullable|string|max:1000',
            'catatan_internal_retur' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $returPembelian->tindakan_lanjut_supplier = $validated['tindakan_lanjut_supplier'];
            if ($request->filled('catatan_ke_supplier')) {
                $returPembelian->catatan_ke_supplier = $validated['catatan_ke_supplier'];
            }
            if ($request->filled('catatan_internal_retur')) {
                $returPembelian->catatan_internal_retur = $validated['catatan_internal_retur'];
            }
            $returPembelian->save();

           
            if ($validated['tindakan_lanjut_supplier'] === 'SELESAI_DIGANTI') {

                session()->flash('info_barang_pengganti', "Status retur pembelian {$returPembelian->nomor_retur} diupdate. Jangan lupa catat penerimaan untuk barang pengganti dari supplier jika sudah datang.");
            }

            DB::commit();
        
            return redirect()->route('admin.retur_pembelian.index')->with('success', "Tindak lanjut untuk Retur Pembelian No. {$returPembelian->nomor_retur} berhasil diperbarui.");

        } catch (\Exception $e) {
            DB::rollBack();
        
            return redirect()->back()->with('error', 'Gagal memperbarui tindak lanjut retur: ' . $e->getMessage())->withInput();
        }
    }


}