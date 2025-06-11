<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan; // Import model Pelanggan
use App\Http\Requests\StorePelangganRequest; // Import Form Request
use App\Http\Requests\UpdatePelangganRequest; // Import Form Request
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use Yajra\DataTables\Facades\DataTables; 

class PelangganController extends Controller
{
    /**
     * Display a listing of the resource.
     * Handles AJAX requests for DataTables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Jika request adalah AJAX, proses untuk DataTables
        if ($request->ajax()) {
            $data = Pelanggan::select(['id', 'nama', 'telepon', 'alamat', 'status', 'created_at']); // Pilih kolom yang dibutuhkan

            return DataTables::of($data)
                ->addIndexColumn() // Menambahkan kolom DT_RowIndex (nomor urut)
                ->editColumn('status', function ($row) {
                    // Tampilkan status sebagai badge Bootstrap
                    $badgeClass = $row->status ? 'success' : 'danger';
                    $statusText = $row->status ? 'Aktif' : 'Tidak Aktif';
                    return '<span class="badge bg-' . $badgeClass . '">' . $statusText . '</span>';
                })
                ->addColumn('action', function ($row) {
                    // Tombol Edit dan Hapus
                    $btn = '<a href="' . route('admin.pelanggan.edit', $row->id) . '" class="btn btn-warning btn-sm me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>';
                    $btn .= '<button type="button" class="btn btn-danger btn-sm btn-delete" data-id="' . $row->id . '" title="Hapus"><i class="bi bi-trash"></i></button>';
                    return $btn;
                })
                // Memberitahu DataTables bahwa kolom ini berisi HTML mentah
                ->rawColumns(['status', 'action'])
                ->make(true); // Membuat dan mengembalikan response JSON
        }

        // Jika bukan request AJAX, tampilkan view index biasa
        return view('admin.pelanggan.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('admin.pelanggan.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePelangganRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePelangganRequest $request)
    {
        // Data sudah divalidasi oleh StorePelangganRequest
        $validatedData = $request->validated();

        
        try {
            Pelanggan::create($validatedData);


            return redirect()->route('admin.pelanggan.index')
                             ->with('success', 'Data pelanggan berhasil ditambahkan.');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Terjadi kesalahan saat menyimpan data pelanggan: ' . $e->getMessage())
                             ->withInput(); 
        }
    }

    /**
     * Display the specified resource.
     * (Opsional, bisa diimplementasikan jika ada halaman detail pelanggan)
     *
     * @param  \App\Models\Pelanggan  $pelanggan
     * @return \Illuminate\Http\Response
     */
    public function show(Pelanggan $pelanggan)
    {
        // return view('admin.pelanggan.show', compact('pelanggan'));
        abort(404); // Jika tidak ada halaman show, kembalikan 404
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Pelanggan  $pelanggan
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Pelanggan $pelanggan)
    {
        return view('admin.pelanggan.edit', compact('pelanggan'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePelangganRequest  $request
     * @param  \App\Models\Pelanggan  $pelanggan
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePelangganRequest $request, Pelanggan $pelanggan)
    {
        // Data sudah divalidasi oleh UpdatePelangganRequest
        $validatedData = $request->validated();

        // DB::beginTransaction(); 
        try {
            $pelanggan->update($validatedData);

            // DB::commit(); 
            return redirect()->route('admin.pelanggan.index')
                             ->with('success', 'Data pelanggan berhasil diperbarui.');

        } catch (\Exception $e) {
            // DB::rollBack(); 
            // Log error jika perlu: Log::error('Error update pelanggan: '. $e->getMessage());
            return redirect()->back()
                             ->with('error', 'Terjadi kesalahan saat memperbarui data pelanggan: ' . $e->getMessage())
                             ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     * Handles AJAX requests from DataTables delete button.
     *
     * @param  \App\Models\Pelanggan  $pelanggan
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Pelanggan $pelanggan)
    {
        // DB::beginTransaction(); 
        try {
            $pelanggan->delete();

            // DB::commit(); 
            return response()->json([
                'success' => true,
                'message' => 'Data pelanggan berhasil dihapus.'
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            // DB::rollBack(); 
            // Tangani error spesifik (misal: foreign key jika pelanggan masih terkait transaksi)
            $errorMessage = 'Gagal menghapus data pelanggan.';
            if ($e->getCode() == '23000') { // Kode error constraint violation (tergantung DB)
                 $errorMessage = 'Gagal menghapus. Data pelanggan mungkin masih terkait dengan data penjualan atau lainnya.';
            }
            // Log::error('Error delete pelanggan: '. $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500); // Internal Server Error
        } catch (\Exception $e) {
            // DB::rollBack(); 
            // Log::error('Error delete pelanggan: '. $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data.'
            ], 500);
        }
    }
}