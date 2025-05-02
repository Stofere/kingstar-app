<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merk;
use App\Http\Requests\StoreMerkRequest;
use App\Http\Requests\UpdateMerkRequest;
use Illuminate\Http\Request;

class MerkController extends Controller
{
    public function index()
    {
        $merk = Merk::orderBy('nama')->get();
        return view('admin.merk.index', compact('merk'));
    }

    public function create()
    {
        return view('admin.merk.create');
    }

    public function store(StoreMerkRequest $request)
    {
        Merk::create($request->validated());
        return redirect()->route('admin.merk.index')->with('success', 'Merk berhasil ditambahkan.');
    }

    public function edit(Merk $merk)
    {
        return view('admin.merk.edit', compact('merk'));
    }

    public function update(UpdateMerkRequest $request, Merk $merk)
    {
        $merk->update($request->validated());
        return redirect()->route('admin.merk.index')->with('success', 'Merk berhasil diperbarui.');
    }

    public function destroy(Merk $merk)
    {
         try {
             // Cek relasi sebelum hapus jika perlu (misal, jika onDelete bukan 'set null')
             // if ($merk->produk()->exists()) {
             //     return redirect()->route('admin.merk.index')->with('error', 'Gagal menghapus. Merk masih digunakan oleh produk.');
             // }
             $merk->delete();
             return redirect()->route('admin.merk.index')->with('success', 'Merk berhasil dihapus.');
         } catch (\Illuminate\Database\QueryException $e) {
             return redirect()->route('admin.merk.index')->with('error', 'Gagal menghapus merk. Mungkin masih terkait dengan data lain.');
         }
    }
}