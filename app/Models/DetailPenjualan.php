<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    use HasFactory;

    protected $table = 'detail_penjualan';

    protected $fillable = [
        'id_penjualan',
        'id_stok_barang',
        'jumlah',
        'harga_jual',
        'nomor_seri_terjual',
        'status_bayar_konsinyasi',
        'customer_garansi_mulai_at',
        'customer_garansi_berakhir_at',
        'catatan',
    ];

    protected $casts = [
        'harga_jual' => 'decimal:2',
        'customer_garansi_mulai_at' => 'date',
        'customer_garansi_berakhir_at' => 'date',
    ];

    // Relasi: DetailPenjualan belongs to Penjualan
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan');
    }

    // Relasi: DetailPenjualan belongs to StokBarang (batch yang dijual)
    public function stokBarang()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang');
    }

    // Relasi: DetailPenjualan bisa memiliki satu ReturPenjualan
    public function returPenjualan()
    {
        return $this->hasOne(ReturPenjualan::class, 'id_detail_penjualan');
    }
}