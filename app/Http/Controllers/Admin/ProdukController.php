<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Merk; // Butuh Merk untuk dropdown
use App\Http\Requests\StoreProdukRequest;  // Gunakan Form Request
use App\Http\Requests\UpdateProdukRequest; // Gunakan Form Request
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Untuk handle file gambar


class ProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index() // Tidak perlu Request $request untuk client-side
    {
        // Ambil SEMUA produk, eager load merk
        // Untuk data yang sangat besar, pertimbangkan pagination Laravel biasa
        // atau implementasi server-side DataTables manual/Yajra nanti.
        $produk = Produk::where('status', 1)->get();

        // Kirim data ke view index
        return view('admin.produk.index', compact('produk'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $merk = Merk::orderBy('nama')->pluck('nama', 'id'); // Ambil data merk untuk dropdown
        $produk = new Produk(['status' => true, 'satuan' => 'PCS']); // Default values for create form
        return view('admin.produk.create', compact('merk', 'produk'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreProdukRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreProdukRequest $request) // Inject request
    {
        $validated = $request->validated();

        // Handle file upload gambar
        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('public/produk'); // Simpan di storage/app/public/produk
            // Pastikan Anda sudah menjalankan `php artisan storage:link`
            $validated['gambar'] = basename($path); // Simpan hanya nama file di DB
        } else {
             $validated['gambar'] = null; // Pastikan null jika tidak ada file
        }

        // Konversi boolean dari form (jika value '1'/'0')
        $validated['memiliki_serial'] = filter_var($request->input('memiliki_serial', false), FILTER_VALIDATE_BOOLEAN);
        $validated['status'] = filter_var($request->input('status', true), FILTER_VALIDATE_BOOLEAN);


        Produk::create($validated);

        return redirect()->route('admin.produk.index')
                         ->with('success', 'Produk baru berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Produk  $produk
     * @return \Illuminate\Http\Response
     */
    public function show(Produk $produk)
    {
        // Biasanya tidak perlu halaman show terpisah untuk admin master data
        return redirect()->route('admin.produk.edit', $produk);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Produk  $produk
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Produk $produk) // Route model binding
    {
        $merk = Merk::orderBy('nama')->pluck('nama', 'id');
        return view('admin.produk.edit', compact('produk', 'merk'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProdukRequest  $request
     * @param  \App\Models\Produk  $produk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProdukRequest $request, Produk $produk) // Inject request & model
    {
        $validated = $request->validated();

        // Handle file upload gambar (jika ada gambar baru)
        if ($request->hasFile('gambar')) {
            // 1. Hapus gambar lama jika ada
            if ($produk->gambar && Storage::exists('public/produk/' . $produk->gambar)) {
                Storage::delete('public/produk/' . $produk->gambar);
            }
            // 2. Simpan gambar baru
            $path = $request->file('gambar')->store('public/produk');
            $validated['gambar'] = basename($path);
        }
        // Jika tidak ada file baru, 'gambar' tidak akan ada di $validated,
        // dan gambar lama tidak akan diubah.

        // Konversi boolean dari form (jika value '1'/'0')
        $validated['memiliki_serial'] = filter_var($request->input('memiliki_serial', false), FILTER_VALIDATE_BOOLEAN);
        $validated['status'] = filter_var($request->input('status', true), FILTER_VALIDATE_BOOLEAN);

        $produk->update($validated);

        return redirect()->route('admin.produk.index')
                         ->with('success', 'Data produk berhasil diperbarui.');
    }

    /**
     * "Soft Delete" the specified resource from storage by setting status to false.
     *
     * @param  \App\Models\Produk  $produk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Produk $produk) // Nama method tetap 'destroy' sesuai route resource
    {
        // Tidak perlu menghapus gambar saat soft delete, produk mungkin diaktifkan lagi
        // if ($produk->gambar && Storage::exists('public/produk/' . $produk->gambar)) {
        //    Storage::delete('public/produk/' . $produk->gambar);
        // }

        // Ubah status menjadi false (Tidak Aktif)
        $produk->status = false;

        // Simpan perubahan
        if ($produk->save()) {
            // Jika berhasil disimpan
            return redirect()->route('admin.produk.index')
                             ->with('success', 'Produk berhasil dinonaktifkan.'); // Ubah pesan sukses
        } else {
            // Jika gagal menyimpan karena alasan tak terduga
             return redirect()->route('admin.produk.index')
                              ->with('error', 'Gagal menonaktifkan produk. Terjadi kesalahan saat menyimpan.');
        }

        // Tidak perlu try-catch untuk foreign key constraint karena kita tidak mendelete
    }
}