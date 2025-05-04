<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Merk;
use App\Http\Requests\StoreProdukRequest;
use App\Http\Requests\UpdateProdukRequest;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse; // Import JsonResponse untuk type hinting


class ProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     * Handle AJAX request for DataTables server-side processing.
     *
     * @param \Illuminate\Http\Request $request // <-- Terima $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request) // <-- Tambahkan Request $request
    {
        // Jika request adalah AJAX (dari DataTables)
        if ($request->ajax()) {
            // Ambil data produk, eager load merk
            // select('produk.*') penting agar kolom dari tabel produk bisa diakses
            $produk = Produk::with('merk')->select('produk.*');

            // Proses data menggunakan Yajra DataTables
            return DataTables::of($produk)
                ->addIndexColumn() // Tambah kolom nomor urut DT_RowIndex
                ->editColumn('merk.nama', function ($row) {
                    // Format kolom merk
                    return $row->merk ? $row->merk->nama : '-';
                })
                ->editColumn('harga_jual_standart', function ($row) {
                    // Format harga
                    return 'Rp ' . number_format($row->harga_jual_standart ?? 0, 0, ',', '.');
                })
                ->editColumn('memiliki_serial', function ($row) {
                    // Format boolean serial
                    return $row->memiliki_serial ? '<span class="badge bg-success">Ya</span>' : '<span class="badge bg-secondary">Tidak</span>';
                })
                 ->editColumn('status', function ($row) {
                    // Format boolean status
                    return $row->status ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Tidak Aktif</span>';
                })
                ->addColumn('gambar_display', function ($row) {
                    // Tampilkan thumbnail gambar
                    if ($row->gambar && Storage::exists('public/produk/' . $row->gambar)) {
                        $url = Storage::url('produk/' . $row->gambar);
                        // Tambahkan link untuk modal atau lihat ukuran penuh jika perlu
                        return '<img src="' . $url . '" alt="' . e($row->nama) . '" height="50" style="cursor: pointer;" onclick="showImageModal(\''.$url.'\', \''.e($row->nama).'\')">';
                    }
                    return '<span class="text-muted small">(No Image)</span>';
                })
                ->addColumn('action', function ($row) {
                    // Tambah tombol aksi Edit & Hapus
                    $editUrl = route('admin.produk.edit', $row->id);
                    $deleteUrl = route('admin.produk.destroy', $row->id);
                    $btn = '<a href="' . $editUrl . '" class="btn btn-warning btn-sm me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>';
                    $btn .= '<form action="' . $deleteUrl . '" method="POST" class="d-inline form-delete">';
                    $btn .= csrf_field();
                    $btn .= method_field('DELETE');
                    $btn .= '<button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>';
                    $btn .= '</form>';
                    return $btn;
                })
                // Beritahu DataTables kolom mana saja yang berisi HTML mentah
                ->rawColumns(['memiliki_serial', 'status', 'gambar_display', 'action'])
                ->make(true); // Buat dan kirim response JSON untuk DataTables
        }

        // Jika bukan request AJAX, tampilkan view index biasa
        // View ini hanya berisi kerangka tabel HTML, datanya akan diisi via AJAX
        return view('admin.produk.index');
    }

    public function create()
    {
        // Ambil data Merk untuk dropdown di form
        $merk = Merk::orderBy('nama')->pluck('nama', 'id');
        // Buat instance Produk baru untuk mengisi nilai default di form (jika ada)
        $produk = new Produk([
            'status' => true,         // Default status aktif
            'satuan' => 'PCS',        // Default satuan PCS
            'memiliki_serial' => false // Default tidak memiliki serial
            // Tambahkan default lain jika perlu
        ]);
        // Kembalikan view create dengan data merk dan objek produk baru
        return view('admin.produk.create', compact('merk', 'produk'));
    }

    public function store(StoreProdukRequest $request) 
    {
        // Ambil data yang sudah divalidasi oleh StoreProdukRequest
        $validated = $request->validated();

        // Handle file upload gambar jika ada
        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('public/produk');
            $validated['gambar'] = basename($path); // Simpan nama file saja
        } else {
             $validated['gambar'] = null; // Set null jika tidak ada gambar
        }

        // Konversi boolean dari form (nilai '1' atau '0') ke boolean PHP
        $validated['memiliki_serial'] = filter_var($request->input('memiliki_serial', false), FILTER_VALIDATE_BOOLEAN);
        $validated['status'] = filter_var($request->input('status', true), FILTER_VALIDATE_BOOLEAN);

        // Buat record produk baru di database
        Produk::create($validated);

        // Redirect ke halaman index produk dengan pesan sukses
        return redirect()->route('admin.produk.index')
                         ->with('success', 'Produk baru berhasil ditambahkan.');
    }

    public function show(Produk $produk)
    {
        return redirect()->route('admin.produk.edit', $produk);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Produk  $produk // <-- Route Model Binding
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Produk $produk)
    {
        // Ambil data Merk untuk dropdown
        $merk = Merk::orderBy('nama')->pluck('nama', 'id');
        // Kembalikan view edit, pass data produk yang akan diedit dan data merk
        return view('admin.produk.edit', compact('produk', 'merk'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProdukRequest  $request // <-- Gunakan UpdateProdukRequest
     * @param  \App\Models\Produk  $produk // <-- Terima $produk dari route
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProdukRequest $request, Produk $produk) // <-- Injeksi UpdateProdukRequest & $produk
    {
        // Ambil data yang sudah divalidasi oleh UpdateProdukRequest
        $validated = $request->validated();

        // Handle file upload gambar (jika ada gambar baru diupload)
        if ($request->hasFile('gambar')) {
            // 1. Hapus gambar lama jika ada di storage
            if ($produk->gambar && Storage::exists('public/produk/' . $produk->gambar)) {
                Storage::delete('public/produk/' . $produk->gambar);
            }
            // 2. Simpan gambar baru
            $path = $request->file('gambar')->store('public/produk');
            $validated['gambar'] = basename($path); // Update nama file di data tervalidasi
        }
        // Jika tidak ada file baru yang diupload, $validated tidak akan berisi key 'gambar',
        // sehingga kolom 'gambar' di database tidak akan diubah oleh $produk->update().

        // Konversi boolean dari form ('1'/'0') ke boolean PHP
        $validated['memiliki_serial'] = filter_var($request->input('memiliki_serial', false), FILTER_VALIDATE_BOOLEAN);
        $validated['status'] = filter_var($request->input('status', true), FILTER_VALIDATE_BOOLEAN);

        // Update data produk di database
        $produk->update($validated);

        // Redirect ke halaman index produk dengan pesan sukses
        return redirect()->route('admin.produk.index')->with('success', 'Data produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk)
    {
        try {
             if ($produk->gambar && Storage::exists('public/produk/' . $produk->gambar)) {
                Storage::delete('public/produk/' . $produk->gambar);
             }
            $produk->delete();
            return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1451) {
                 return redirect()->route('admin.produk.index')->with('error', 'Gagal menghapus produk. Produk masih digunakan dalam data stok atau transaksi.');
            } else {
                 return redirect()->route('admin.produk.index')->with('error', 'Gagal menghapus produk. Terjadi kesalahan database: ' . $e->getMessage());
            }
        }
    }

        /**
     * Mencari produk berdasarkan query untuk Select2 AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchAjax(Request $request): JsonResponse // Tambahkan method ini
    {
        $searchQuery = $request->input('q'); // Ambil query pencarian dari parameter 'q'
        $page = $request->input('page', 1); // Ambil nomor halaman, default 1
        $limit = 15; // Jumlah item per halaman

        // Query dasar untuk produk yang aktif
        $query = Produk::query()->where('status', true);

        // Jika ada query pencarian, filter berdasarkan nama atau kode produk
        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('nama', 'LIKE', "%{$searchQuery}%")
                  ->orWhere('kode_produk', 'LIKE', "%{$searchQuery}%");
            });
        }

        // Lakukan pagination
        $paginator = $query->select(['id', 'nama', 'kode_produk']) // Pilih kolom yang dibutuhkan
                           ->orderBy('nama') // Urutkan berdasarkan nama
                           ->paginate($limit, ['*'], 'page', $page);

        // Format hasil untuk Select2
        $formattedItems = $paginator->items(); // Dapatkan array item dari paginator

        $results = collect($formattedItems)->map(function ($produk) {
            // Buat teks yang informatif (Nama (Kode Produk))
            $kode = $produk->kode_produk ? " ({$produk->kode_produk})" : "";
            return [
                'id' => $produk->id,
                'text' => $produk->nama . $kode
            ];
        });

        // Kembalikan data dalam format JSON yang dibutuhkan Select2
        return response()->json([
            'items' => $results,
            'total_count' => $paginator->total() // Total item yang cocok (untuk pagination Select2)
        ]);
    }

}