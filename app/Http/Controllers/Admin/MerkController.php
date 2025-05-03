<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merk;
use App\Http\Requests\StoreMerkRequest;    // Gunakan Form Request
use App\Http\Requests\UpdateMerkRequest;   // Gunakan Form Request
use Illuminate\Http\Request;

class MerkController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        // Ambil semua data merk, urutkan berdasarkan nama
        $merk = Merk::orderBy('nama')->get();
        // Kirim data ke view index
        return view('admin.merk.index', compact('merk'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        // Tampilkan view form create
        return view('admin.merk.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreMerkRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreMerkRequest $request) // Inject request yang sudah divalidasi
    {
        // Buat record baru dengan data yang sudah divalidasi
        Merk::create($request->validated());

        // Redirect ke halaman index dengan pesan sukses
        return redirect()->route('admin.merk.index')
                         ->with('success', 'Merk baru berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     * (Biasanya tidak diperlukan untuk master data admin, redirect ke edit)
     *
     * @param  \App\Models\Merk  $merk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Merk $merk) // Route model binding
    {
        return redirect()->route('admin.merk.edit', $merk);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Merk  $merk
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Merk $merk) // Route model binding
    {
        // Kirim data merk yang akan diedit ke view form edit
        return view('admin.merk.edit', compact('merk'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMerkRequest  $request
     * @param  \App\Models\Merk  $merk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateMerkRequest $request, Merk $merk) // Inject request & model
    {
        // Update record dengan data yang sudah divalidasi
        $merk->update($request->validated());

        // Redirect ke halaman index dengan pesan sukses
        return redirect()->route('admin.merk.index')
                         ->with('success', 'Data merk berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     * (Menggunakan Hard Delete untuk Merk)
     *
     * @param  \App\Models\Merk  $merk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Merk $merk) // Route model binding
    {
        try {
            // Cek apakah merk masih digunakan oleh produk (jika foreign key di produk nullable)
            // Jika foreign key di produk adalah 'restrict', query exception akan otomatis ditangkap
            if ($merk->produk()->exists()) {
                 return redirect()->route('admin.merk.index')
                                  ->with('error', 'Gagal menghapus merk. Merk masih digunakan oleh beberapa produk.');
            }

            // Hapus record merk
            $merk->delete();

            // Redirect ke halaman index dengan pesan sukses
            return redirect()->route('admin.merk.index')
                             ->with('success', 'Merk berhasil dihapus.');

        } catch (\Illuminate\Database\QueryException $e) {
            // Tangani error jika ada relasi yang menghalangi penghapusan (misal foreign key restrict)
             // Kode error 1451 biasanya untuk foreign key constraint violation di MySQL
            if ($e->errorInfo[1] == 1451) {
                 return redirect()->route('admin.merk.index')
                                  ->with('error', 'Gagal menghapus merk. Merk masih digunakan oleh beberapa produk.');
            } else {
                 return redirect()->route('admin.merk.index')
                                  ->with('error', 'Gagal menghapus merk. Terjadi kesalahan database: ' . $e->getMessage());
            }
        }
    }
}