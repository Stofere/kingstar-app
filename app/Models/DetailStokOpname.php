<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailStokOpname extends Model
{
    use HasFactory;

    protected $table = 'detail_stok_opname';

    protected $fillable = [
        'id_stok_opname',
        'id_stok_barang',
        'jumlah_sistem',
        'jumlah_fisik',
        'selisih',
        'catatan',
    ];

    // Tidak perlu cast khusus untuk integer

    // Relasi: DetailStokOpname belongs to StokOpname
    public function stokOpname()
    {
        return $this->belongsTo(StokOpname::class, 'id_stok_opname');
    }

    // Relasi: DetailStokOpname belongs to StokBarang (batch yang dihitung)
    public function stokBarang()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang');
    }
}