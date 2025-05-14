<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenjualanStokAlokasi extends Model
{
    use HasFactory;

    protected $table = 'detail_penjualan_stok_alokasi'; // Pastikan nama tabel benar

    protected $fillable = [
        'id_detail_penjualan',
        'id_stok_barang',
        'jumlah_diambil',
    ];

    // Relasi ke DetailPenjualan
    public function detailPenjualan()
    {
        return $this->belongsTo(DetailPenjualan::class, 'id_detail_penjualan');
    }

    // Relasi ke StokBarang
    public function stokBarang()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang');
    }

    // Relasi ke LogNomorSeri (polimorfik)
    // Serial yang diambil dari alokasi ini akan memiliki status_log 'TERJUAL'
    // dan id_referensi = this->id, tipe_referensi = DetailPenjualanStokAlokasi::class
    public function logNomorSeri()
    {
         return $this->morphMany(LogNomorSeri::class, 'referensi');
         // Catatan: Anda perlu menambahkan morphTo('referensi') di model LogNomorSeri
    }
}