<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenjualanStokAlokasi extends Model
{
    use HasFactory;

    protected $table = 'detail_penjualan_stok_alokasi'; // Eksplisit nama tabel

    protected $fillable = [
        'id_detail_penjualan',
        'id_stok_barang',
        'jumlah_diambil',
    ];

    
    public function detailPenjualan()
    {
        return $this->belongsTo(DetailPenjualan::class, 'id_detail_penjualan');
    }

    public function stokBarang()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang');
    }
}