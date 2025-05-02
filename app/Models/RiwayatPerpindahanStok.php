<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatPerpindahanStok extends Model
{
    use HasFactory;

    protected $table = 'riwayat_perpindahan_stok';

    protected $fillable = [
        'id_stok_barang',
        'jumlah',
        'dari_lokasi',
        'ke_lokasi',
        'dipindahkan_at',
        'id_pengguna',
        'catatan',
    ];

    protected $casts = [
        'dipindahkan_at' => 'datetime',
    ];

    // Relasi: RiwayatPerpindahanStok belongs to StokBarang
    public function stokBarang()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang');
    }

    // Relasi: RiwayatPerpindahanStok belongs to Pengguna
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }
}