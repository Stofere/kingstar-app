<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Http\Requests\StoreSupplierRequest;  
use App\Http\Requests\UpdateSupplierRequest; 
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables; 
use Illuminate\Http\JsonResponse; 

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $suppliers = Supplier::select('supplier.*'); // Select spesifik untuk DataTables

            return DataTables::of($suppliers)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    // Tampilkan status Aktif/Tidak Aktif dengan badge
                    return $row->status
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Tidak Aktif</span>';
                })
                ->addColumn('action', function ($row) {
                    // Tombol aksi Edit & Hapus
                    $editUrl = route('admin.supplier.edit', $row->id);
                    $deleteUrl = route('admin.supplier.destroy', $row->id);
                    $btn = '<a href="' . $editUrl . '" class="btn btn-warning btn-sm me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>';
                    $btn .= '<form action="' . $deleteUrl . '" method="POST" class="d-inline form-delete">';
                    $btn .= csrf_field();
                    $btn .= method_field('DELETE');
                    $btn .= '<button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>';
                    $btn .= '</form>';
                    return $btn;
                })
                ->rawColumns(['status', 'action']) // Kolom yg berisi HTML
                ->make(true);
        }

        // Tampilkan view index jika bukan AJAX
        return view('admin.supplier.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        // Buat instance baru untuk default form
        $supplier = new Supplier(['status' => true]);
        return view('admin.supplier.create', compact('supplier'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreSupplierRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();
        // Konversi status dari '1'/'0' ke boolean
        $validated['status'] = filter_var($request->input('status', true), FILTER_VALIDATE_BOOLEAN);

        Supplier::create($validated);

        return redirect()->route('admin.supplier.index')
                         ->with('success', 'Supplier baru berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function show(Supplier $supplier)
    {
        // Redirect ke halaman edit saja
        return redirect()->route('admin.supplier.edit', $supplier);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Supplier $supplier) // Route model binding
    {
        return view('admin.supplier.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSupplierRequest  $request
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier) // Inject request & model
    {
        $validated = $request->validated();
        // Konversi status dari '1'/'0' ke boolean
        $validated['status'] = filter_var($request->input('status', true), FILTER_VALIDATE_BOOLEAN);

        $supplier->update($validated);

        return redirect()->route('admin.supplier.index')
                         ->with('success', 'Data supplier berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Supplier $supplier) // Tambahkan Request untuk cek AJAX
    {
        // Cek apakah supplier masih terkait dengan data lain (misal: Pembelian, StokBarang konsinyasi)
        // Ini penting karena onDelete di tabel lain mungkin 'restrict' atau 'set null'
        if ($supplier->pembelian()->exists() || $supplier->stokBarang()->where('tipe_stok', 'KONSINYASI')->exists()) {
             // Jika request dari AJAX (yang kita implementasikan di index)
             if ($request->ajax() || $request->wantsJson()) {
                 return response()->json([
                     'success' => false,
                     'message' => 'Gagal menghapus. Supplier masih terkait dengan data pembelian atau stok konsinyasi.'
                 ], 409); // 409 Conflict
             }
             // Jika request biasa (form submit tanpa AJAX)
            return redirect()->route('admin.supplier.index')
                             ->with('error', 'Gagal menghapus. Supplier masih terkait dengan data pembelian atau stok konsinyasi.');
        }

        try {
            $supplier->delete();

            // Response untuk AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier berhasil dihapus.'
                ]);
            }
            // Response untuk request biasa
            return redirect()->route('admin.supplier.index')
                             ->with('success', 'Supplier berhasil dihapus.');

        } catch (\Illuminate\Database\QueryException $e) {
             $errorMessage = 'Gagal menghapus supplier. Terjadi kesalahan database.';
             // Log error $e->getMessage() jika perlu
             if ($request->ajax() || $request->wantsJson()) {
                 return response()->json([
                     'success' => false,
                     'message' => $errorMessage
                 ], 500); // Internal Server Error
             }
            return redirect()->route('admin.supplier.index')
                             ->with('error', $errorMessage);
        }
    }

    /**
     * Mencari supplier aktif berdasarkan query untuk Select2 AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchAjax(Request $request): JsonResponse // Tambahkan method ini
    {
        $searchQuery = $request->input('q');
        $page = $request->input('page', 1);
        $limit = 15;

        // Query dasar untuk supplier yang AKTIF
        $query = Supplier::query()->where('status', true); // <-- Filter supplier aktif

        // Filter berdasarkan nama jika ada query pencarian
        if ($searchQuery) {
            $query->where('nama', 'LIKE', "%{$searchQuery}%");
            // Anda bisa menambahkan pencarian di field lain jika perlu
            // ->orWhere('telepon', 'LIKE', "%{$searchQuery}%");
        }

        // Lakukan pagination
        $paginator = $query->select(['id', 'nama', 'telepon']) // Pilih kolom yang dibutuhkan
                           ->orderBy('nama')
                           ->paginate($limit, ['*'], 'page', $page);

        // Format hasil untuk Select2
        $results = collect($paginator->items())->map(function ($supplier) {
            // Buat teks (Nama (Telepon)) - opsional
            $telepon = $supplier->telepon ? " ({$supplier->telepon})" : "";
            return [
                'id' => $supplier->id,
                'text' => $supplier->nama . $telepon
            ];
        });

        // Kembalikan data JSON
        return response()->json([
            'items' => $results,
            'total_count' => $paginator->total()
        ]);
    }
}