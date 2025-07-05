<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailReturPembelian extends Model
{
    use HasFactory;
    protected $table = 'detail_retur_pembelian';
    protected $fillable = [
        'id_retur_pembelian',
        'id_stok_barang',
        'jumlah_retur',
        'nomor_seri_diretur',
        'alasan_retur',
        'tindakan_lanjut_supplier',
        'catatan_ke_supplier',
    ];

    public function returPembelian()
    {
        return $this->belongsTo(ReturPembelian::class, 'id_retur_pembelian');
    }

    public function stokBarang()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang');
    }
}