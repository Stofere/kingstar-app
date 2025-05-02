<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenyesuaianStok extends Model
{
    use HasFactory;

    protected $table = 'penyesuaian_stok';

    // Hanya created_at yang relevan, updated_at mungkin tidak
    // const UPDATED_AT = null; // Nonaktifkan updated_at jika tidak perlu

    protected $fillable = [
        'id_stok_barang',
        'jumlah_penyesuaian',
        'tipe_penyesuaian',
        'nomor_seri_terkait',
        'id_stok_opname',
        'id_pengguna',
        'tanggal_penyesuaian',
        'catatan',
    ];

    protected $casts = [
        'tanggal_penyesuaian' => 'datetime',
    ];

    // Relasi: PenyesuaianStok belongs to StokBarang (yang disesuaikan)
    public function stokBarang()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang');
    }

    // Relasi: PenyesuaianStok belongs to StokOpname (jika berasal dari opname)
    public function stokOpname()
    {
        return $this->belongsTo(StokOpname::class, 'id_stok_opname');
    }

    // Relasi: PenyesuaianStok belongs to Pengguna (yang melakukan)
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }
}