<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Models\DetailPembelian; // Import DetailPembelian
use App\Models\Supplier;      // Import Supplier
use App\Models\Produk;        // Import Produk (untuk create/edit)
use App\Http\Requests\StorePembelianRequest;   // Ganti dengan request Anda
use App\Http\Requests\UpdatePembelianRequest;   // Ganti dengan request Anda
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;       // Untuk Transaksi Database
use Illuminate\Support\Facades\Auth;    // Untuk mendapatkan ID pengguna
use Yajra\DataTables\Facades\DataTables; // Import DataTables
use Carbon\Carbon;                      // Import Carbon untuk format tanggal
use Illuminate\Http\JsonResponse;
use PhpParser\Node\Stmt\Return_;

class PembelianController extends Controller
{
    /**
     * Display a listing of the resource.
     * Handles AJAX requests for DataTables.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Query dasar dengan eager loading supplier
            $query = Pembelian::with('supplier')->select('pembelian.*');

            return DataTables::of($query)
                ->addIndexColumn() // Menambahkan kolom DT_RowIndex (nomor urut)
                ->addColumn('supplier_nama', function ($row) {
                    // Mengambil nama supplier dari relasi, handle jika null
                    return $row->supplier->nama ?? '<span class="text-muted">N/A</span>';
                })
                ->editColumn('tanggal_pembelian_formatted', function ($row) {
                    // Memformat tanggal menggunakan Carbon
                    return Carbon::parse($row->tanggal_pembelian)->isoFormat('D MMM YYYY');
                })
                ->addColumn('total_harga_formatted', function ($row) {
                    // Memformat total harga sebagai mata uang
                    return 'Rp ' . number_format($row->total_harga, 0, ',', '.');
                })
                ->addColumn('status_pembelian_badge', function ($row) {
                    // Membuat HTML badge untuk status pembelian
                    $statusClass = 'secondary'; // Default
                    if ($row->status_pembelian == 'DIPESAN') $statusClass = 'info';
                    elseif ($row->status_pembelian == 'PENGIRIMAN') $statusClass = 'primary';
                    elseif ($row->status_pembelian == 'TIBA_SEBAGIAN') $statusClass = 'warning';
                    elseif ($row->status_pembelian == 'SELESAI') $statusClass = 'success';
                    elseif ($row->status_pembelian == 'DIBATALKAN') $statusClass = 'danger';
                    return '<span class="badge bg-' . $statusClass . '">' . $row->status_pembelian . '</span>';
                })
                ->addColumn('status_pembayaran_badge', function ($row) {
                    // Membuat HTML badge untuk status pembayaran
                    $bayarClass = 'danger'; // Default BELUM_LUNAS
                    if ($row->status_pembayaran == 'LUNAS') $bayarClass = 'success';
                    elseif ($row->status_pembayaran == 'JATUH_TEMPO') $bayarClass = 'warning';
                    return '<span class="badge bg-' . $bayarClass . '">' . $row->status_pembayaran . '</span>';
                })
                ->addColumn('action', function ($row) {
                    // Membuat HTML untuk tombol aksi (Lihat, Edit, Hapus)
                    $btnShow = '<a href="' . route('admin.pembelian.show', $row->id) . '" class="btn btn-info btn-sm me-1" title="Lihat Detail"><i class="bi bi-eye"></i></a>';
                    $btnEdit = '';
                    $btnDelete = '';
                    $btnProsesPenerimaan = ''; // Variabel baru untuk tombol penerimaan

                    // ===>>> LOGIKA TOMBOL PROSES PENERIMAAN <<<===
                    // Tampilkan tombol jika status 'DIPESAN' atau 'TIBA_SEBAGIAN'
                    // dan jika masih ada item yang belum diterima penuh (ini perlu dicek lebih detail jika mau)
                    if (in_array($row->status_pembelian, ['DIPESAN', 'TIBA_SEBAGIAN'])) {
                        // Cek apakah masih ada item yang bisa diterima
                        // Ini bisa menjadi query tambahan atau flag di model Pembelian
                        // Untuk sederhana, kita tampilkan jika statusnya memungkinkan
                        $masihBisaDiterima = true; // Asumsi awal, idealnya ada pengecekan
                        // Contoh pengecekan (membutuhkan relasi detailPembelian):
                        // $masihBisaDiterima = $row->detailPembelian()->whereRaw('jumlah > jumlah_diterima')->exists();

                        if ($masihBisaDiterima) {
                             $btnProsesPenerimaan = '<a href="' . route('gudang.penerimaan.create', ['pembelian' => $row->id]) . '" class="btn btn-success btn-sm me-1" title="Proses Penerimaan Barang">
                                                        <i class="bi bi-box-arrow-in-down"></i> Terima
                                                    </a>';
                        }
                    }
                    // ===>>> AKHIR LOGIKA <<<===

                    // Logika kondisional untuk tombol Edit
                    if (in_array($row->status_pembelian, ['DRAFT', 'DIPESAN'])) {
                        $btnEdit = '<a href="' . route('admin.pembelian.edit', $row->id) . '" class="btn btn-warning btn-sm me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>';
                    }

                    // Logika kondisional untuk tombol Hapus
                    if (in_array($row->status_pembelian, ['DRAFT', 'DIBATALKAN'])) {
                        $btnDelete = '<form action="' . route('admin.pembelian.destroy', $row->id) . '" method="POST" class="d-inline form-delete">
                                        ' . csrf_field() . '
                                        ' . method_field('DELETE') . '
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>';
                    }

                    return $btnProsesPenerimaan . $btnShow . $btnEdit . $btnDelete;
                })
                // Memberitahu DataTables bahwa kolom ini berisi HTML mentah
                ->rawColumns(['supplier_nama', 'status_pembelian_badge', 'status_pembayaran_badge', 'action'])
                ->make(true); // Membuat dan mengembalikan response JSON
        }

        // Jika bukan request AJAX, tampilkan view index biasa
        return view('admin.pembelian.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function create()
    {
        // Ambil data yang diperlukan untuk form (misal: supplier, produk)
        $suppliers = Supplier::where('status', true)->orderBy('nama')->pluck('nama', 'id');
        // Produk mungkin diambil via AJAX di form untuk performa jika banyak
        // $produk = Produk::where('status', true)->orderBy('nama')->get(['id', 'nama', 'kode_produk']);

        return view('admin.pembelian.create');
    }

    /**
     * Store a newly created resource in storage.
     * (Modified to accept optional user input for nomor_pembelian)
     *
     * @param  \App\Http\Requests\StorePembelianRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePembelianRequest $request)
    {
        $validated = $request->validated();
    
        DB::beginTransaction();
        try {
            $nomorPembelianFinal = '';
            $tanggalPembelian = Carbon::parse($validated['tanggal_pembelian']);

            // Cek jika Admin memasukkan nomor manual dan validasi lolos
            if (Auth::user()->role === 'ADMIN' && $request->filled('nomor_pembelian')) {
                // Pasitkan validasi di StorePembelianRequest sudah mencakup format dan uniqueness
                $nomorPembelianFinal = $validated['nomor_pembelian'];
            } else {
                // Generate nomor otomatis (gunakan tanggal dari form)
                $nomorPembelianFinal = $this->generateNextPurchaseOrderNumber($tanggalPembelian);
            }

    
            // 1. Buat data Pembelian utama
            $pembelian = Pembelian::create([
                'id_supplier' => $validated['id_supplier'],
                'id_pengguna' => Auth::id(),
                // Gunakan nomor yang digenerate, abaikan input user jika ada
                'nomor_pembelian' => $nomorPembelianFinal,
                'nomor_faktur_supplier' => $validated['nomor_faktur_supplier'] ?? null,
                'tanggal_pembelian' => $tanggalPembelian->format('Y-m-d'), // Tetap gunakan tanggal dari form
                'metode_pembayaran' => $validated['metode_pembayaran'] ?? null,
                'status_pembayaran' => $validated['status_pembayaran'] ?? 'BELUM_LUNAS',
                'dibayar_at' => $validated['dibayar_at'] ?? null,
                'status_pembelian' => $validated['status_pembelian'] ?? 'DRAFT',
                'catatan' => $validated['catatan'] ?? null,
                'total_harga' => 0, // Akan dihitung ulang
                'status' => $validated['status'] ?? true,
            ]);

            // 2. Proses dan simpan Detail Pembelian
            $totalHargaPembelian = 0;
            if (isset($validated['details']) && is_array($validated['details'])) {
                foreach ($validated['details'] as $detail) {
                    // Pastikan data detail valid sebelum disimpan
                    if (isset($detail['id_produk'], $detail['jumlah'], $detail['harga_beli'])) {
                        $pembelian->detailPembelian()->create([
                            'id_produk' => $detail['id_produk'],
                            'jumlah' => $detail['jumlah'],
                            'harga_beli' => $detail['harga_beli'],
                            'jumlah_diterima' => 0, // Awalnya 0
                            // 'catatan' => $detail['catatan'] ?? null,
                        ]);
                        // Akumulasi total harga
                        $totalHargaPembelian += ($detail['jumlah'] * $detail['harga_beli']);
                    }
                }
            }

            // 3. Update total harga di Pembelian utama
            $pembelian->total_harga = $totalHargaPembelian;
            $pembelian->save();

            DB::commit(); // Simpan semua perubahan jika berhasil

            return redirect()->route('admin.pembelian.index')
                             ->with('success', 'Pembelian baru (' . $nomorPembelianFinal . ') berhasil ditambahkan.'); // Tampilkan nomor di pesan sukses

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua perubahan jika terjadi error
            // Log error jika perlu: Log::error('Error store pembelian: '. $e->getMessage());
            return redirect()->back()
                             ->with('error', 'Terjadi kesalahan saat menyimpan pembelian: ' . $e->getMessage())
                             ->withInput(); // Kembalikan input lama ke form
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Pembelian  $pembelian
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function show(Pembelian $pembelian)
    {
        // Load relasi yang dibutuhkan untuk view detail
        $pembelian->load(['supplier', 'pengguna', 'detailPembelian.produk']);

        return view('admin.pembelian.show', compact('pembelian'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Pembelian  $pembelian
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function edit(Pembelian $pembelian)
    {
        // Periksa apakah pembelian boleh diedit berdasarkan status
        if (!in_array($pembelian->status_pembelian, ['DRAFT', 'DIPESAN'])) {
             return redirect()->route('admin.pembelian.index')
                              ->with('error', 'Pembelian dengan status ' . $pembelian->status_pembelian . ' tidak dapat diedit.');
        }

        // Load relasi detail untuk ditampilkan di form
        $pembelian->load('detailPembelian.produk');
        $suppliers = Supplier::where('status', true)->orderBy('nama')->pluck('nama', 'id');
        // $produk = Produk::where('status', true)->orderBy('nama')->get(['id', 'nama', 'kode_produk']);

        return view('admin.pembelian.edit', compact('pembelian', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePembelianRequest  $request
     * @param  \App\Models\Pembelian  $pembelian
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePembelianRequest $request, Pembelian $pembelian)
    {
         // Periksa lagi apakah boleh diedit
         if (!in_array($pembelian->status_pembelian, ['DRAFT', 'DIPESAN'])) {
             return redirect()->route('admin.pembelian.index')
                              ->with('error', 'Pembelian dengan status ' . $pembelian->status_pembelian . ' tidak dapat diedit.');
         }

        $validated = $request->validated();
        // Asumsi request 'details' berisi data detail yang *baru*
        // Anda perlu logika untuk membandingkan, mengupdate, menghapus, dan menambah detail

        DB::beginTransaction();
        try {
            // 1. Update data Pembelian utama
            $pembelian->update([
                'id_supplier' => $validated['id_supplier'],
                // 'id_pengguna' => Auth::id(), // Mungkin tidak perlu diubah saat update?
                'nomor_pembelian' => $validated['nomor_pembelian'] ?? null,
                'nomor_faktur_supplier' => $validated['nomor_faktur_supplier'] ?? null,
                'tanggal_pembelian' => $validated['tanggal_pembelian'],
                'metode_pembayaran' => $validated['metode_pembayaran'] ?? null,
                'status_pembayaran' => $validated['status_pembayaran'] ?? 'BELUM_LUNAS',
                'dibayar_at' => $validated['dibayar_at'] ?? null,
                'status_pembelian' => $validated['status_pembelian'] ?? 'DRAFT',
                'catatan' => $validated['catatan'] ?? null,
                'status' => $validated['status'] ?? true,
                // Total harga akan dihitung ulang
            ]);

            // 2. Proses Detail Pembelian (Logika Update/Delete/Create)
            $totalHargaPembelian = 0;
            $detailIdsToKeep = [];

            if (isset($validated['details']) && is_array($validated['details'])) {
                foreach ($validated['details'] as $detail) {
                    // Pastikan data detail valid
                    if (isset($detail['id_produk'], $detail['jumlah'], $detail['harga_beli'])) {
                        $detailData = [
                            'id_produk' => $detail['id_produk'],
                            'jumlah' => $detail['jumlah'],
                            'harga_beli' => $detail['harga_beli'],
                            // 'jumlah_diterima' tidak diubah di sini
                            // 'catatan' => $detail['catatan'] ?? null,
                        ];

                        // Jika ada 'detail_id', update existing detail
                        if (isset($detail['detail_id']) && !empty($detail['detail_id'])) {
                            $existingDetail = DetailPembelian::find($detail['detail_id']);
                            if ($existingDetail && $existingDetail->id_pembelian == $pembelian->id) {
                                $existingDetail->update($detailData);
                                $detailIdsToKeep[] = $existingDetail->id;
                                $totalHargaPembelian += ($existingDetail->jumlah * $existingDetail->harga_beli);
                            }
                        } else {
                            // Jika tidak ada 'detail_id', buat detail baru
                            $newDetail = $pembelian->detailPembelian()->create($detailData);
                            $detailIdsToKeep[] = $newDetail->id;
                            $totalHargaPembelian += ($newDetail->jumlah * $newDetail->harga_beli);
                        }
                    }
                }
            }

            // 3. Hapus detail yang tidak ada dalam request (tidak ada di $detailIdsToKeep)
            $pembelian->detailPembelian()->whereNotIn('id', $detailIdsToKeep)->delete();

            // 4. Update total harga di Pembelian utama
            $pembelian->total_harga = $totalHargaPembelian;
            $pembelian->save();

            DB::commit();

            return redirect()->route('admin.pembelian.index')
                             ->with('success', 'Data pembelian berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Error update pembelian: '. $e->getMessage());
            return redirect()->back()
                             ->with('error', 'Terjadi kesalahan saat memperbarui pembelian: ' . $e->getMessage())
                             ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     * Handles AJAX requests from DataTables delete button.
     *
     * @param  \App\Models\Pembelian  $pembelian
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Pembelian $pembelian)
    {
        // Periksa apakah boleh dihapus
        if (!in_array($pembelian->status_pembelian, ['DRAFT', 'DIBATALKAN'])) {
             return response()->json([
                 'success' => false,
                 'message' => 'Gagal menghapus. Pembelian dengan status ' . $pembelian->status_pembelian . ' tidak dapat dihapus.'
             ], 403); // Forbidden
        }

        DB::beginTransaction();
        try {
            // Hapus detail pembelian terlebih dahulu (jika tidak ada cascade on delete)
            $pembelian->detailPembelian()->delete();
            // Hapus pembelian utama
            $pembelian->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data pembelian berhasil dihapus.'
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
             // Tangani error spesifik (misal: foreign key jika ada relasi lain)
             $errorMessage = 'Gagal menghapus data pembelian.';
             // if ($e->getCode() == '23000') { // Kode error constraint violation (tergantung DB)
             //     $errorMessage = 'Gagal menghapus. Data pembelian mungkin masih terkait dengan data lain.';
             // }
             // Log::error('Error delete pembelian: '. $e->getMessage());
             return response()->json([
                 'success' => false,
                 'message' => $errorMessage
             ], 500); // Internal Server Error
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Error delete pembelian: '. $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data.'
            ], 500);
        }
    }

    /**
     * Generate the next purchase order number based on date.
     * (Refactored logic for reuse)
     *
     * @param Carbon $date
     * @return string
     */
    private function generateNextPurchaseOrderNumber(Carbon $date): string
    {
        $branchCode = config('app.branch_code', 'XXX');
        $dateFormatted = $date->format('dmy');
        $prefix = "PO-{$branchCode}-{$dateFormatted}-";

        // Cari nomor urut terakhir untuk hari ini (menggunakan tanggal yang diberikan)
        $lastPurchaseToday = Pembelian::where('tanggal_pembelian', $date->format('Y-m-d'))
                                      ->where('nomor_pembelian', 'LIKE', $prefix . '%')
                                      ->orderBy('nomor_pembelian', 'desc')
                                      ->lockForUpdate() // Penting untuk mencegah race condition saat store
                                      ->first();

        $nextSequence = 1;
        if ($lastPurchaseToday) {
            $lastSequence = (int) substr($lastPurchaseToday->nomor_pembelian, strlen($prefix));
            $nextSequence = $lastSequence + 1;
        }

        $sequenceFormatted = str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
        return $prefix . $sequenceFormatted;
    }

    /**
     * Handle AJAX request to get the next predicted PO number.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateNextNumberAjax(Request $request): JsonResponse // Method baru
    {
        try {
            // Ambil tanggal dari request, default ke hari ini jika tidak ada
            $tanggal = $request->input('tanggal') ? Carbon::parse($request->input('tanggal')) : Carbon::now();
            // Panggil private method untuk generate nomor (tanpa lockForUpdate di sini, hanya prediksi)
             $branchCode = config('app.branch_code', 'XXX');
             $dateFormatted = $tanggal->format('dmy');
             $prefix = "PO-{$branchCode}-{$dateFormatted}-";
             $lastPurchaseToday = Pembelian::where('tanggal_pembelian', $tanggal->format('Y-m-d'))
                                           ->where('nomor_pembelian', 'LIKE', $prefix . '%')
                                           ->orderBy('nomor_pembelian', 'desc')
                                           ->first(); // Tidak perlu lock untuk prediksi
             $nextSequence = 1;
             if ($lastPurchaseToday) {
                 $lastSequence = (int) substr($lastPurchaseToday->nomor_pembelian, strlen($prefix));
                 $nextSequence = $lastSequence + 1;
             }
             $sequenceFormatted = str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
             $nomorBerikutnya = $prefix . $sequenceFormatted;


            return response()->json(['success' => true, 'nomor_pembelian' => $nomorBerikutnya]);
        } catch (\Exception $e) {
            // Log::error('Error generate PO number AJAX: '. $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memprediksi nomor pembelian.'], 500);
        } 
    }
}