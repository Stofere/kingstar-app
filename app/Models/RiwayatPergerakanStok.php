<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatPergerakanStok extends Model
{
    use HasFactory;

    protected $table = 'riwayat_pergerakan_stok';

    protected $guarded = ['id']; // Izinkan semua field untuk diisi

    protected $casts = [
        'tanggal_transaksi' => 'datetime',
    ];

    // Relasi ke Produk
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    // Relasi ke Batch Stok
    public function stokBarang()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang_terkait');
    }

    // Relasi ke Pengguna
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

    // Relasi Polymorphic ke dokumen sumber (Nota Penjualan, Pembelian, dll)
    public function referensi()
    {
        return $this->morphTo();
    }
}