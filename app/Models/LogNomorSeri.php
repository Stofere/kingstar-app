<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogNomorSeri extends Model
{
    use HasFactory;

    protected $table = 'log_nomor_seri';

    
    public $timestamps = false; 

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

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    public function stokBarangAsal()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang_asal');
    }

    public function referensi()
    {
        return $this->morphTo(null, 'tipe_referensi', 'id_referensi');
    }

    public function retur()
    {
        return $this->belongsTo(ReturPenjualan::class, 'id_referensi');
    }

}