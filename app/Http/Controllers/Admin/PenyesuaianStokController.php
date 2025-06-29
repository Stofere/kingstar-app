<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StokBarang;
use App\Models\RiwayatPergerakanStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PenyesuaianStokController extends Controller
{
    /**
     * Menampilkan form untuk membuat penyesuaian stok baru.
     */
    public function create()
    {
        // Opsi tipe penyesuaian yang akan muncul di dropdown
        $tipePenyesuaianOptions = [
            'PENGEMBALIAN_KONSINYASI' => 'Pengembalian Barang Konsinyasi ke Supplier',
            'STOK_HILANG' => 'Stok Hilang (Hasil Opname / Lainnya)',
            'STOK_RUSAK_GUDANG' => 'Stok Rusak (di Gudang/Toko)',
            'KOREKSI_MASUK' => 'Koreksi Stok (Penambahan)',
            'KOREKSI_KELUAR' => 'Koreksi Stok (Pengurangan)',
        ];
        
        // Kita juga perlu AJAX untuk mencari batch, jadi kita siapkan view-nya saja
        return view('admin.penyesuaian_stok.create', compact('tipePenyesuaianOptions'));
    }

    /**
     * Menyimpan data penyesuaian stok.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_stok_barang' => 'required|integer|exists:stok_barang,id',
            'tipe_penyesuaian' => ['required', 'string', Rule::in(['PENGEMBALIAN_KONSINYASI', 'STOK_HILANG', 'STOK_RUSAK_GUDANG', 'KOREKSI_MASUK', 'KOREKSI_KELUAR'])],
            'jumlah' => 'required|integer|min:1',
            'catatan' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $stokBarang = StokBarang::with('produk')->lockForUpdate()->find($validated['id_stok_barang']);
            
            $isPenambahan = in_array($validated['tipe_penyesuaian'], ['KOREKSI_MASUK']);
            $jumlahPenyesuaian = (int)$validated['jumlah'];

            // Validasi jumlah untuk transaksi pengurangan
            if (!$isPenambahan && $jumlahPenyesuaian > $stokBarang->jumlah) {
                return back()->with('error', 'Jumlah penyesuaian tidak boleh melebihi sisa stok di batch.')->withInput();
            }

            // Validasi khusus untuk Pengembalian Konsinyasi
            if ($validated['tipe_penyesuaian'] === 'PENGEMBALIAN_KONSINYASI' && $stokBarang->tipe_stok !== 'KONSINYASI') {
                 return back()->with('error', 'Batch yang dipilih bukan merupakan barang konsinyasi.')->withInput();
            }
            
            // Dapatkan saldo terakhir produk
            $saldoTerakhir = RiwayatPergerakanStok::where('id_produk', $stokBarang->id_produk)->lockForUpdate()->latest('id')->value('saldo_setelah_transaksi') ?? 0;
            
            // Proses transaksi
            if ($isPenambahan) {
                $stokBarang->increment('jumlah', $jumlahPenyesuaian);
                $saldoBaru = $saldoTerakhir + $jumlahPenyesuaian;
            } else { // Pengurangan
                $stokBarang->decrement('jumlah', $jumlahPenyesuaian);
                $saldoBaru = $saldoTerakhir - $jumlahPenyesuaian;
            }

            // Buat record di riwayat pergerakan stok
            RiwayatPergerakanStok::create([
                'id_produk' => $stokBarang->id_produk,
                'id_stok_barang_terkait' => $stokBarang->id,
                'tipe_transaksi' => $validated['tipe_penyesuaian'],
                'jumlah_masuk' => $isPenambahan ? $jumlahPenyesuaian : 0,
                'jumlah_keluar' => !$isPenambahan ? $jumlahPenyesuaian : 0,
                'saldo_setelah_transaksi' => $saldoBaru,
                'tanggal_transaksi' => now(),
                'keterangan' => $validated['catatan'] ?? 'Penyesuaian stok manual',
                'id_pengguna' => Auth::id(),
            ]);

            DB::commit();
            // Redirect ke halaman index (yang akan kita buat nanti) atau ke halaman create lagi dengan pesan sukses
            return redirect()->route('admin.penyesuaian_stok.create')->with('success', 'Penyesuaian stok berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }
}