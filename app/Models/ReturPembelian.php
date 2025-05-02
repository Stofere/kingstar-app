<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPembelian extends Model
{
    use HasFactory;

    protected $table = 'retur_pembelian';

    protected $fillable = [
        'id_stok_barang',
        'nomor_seri_diretur',
        'id_pengguna',
        'jumlah_retur',
        'alasan',
        'status_retur',
        'tindakan_lanjut',
        'tanggal_retur',
        'catatan_supplier',
    ];

    protected $casts = [
        'tanggal_retur' => 'date',
    ];

    // Relasi: ReturPembelian belongs to StokBarang (yang diretur)
    public function stokBarang()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang');
    }

    // Relasi: ReturPembelian belongs to Pengguna (yang memproses)
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }
}