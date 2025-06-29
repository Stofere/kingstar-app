<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatPergerakanStok extends Model
{
    use HasFactory;

    public $timestamps = false; // Karena kita hanya pakai created_at manual
    protected $table = 'riwayat_pergerakan_stok';
    protected $guarded = ['id']; // Atau gunakan $fillable

    protected $casts = [
        'tanggal_transaksi' => 'datetime',
    ];

    // Relasi ke Produk
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    public function stokBarangTerkait()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang_terkait');
    }

    // Relasi ke Pengguna
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

   public function referensi()
    {
        // 'id_referensi' adalah nama kolom untuk ID
        // 'tipe_referensi' adalah nama kolom untuk nama class Model
        return $this->morphTo(null, 'tipe_referensi', 'id_referensi');
    }
}