<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogNomorSeri extends Model
{
    use HasFactory;

    protected $table = 'log_nomor_seri';

    // Laravel tidak menggunakan timestamps() di tabel ini secara default
    // Jika Anda ingin menggunakan created_at/updated_at, pastikan ada di migrasi
    // public $timestamps = false; // Uncomment jika tidak ada kolom timestamps

    protected $fillable = [
        'id_produk',
        'id_stok_barang_asal',
        'nomor_seri',
        'status_log',
        'id_referensi',
        'tipe_referensi',
        'tanggal_status',
        'catatan',
    ];

    protected $casts = [
        'tanggal_status' => 'datetime',
    ];

    // Relasi: LogNomorSeri belongs to Produk
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    // Relasi: LogNomorSeri belongs to StokBarang (asal saat diterima)
    public function stokBarangAsal()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang_asal');
    }

    // Relasi: Polymorphic relationship ke model referensi
    // Nama method 'referensi' bebas, tapi sesuaikan dengan argumen __FUNCTION__
    public function referensi()
    {
        // Menggunakan nama kolom dari skema Anda
        return $this->morphTo(__FUNCTION__, 'tipe_referensi', 'id_referensi');
    }
}