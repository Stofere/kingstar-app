<?php

namespace App\Http\Controllers\Kasir;

use Carbon\Carbon;
use App\Models\Penjualan;
use App\Models\StokBarang;
use Illuminate\Http\Request;
use App\Models\ReturPenjualan;
use App\Models\DetailPenjualan;
use App\Models\DetailPenjualanStokAlokasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\DetailReturPenjualan;
use Illuminate\Support\Facades\Auth;
use App\Models\RiwayatPergerakanStok;
use Yajra\DataTables\Facades\DataTables;


class ReturPenjualanController extends Controller
{
    /**
     * Menampilkan form untuk mencari transaksi penjualan yang akan diretur.
     */
    public function showCariTransaksiForm()
    {
        return view('kasir.retur_penjualan.cari_transaksi');
    }

    public function getTransaksiDetailAjax(Request $request)
    {
        // 1. Validasi Input Awal
        $request->validate(['nomor_penjualan' => 'required|string']);
        $nomorPenjualan = trim($request->nomor_penjualan);

        // 2. Query Utama dengan Eager Loading Mendalam
        $penjualan = Penjualan::where('nomor_penjualan', $nomorPenjualan)->with([
            'pelanggan', 
            'detailPenjualan' => function($q) {
                $q->with([
                    'produk',
                    'stokAlokasi' => function($sa) { // Muat alokasi
                        $sa->with(['stokBarang.supplier']); // Dari alokasi, muat batch & suppliernya
                    },
                    'returPenjualan.alokasiAsal' // Dari retur yang ada, muat alokasi asalnya
                ]);
            }
        ])->first();

        // 3. Validasi Kondisi Penjualan
        if (!$penjualan) {
            return response()->json(['success' => false, 'message' => 'Transaksi penjualan tidak ditemukan.'], 404);
        }
        if ($penjualan->status_penjualan !== 'SELESAI') {
            return response()->json(['success' => false, 'message' => 'Transaksi ini belum berstatus SELESAI dan tidak dapat diretur.'], 422);
        }
        $batasHariRetur = config('app.batas_hari_retur_penjualan', 7);
        if (Carbon::parse($penjualan->tanggal_penjualan)->lt(Carbon::now()->subDays($batasHariRetur))) {
            return response()->json(['success' => false, 'message' => "Transaksi ini sudah melewati batas waktu retur ({$batasHariRetur} hari)."], 422);
        }

        // 4. Proses Data untuk Mendapatkan Item yang Bisa Diretur
        $itemsForRetur = collect();

    if ($penjualan && $penjualan->detailPenjualan) {
        foreach($penjualan->detailPenjualan as $detail) {
            
            // ### LOGIKA BARU UNTUK PRODUK BERSERIAL ###
            if ($detail->produk && $detail->produk->memiliki_serial && !empty($detail->nomor_seri_terjual)) {
                $serialsTerjual = array_map('trim', explode(',', $detail->nomor_seri_terjual));
                
                // Ambil semua riwayat penjualan untuk serial-serial ini dalam satu query
                $riwayatPenjualanSerials = RiwayatPergerakanStok::whereIn('nomor_seri', $serialsTerjual)
                    ->where('tipe_transaksi', 'PENJUALAN')
                    ->where('tipe_referensi', Penjualan::class)
                    ->where('id_referensi', $penjualan->id)
                    ->with('stokBarangTerkait.supplier') // Eager load supplier asal
                    ->get()
                    ->keyBy('nomor_seri'); // Jadikan nomor seri sebagai key agar mudah dicari

                foreach($serialsTerjual as $serial) {
                    // Cek apakah serial ini sudah pernah diretur
                    $sudahDiretur = DetailReturPenjualan::where('nomor_seri_diretur', $serial)
                                        ->whereHas('returPenjualan', fn($q) => $q->where('id_penjualan_asal', $penjualan->id))
                                        ->exists();
                    
                    if ($sudahDiretur) continue;

                    // Ambil riwayat spesifik untuk serial ini dari koleksi yang sudah kita query
                    $riwayatAsal = $riwayatPenjualanSerials->get($serial);
                    if (!$riwayatAsal || !$riwayatAsal->stokBarangTerkait) continue;

                    // Temukan record DPSA yang sesuai
                    $alokasiAsal = $detail->stokAlokasi->where('id_stok_barang', $riwayatAsal->id_stok_barang_terkait)->first();
                    if (!$alokasiAsal) continue; // Safety check

                    $namaSupplier = $riwayatAsal->stokBarangTerkait->supplier->nama ?? 'N/A';
                    $infoBatch = "Dari Batch ID: {$riwayatAsal->id_stok_barang_terkait} (Supplier: {$namaSupplier})";

                    $itemsForRetur->push([
                        'id_dpsa' => $alokasiAsal->id, // ID dari detail_penjualan_stok_alokasi (BENAR)
                        'nama_produk' => $detail->produk->nama . " (SN: {$serial})",
                        'is_serial' => true,
                        'nomor_seri' => $serial,
                        'info_batch' => $infoBatch,
                    ]);
                }
            }
            // Untuk produk NON-SERIAL
            elseif ($detail->produk) {
                foreach($detail->stokAlokasi as $alokasi) {
                    $qtySudahDireturDariAlokasiIni = $detail->returPenjualan->where('id_dpsa_asal', $alokasi->id)->sum('jumlah_retur');
                    $sisaBisaDiretur = $alokasi->jumlah_diambil - $qtySudahDireturDariAlokasiIni;

                    if ($sisaBisaDiretur > 0) {
                        // ### PERBAIKAN ###
                        // 1. Evaluasi nama supplier di luar string
                        $namaSupplier = $alokasi->stokBarang->supplier->nama ?? 'N/A';
                        // 2. Gunakan variabel di dalam string
                        $infoBatch = "Dari Batch ID: {$alokasi->id_stok_barang} (Supplier: {$namaSupplier})";
                        
                        $itemsForRetur->push([
                            'id_dpsa' => $alokasi->id,
                            'nama_produk' => $detail->produk->nama,
                            'is_serial' => false,
                            'sisa_qty_bisa_diretur_item' => $sisaBisaDiretur,
                            'info_batch' => $infoBatch
                        ]);
                    }
                }
            }
        }
    }

        // 5. Validasi Akhir dan Kirim Respons
        if($itemsForRetur->isEmpty()){
        return response()->json(['success' => false, 'message' => 'Semua item dari transaksi ini sudah diretur sepenuhnya atau tidak bisa diretur.'], 422);
    }

        return response()->json([
            'success' => true,
            'penjualan' => [
                'id' => $penjualan->id,
                'nomor_penjualan' => $penjualan->nomor_penjualan,
                'tanggal_penjualan_formatted' => Carbon::parse($penjualan->tanggal_penjualan)->isoFormat('D MMM YYYY, HH:mm'),
                'pelanggan_nama' => $penjualan->pelanggan->nama ?? 'Umum',
            ],
            'detail_items_info' => $itemsForRetur->toArray()
        ]);
    }

    /**
     * Menerima ID item yang dipilih dari form pencarian,
     * lalu me-redirect ke form retur dengan membawa ID item tersebut.
     */
    public function processSelectedItems(Request $request)
    {
        // 1. Validasi dasar: pastikan 'selected_items' dikirim dan berupa array.
        $validated = $request->validate([
            'selected_items' => 'required|array|min:1',
        ], [
            'selected_items.required' => 'Anda harus memilih minimal satu item untuk diretur.'
        ]);

        $processedItems = [];
        $idPenjualan = null;

        // 2. Loop melalui setiap nilai checkbox yang dipilih.
        foreach ($validated['selected_items'] as $itemValue) {
            $id_dpsa = null;
            $nomor_seri = null;

            // Pisahkan nilai jika item berserial (format: "id|nomor_seri")
            if (str_contains($itemValue, '|')) {
                [$id_dpsa, $nomor_seri] = explode('|', $itemValue, 2);
            } else {
                // Jika non-serial, nilainya adalah id_dpsa itu sendiri.
                $id_dpsa = $itemValue;
            }

            // 3. Validasi lebih dalam: pastikan id_dpsa ada di database.
            // Eager load relasi yang dibutuhkan untuk mendapatkan id_penjualan.
            $alokasi = \App\Models\DetailPenjualanStokAlokasi::with('detailPenjualan')->find($id_dpsa);

            if (!$alokasi) {
                // Jika salah satu saja tidak valid, batalkan proses.
                return redirect()->back()->with('error', "Item retur dengan referensi alokasi ID {$id_dpsa} tidak valid.")->withInput();
            }

            // Ambil ID Penjualan dari item pertama yang valid.
            if (is_null($idPenjualan)) {
                $idPenjualan = $alokasi->detailPenjualan->id_penjualan;
            } 
            // Pastikan semua item berasal dari nota penjualan yang sama.
            elseif ($idPenjualan !== $alokasi->detailPenjualan->id_penjualan) {
                return redirect()->back()->with('error', 'Semua item yang diretur harus berasal dari nota penjualan yang sama.')->withInput();
            }

            // Kumpulkan data yang sudah bersih dan valid.
            $processedItems[] = [
                'id_dpsa_asal' => $alokasi->id,
                'nomor_seri_diretur' => $nomor_seri
            ];
        }

        if (is_null($idPenjualan)) {
            return redirect()->back()->with('error', 'Gagal menentukan nota penjualan asal dari item yang dipilih.');
        }

        // 4. Simpan data yang sudah diproses ke session. Ini kunci utamanya.
        session()->flash('retur_items_data', $processedItems);

        // 5. Redirect ke form retur, hanya dengan ID penjualan.
        return redirect()->route('kasir.retur_penjualan.form', [
            'penjualan' => $idPenjualan,
        ]);
    }

    /**
     * Menampilkan form untuk input detail retur.
     */
    public function showReturForm(Request $request, Penjualan $penjualan)
    {
        // 1. Ambil data item dari flash session
        $itemsToRetur = session('retur_items_data');

        // 2. Validasi: jika tidak ada data di session, kembalikan ke pencarian
        if (empty($itemsToRetur)) {
            return redirect()->route('kasir.retur_penjualan.cari_transaksi')
                            ->with('error', 'Tidak ada item yang dipilih untuk diretur. Silakan cari nota kembali.');
        }

        // 3. Ambil semua ID DPSA untuk memuat data sekaligus (efisien)
        $dpsaIds = collect($itemsToRetur)->pluck('id_dpsa_asal');
        
        $alokasiItems = DetailPenjualanStokAlokasi::whereIn('id', $dpsaIds)
            ->with(['detailPenjualan.produk', 'stokBarang.supplier'])
            ->get()
            ->keyBy('id'); // Jadikan ID sebagai key agar mudah dicari

        // 4. Siapkan data yang rapi untuk dikirim ke view
        $detailItemsUntukForm = [];
        foreach ($itemsToRetur as $item) {
            $alokasi = $alokasiItems->get($item['id_dpsa_asal']);
            if (!$alokasi) {
                continue;
            }

            // Hitung sisa yang bisa diretur dari alokasi spesifik ini
            $qtySudahDiretur = DetailReturPenjualan::where('id_dpsa_asal', $alokasi->id)->sum('jumlah_retur');
            $sisaQty = $alokasi->jumlah_diambil - $qtySudahDiretur;

            // Jika karena suatu hal item ini sudah diretur penuh, jangan tampilkan di form
            if ($sisaQty <= 0) {
                continue;
            }

            // ### PERBAIKAN SINTAKS DI SINI ###
            // Evaluasi nama supplier di luar string untuk menghindari error '??'
            $namaSupplier = $alokasi->stokBarang->supplier->nama ?? 'N/A';
            $infoBatch = "Dari Batch ID: {$alokasi->id_stok_barang} (Supplier: {$namaSupplier})";

            $detailItemsUntukForm[] = (object)[
                'id_dpsa_asal' => $alokasi->id,
                'produk' => $alokasi->detailPenjualan->produk,
                'info_batch' => $infoBatch,
                'nomor_seri_diretur' => $item['nomor_seri_diretur'], // Bawa nomor seri jika ada
                'jumlah_retur_maksimal' => $sisaQty,
            ];
        }
        
        // Jika setelah dicek ternyata semua sudah diretur, kembalikan
        if (empty($detailItemsUntukForm)) {
            return redirect()->route('kasir.retur_penjualan.cari_transaksi')->with('info', 'Semua item yang Anda pilih sudah pernah diretur sebelumnya.');
        }

        // Opsi untuk dropdown di view
        $alasanReturOptions = [
            'BARANG_RUSAK_PELANGGAN' => 'Barang Rusak Saat Diterima Pelanggan',
            'SALAH_BARANG_TERKIRIM' => 'Salah Kirim Barang',
            'BERUBAH_PIKIRAN' => 'Pelanggan Berubah Pikiran (Sesuai Kebijakan)',
            'TIDAK_SESUAI_SPESIFIKASI' => 'Tidak Sesuai Spesifikasi',
            'LAINNYA' => 'Lainnya',
        ];

        // Penting: Simpan kembali data ke session agar tidak hilang jika terjadi error validasi di form ini
        session()->keep('retur_items_data');
        
        return view('kasir.retur_penjualan.form_retur', compact('penjualan', 'detailItemsUntukForm', 'alasanReturOptions'));
    }

    /**
     * Menyimpan data retur penjualan.
     */
    public function store(Request $request, Penjualan $penjualan)
    {
        // Validasi dasar
        $validated = $request->validate([
            'tanggal_retur' => 'required|date_format:Y-m-d\TH:i|before_or_equal:now',
            'catatan_global_retur' => 'nullable|string|max:1000',
            'items_retur' => 'required|array|min:1',

            // Validasi untuk setiap item dalam array
            'items_retur.*.id_dpsa_asal' => 'required|integer|exists:detail_penjualan_stok_alokasi,id',
            'items_retur.*.nomor_seri_diretur' => 'nullable|string',
            'items_retur.*.jumlah_retur' => 'required|integer|min:1',
            'items_retur.*.alasan_retur' => 'required|string', // Hanya alasan yang divalidasi
            'items_retur.*.catatan_tambahan_item' => 'nullable|string',
            ],[
            // Pesan error kustom
            'items_retur.required' => 'Terjadi kesalahan, tidak ada item yang terdeteksi untuk diretur.',
            'items_retur.*.alasan_retur.required' => 'Alasan retur wajib dipilih untuk setiap item.',
        ]);
        
        DB::beginTransaction();
        try {
            $tanggalRetur = Carbon::parse($validated['tanggal_retur']);
            
            // Buat satu Nota Retur (Header)
            $returHeader = ReturPenjualan::create([
                'nomor_retur' => $this->generateNextReturPenjualanNumber($tanggalRetur),
                'id_penjualan_asal' => $penjualan->id,
                'id_pengguna' => Auth::id(),
                'tanggal_retur' => $tanggalRetur,
                'catatan_internal_retur' => $validated['catatan_global_retur'] ?? null,
                'status_retur' => 'MENUNGGU_PROSES_ADMIN',
            ]);

            // Loop untuk setiap item yang diretur dari form
            foreach ($validated['items_retur'] as $itemData) {
            $alokasiAsal = \App\Models\DetailPenjualanStokAlokasi::find($itemData['id_dpsa_asal']);
            if (!$alokasiAsal) {
                // Seharusnya tidak terjadi karena sudah divalidasi, tapi ini sebagai pengaman
                continue; 
            }
                
                // Validasi Kuantitas: Pastikan jumlah retur tidak melebihi yang sudah dialokasikan
                // dan belum pernah diretur dari alokasi spesifik ini.
                $qtySudahDireturDariAlokasiIni = DetailReturPenjualan::where('id_dpsa_asal', $alokasiAsal->id)->sum('jumlah_retur');
                $sisaBisaDiretur = $alokasiAsal->jumlah_diambil - $qtySudahDireturDariAlokasiIni;
                $jumlahReturSaatIni = (int)$itemData['jumlah_retur'];

                if ($jumlahReturSaatIni > $sisaBisaDiretur) {
                    throw new \Exception("Jumlah retur ({$jumlahReturSaatIni}) melebihi sisa yang bisa diretur ({$sisaBisaDiretur}) dari batch asal.");
                }

                // Buat record Detail Retur yang baru
                DetailReturPenjualan::create([
                    'id_retur_penjualan' => $returHeader->id,
                    'id_detail_penjualan_asal' => $alokasiAsal->id_detail_penjualan,
                    'id_dpsa_asal' => $alokasiAsal->id, // <- Kunci utama
                    'jumlah_retur' => $jumlahReturSaatIni,
                    'nomor_seri_diretur' => $itemData['nomor_seri_diretur'] ?? null,
                    'alasan_retur' => $itemData['alasan_retur'],
                    'tindakan_lanjut' => 'DITERIMA_KEMBALI_PERLU_CEK', 
                    'catatan_pelanggan' => $itemData['catatan_tambahan_item'] ?? null,
                ]);
            }

            DB::commit();
            // Redirect ke halaman index atau show dari retur yang baru dibuat.
            return redirect()->route('kasir.retur_penjualan.index')->with('success', "Retur dengan nomor referensi {$returHeader->nomor_retur} berhasil dibuat dan menunggu proses Admin.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error saat menyimpan retur: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan daftar retur penjualan yang sudah dibuat.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ReturPenjualan::with([
                'pengguna',
                'penjualanAsal.pelanggan',
                'detailReturPenjualan' // Penting untuk cek status setiap item
            ])->select('retur_penjualan.*');

                return DataTables::of($query)
                    ->addIndexColumn()
                    ->addColumn('tanggal_retur_formatted', function ($row) {
                        return Carbon::parse($row->tanggal_retur)->isoFormat('D MMM YYYY, HH:mm');
                    })
                    ->addColumn('nomor_penjualan_asal', function($row){
                        return $row->penjualanAsal->nomor_penjualan ?? '-';
                    })
                    ->addColumn('pelanggan', function($row){
                        return $row->penjualanAsal->pelanggan->nama ?? 'Umum';
                    })
                    ->addColumn('produk_diretur', function($row){
                    $itemCount = $row->detailReturPenjualan->count();
                    return $itemCount . ' Item';
                    })
                    ->addColumn('jml_diretur', function($row){
                        // Jumlahkan total kuantitas dari semua detail retur
                        $totalQty = $row->detailReturPenjualan->sum('jumlah_retur');
                        return $totalQty . ' unit';
                    })
                    ->addColumn('tindakan_lanjut', function($row) {
                        // Periksa setiap detail item di dalam nota retur ini
                        $all_details = $row->detailReturPenjualan;
                        
                        // Cek apakah SEMUA item sudah final (diserahkan ke pelanggan atau dibatalkan)
                        $all_final = $all_details->every(function($detail) {
                            return in_array($detail->tindakan_lanjut, ['SELESAI_DISERAHKAN_KE_PELANGGAN', 'CATAT_SEBAGAI_STOK_RUSAK_FINAL']);
                        });

                        if ($all_final) {
                            return '<span class="badge bg-dark">Selesai & Ditutup</span>';
                        }

                        // Cek apakah SETIDAKNYA ADA SATU item yang siap diambil pelanggan
                        $siapDiambil = $all_details->contains(function($detail) {
                            return in_array($detail->tindakan_lanjut, ['KEMBALI_KE_STOK_BAIK_ADMIN', 'BARANG_SELESAI_SERVIS', 'AKAN_DIRETUR_KE_SUPPLIER']);
                        });

                        if ($siapDiambil) {
                            return '<span class="badge bg-success">Barang Siap Diambil</span>';
                        }
                        
                        // Jika tidak ada yang siap diambil dan belum final, berarti masih diproses Admin
                        return '<span class="badge bg-warning text-dark">Sedang Diproses Admin</span>';
                    })
                    ->addColumn('kasir', function($row){
                        return $row->pengguna->nama ?? '-';
                    })
                    ->addColumn('action', function ($row) {
                        // Tombol Show/Lihat Nota Retur
                        $btnShow = '<a href="' . route('kasir.retur_penjualan.show', $row->id) . '" class="btn btn-info btn-sm me-1" title="Lihat Detail Retur"><i class="bi bi-eye"></i></a>';
                        
                        // ### TOMBOL BARU: CETAK NOTA RETUR ###
                        $urlCetak = route('kasir.retur_penjualan.nota_retur', $row->id);
                        $btnCetak = '<a href="' . $urlCetak . '" target="_blank" class="btn btn-secondary btn-sm me-1" title="Cetak Bukti Retur"><i class="bi bi-printer"></i></a>';

                        $btnSelesaikan = '';
                        // Tombol "Selesaikan" HANYA MUNCUL jika ada barang yang siap diambil
                        $siapDiambil = $row->detailReturPenjualan->contains(function($detail) {
                            return in_array($detail->tindakan_lanjut, ['KEMBALI_KE_STOK_BAIK_ADMIN', 'BARANG_SELESAI_SERVIS', 'AKAN_DIRETUR_KE_SUPPLIER']);
                        });

                        if ($siapDiambil) {
                            // Route ke form penyerahan barang
                            $urlSelesaikan = route('kasir.retur_penjualan.selesaikan.form', $row->id);
                            $btnSelesaikan = '<a href="' . $urlSelesaikan . '" class="btn btn-success btn-sm" title="Selesaikan Penyerahan Barang"><i class="bi bi-check-all"></i> Selesaikan</a>';
                        }

                        return '<div class="btn-group">' . $btnShow . $btnCetak . $btnSelesaikan . '</div>';
                    })
                    ->rawColumns(['tindakan_lanjut', 'action'])
                    ->make(true);
            }
        return view('kasir.retur_penjualan.index');
    }

    /**
     * Menampilkan detail satu retur penjualan.
     */
    public function show(ReturPenjualan $returPenjualan)
    {
        // Method ini sekarang bisa diakses oleh Kasir dan Admin
        $returPenjualan->load([
            'pengguna',
            'penjualanAsal.pelanggan',
            'detailReturPenjualan.detailPenjualanAsal.produk'
        ]);
        return view('kasir.retur_penjualan.show', compact('returPenjualan'));
    }

    public function showNotaRetur(ReturPenjualan $returPenjualan)
    {
        // Method ini juga harus ada di sini agar bisa diakses Kasir
        $returPenjualan->load([
            'pengguna',
            'penjualanAsal.pelanggan',
            'detailReturPenjualan.detailPenjualanAsal.produk'
        ]);
        $namaToko = "KINGSTAR ELEKTRONIK";
        $alamatToko = "Pasar Genteng Baru Lt. 2 Blok N no. 20, Surabaya";
        $teleponToko = "081290808046";
        return view('admin.proses_retur_pelanggan.nota_retur', compact(
            'returPenjualan', 'namaToko', 'alamatToko', 'teleponToko'
        ));
    }

    /**
     * METHOD BARU: Menampilkan form untuk kasir menyerahkan barang pengganti.
     */
    public function showSelesaikanForm(ReturPenjualan $returPenjualan)
    {
        $returPenjualan->load('detailReturPenjualan.detailPenjualanAsal.produk');

        $itemsSiapDiserahkan = $returPenjualan->detailReturPenjualan->filter(function($detail) {
            $tindakanAdmin = $detail->tindakan_lanjut;
            return in_array($tindakanAdmin, ['KEMBALI_KE_STOK_BAIK_ADMIN', 'BARANG_SELESAI_SERVIS', 'AKAN_DIRETUR_KE_SUPPLIER']);
        });
        
        // Siapkan data produk untuk setiap item yang akan diserahkan (untuk AJAX)
        $itemsDataForJs = $itemsSiapDiserahkan->mapWithKeys(function($detail) {
            return [
                $detail->id => [
                    'id_produk' => $detail->detailPenjualanAsal->id_produk,
                    'nama_produk' => $detail->detailPenjualanAsal->produk->nama,
                    'memiliki_serial' => (bool)$detail->detailPenjualanAsal->produk->memiliki_serial,
                    'jumlah_dibutuhkan' => $detail->jumlah_retur,
                ]
            ];
        });

        if ($itemsSiapDiserahkan->isEmpty()) {
            return redirect()->route('kasir.retur_penjualan.index')->with('error', 'Tidak ada barang yang siap diserahkan untuk nota retur ini.');
        }

        return view('kasir.retur_penjualan.form_selesaikan', compact('returPenjualan', 'itemsSiapDiserahkan', 'itemsDataForJs'));
    }

    /**
     * METHOD BARU: Menyimpan proses penyerahan barang pengganti.
     */
    public function storeSelesaikanPenyerahan(Request $request, ReturPenjualan $returPenjualan)
{
    // Validasi yang lebih spesifik
    $validated = $request->validate([
        'items_serah' => 'required|array',
        'items_serah.*.id_detail_retur' => 'required|exists:detail_retur_penjualan,id',
        'items_serah.*.id_stok_barang_pengganti' => 'required|exists:stok_barang,id',
        'items_serah.*.nomor_seri_pengganti' => 'nullable|array', // Harus array
        'items_serah.*.nomor_seri_pengganti.*' => 'string', // Setiap elemennya string
        'catatan_penyerahan' => 'nullable|string|max:1000',
    ]);

    DB::beginTransaction();
    try {
        foreach($validated['items_serah'] as $index => $itemSerah) {
            $detailRetur = DetailReturPenjualan::with('detailPenjualanAsal.produk')->find($itemSerah['id_detail_retur']);
            if (!$detailRetur) continue; // Safety check

            $stokPengganti = StokBarang::lockForUpdate()->find($itemSerah['id_stok_barang_pengganti']);
            
            // Validasi stok
            if ($stokPengganti->jumlah < $detailRetur->jumlah_retur) {
                throw new \Exception("Stok pengganti untuk produk {$stokPengganti->produk->nama} tidak mencukupi.");
            }
            
            $produk = $detailRetur->detailPenjualanAsal->produk;
            $nomorSeriPengganti = $itemSerah['nomor_seri_pengganti'] ?? [];

            // Validasi jumlah serial jika produk berserial
            if ($produk->memiliki_serial && count($nomorSeriPengganti) !== $detailRetur->jumlah_retur) {
                 throw new \Exception("Jumlah nomor seri pengganti tidak sesuai dengan jumlah retur untuk produk {$produk->nama}.");
            }

            // 1. Kurangi stok pengganti
            $stokPengganti->decrement('jumlah', $detailRetur->jumlah_retur);

            // 2. Catat di riwayat pergerakan stok
            $keterangan = "Penyerahan barang pengganti ke pelanggan untuk Retur No: {$returPenjualan->nomor_retur}";
            
            // ### PERBAIKAN LOGIKA PENCATATAN RIWAYAT ###
            $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $produk->id)->latest('id')->value('saldo_setelah_transaksi') ?? 0;
            $saldoBerjalan = $saldoTerakhir;

            if ($produk->memiliki_serial) {
                foreach ($nomorSeriPengganti as $sn) {
                    $saldoBerjalan -= 1;
                    RiwayatPergerakanStok::create([
                        'id_produk' => $produk->id,
                        'id_stok_barang_terkait' => $stokPengganti->id,
                        'tipe_transaksi' => 'PENYERAHAN_BARANG_RETUR',
                        'jumlah_keluar' => 1,
                        'saldo_setelah_transaksi' => $saldoBerjalan,
                        'nomor_seri' => $sn,
                        'id_referensi' => $detailRetur->id, 'tipe_referensi' => DetailReturPenjualan::class,
                        'tanggal_transaksi' => now(), 'keterangan' => $keterangan, 'id_pengguna' => Auth::id(),
                    ]);
                }
            } else { // Non-serial
                RiwayatPergerakanStok::create([
                    'id_produk' => $produk->id,
                    'id_stok_barang_terkait' => $stokPengganti->id,
                    'tipe_transaksi' => 'PENYERAHAN_BARANG_RETUR',
                    'jumlah_keluar' => $detailRetur->jumlah_retur,
                    'saldo_setelah_transaksi' => $saldoBerjalan - $detailRetur->jumlah_retur,
                    'nomor_seri' => null,
                    'id_referensi' => $detailRetur->id, 'tipe_referensi' => DetailReturPenjualan::class,
                    'tanggal_transaksi' => now(), 'keterangan' => $keterangan, 'id_pengguna' => Auth::id(),
                ]);
            }

            // 3. Update status detail retur menjadi final
            $detailRetur->tindakan_lanjut = 'SELESAI_DISERAHKAN_KE_PELANGGAN';
            $detailRetur->save();
        }

        // Update catatan global di header retur
        $catatanGlobal = $returPenjualan->catatan_internal_retur ? $returPenjualan->catatan_internal_retur . "\n" : "";
        $returPenjualan->catatan_internal_retur = $catatanGlobal . "[KASIR-SERAHKAN] " . now()->toDateTimeString() . " - " . ($validated['catatan_penyerahan'] ?? 'Barang diserahkan ke pelanggan.');
        
        // Cek apakah semua detail sudah selesai, jika ya, update status header
        $sisaItemBelumSelesai = $returPenjualan->detailReturPenjualan()->where('tindakan_lanjut', '!=', 'SELESAI_DISERAHKAN_KE_PELANGGAN')->count();
        if ($sisaItemBelumSelesai === 0) {
            $returPenjualan->status_retur = 'SELESAI_DITUTUP';
        }
        $returPenjualan->save();

        DB::commit();
        return redirect()->route('kasir.retur_penjualan.index')->with('success', 'Penyerahan barang retur berhasil dicatat.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Gagal selesaikan penyerahan: " . $e->getMessage() . " \n" . $e->getTraceAsString());
        return back()->with('error', 'Gagal menyelesaikan penyerahan: ' . $e->getMessage());
    }
}


    /**
     * Generate the next retur penjualan number.
     */
    private function generateNextReturPenjualanNumber(Carbon $date): string
    {
        $branchCode = config('app.branch_code', 'KGT');
        $dateFormatted = $date->format('dmy');
        $prefix = "RTRJ-{$branchCode}-{$dateFormatted}-";
        $lastToday = ReturPenjualan::whereDate('tanggal_retur', $date->toDateString())
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
}