<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenjualanStokAlokasi extends Model
{
    use HasFactory;

    protected $table = 'detail_penjualan_stok_alokasi'; 

    protected $fillable = [
        'id_detail_penjualan',
        'id_stok_barang',
        'jumlah_diambil',
        'nomor_seri_terkait', 
        'tipe_alokasi', // Tipe alokasi: STOK_KELUAR_BIASA, DIALOKASIKAN_PESANAN, STOK_KELUAR_PESANAN, DIBATALKAN_PESANAN
        'dialokasikan_oleh', // User Admin yang melakukan pra-alokasi
        'dialokasikan_at', // Waktu pra-alokasi/ admin membooking stok
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'dialokasikan_at' => 'datetime',
        // Jika Anda menyimpan nomor_seri_terkait sebagai JSON di DB (bukan comma-separated)
        // 'nomor_seri_terkait' => 'array',
    ];

    /**
     * Mendapatkan detail penjualan yang memiliki alokasi stok ini.
     */
    public function detailPenjualan()
    {
        return $this->belongsTo(DetailPenjualan::class, 'id_detail_penjualan');
    }

    /**
     * Mendapatkan batch stok barang yang dialokasikan.
     */
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