<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Http\Requests\StorePenggunaRequest; // Gunakan Request
use App\Http\Requests\UpdatePenggunaRequest; // Gunakan Request
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PenggunaController extends Controller
{
    public function index()
    {
        $pengguna = Pengguna::orderBy('nama')->get(); // Ambil semua atau gunakan pagination
        return view('admin.pengguna.index', compact('pengguna'));
    }

    public function create()
    {
        $roles = ['ADMIN', 'KASIR', 'GUDANG']; // Opsi untuk dropdown role
        return view('admin.pengguna.create', compact('roles'));
    }

    public function store(StorePenggunaRequest $request) // Inject request
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']); // Hash password

        Pengguna::create($validated);

        return redirect()->route('admin.pengguna.index')
                         ->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    public function show(Pengguna $pengguna)
    {
         // Biasanya tidak perlu show untuk master data admin, redirect ke edit atau index
         return redirect()->route('admin.pengguna.edit', $pengguna);
    }

    public function edit(Pengguna $pengguna) // Route model binding
    {
        $roles = ['ADMIN', 'KASIR', 'GUDANG'];
        return view('admin.pengguna.edit', compact('pengguna', 'roles'));
    }

    public function update(UpdatePenggunaRequest $request, Pengguna $pengguna) // Inject request & model
    {
        $validated = $request->validated();

        // Jika password diisi, hash password baru. Jika tidak, jangan update password.
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']); // Hapus password dari array jika kosong
        }

        $pengguna->update($validated);

        return redirect()->route('admin.pengguna.index')
                         ->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Pengguna $pengguna)
    {
        // Tambahkan validasi: jangan biarkan user menghapus dirinya sendiri?
        if ($pengguna->id === auth()->id()) {
             return redirect()->route('admin.pengguna.index')
                              ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        try {
            $pengguna->delete();
            return redirect()->route('admin.pengguna.index')
                             ->with('success', 'Pengguna berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Tangani error foreign key constraint jika pengguna terkait dengan data lain
            return redirect()->route('admin.pengguna.index')
                             ->with('error', 'Gagal menghapus pengguna. Pengguna mungkin terkait dengan data transaksi lain.');
        }
    }
}