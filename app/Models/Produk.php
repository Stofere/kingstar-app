<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';

    protected $fillable = [
        'id_merk',
        'kode_produk',
        'nama',
        'deskripsi',
        'harga_jual_standart',
        'gambar',
        'satuan',
        'memiliki_serial',
        'durasi_garansi_standar_bulan',
        'status',
    ];

    protected $casts = [
        'harga_jual_standart' => 'decimal:2',
        'memiliki_serial' => 'boolean',
        'status' => 'boolean',
    ];

    // Relasi: Produk belongs to Merk
    public function merk()
    {
        return $this->belongsTo(Merk::class, 'id_merk');
    }

    // Relasi: Produk bisa ada di banyak detail pembelian
    public function detailPembelian()
    {
        return $this->hasMany(DetailPembelian::class, 'id_produk');
    }

    // Relasi: Produk bisa memiliki banyak batch stok
    public function stokBarang()
    {
        return $this->hasMany(StokBarang::class, 'id_produk');
    }

     // Relasi: Produk bisa memiliki banyak log nomor seri
     public function logNomorSeri()
     {
         return $this->hasMany(LogNomorSeri::class, 'id_produk');
     }
}