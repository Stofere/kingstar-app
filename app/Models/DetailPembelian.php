<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPembelian extends Model
{
    use HasFactory;

    protected $table = 'detail_pembelian';

    protected $fillable = [
        'id_pembelian',
        'id_produk',
        'jumlah',
        'harga_beli',
        'jumlah_diterima',
        'catatan',
    ];

    protected $casts = [
        'harga_beli' => 'decimal:2',
    ];

    // Relasi: DetailPembelian belongs to Pembelian
    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'id_pembelian');
    }

    // Relasi: DetailPembelian belongs to Produk
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    // Relasi: DetailPembelian bisa menghasilkan banyak batch StokBarang (jika penerimaan bertahap)
    public function stokBarang()
    {
        return $this->hasMany(StokBarang::class, 'id_detail_pembelian');
    }
}