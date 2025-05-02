<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelian';

    protected $fillable = [
        'id_supplier',
        'id_pengguna',
        'nomor_pembelian',
        'nomor_faktur_supplier',
        'tanggal_pembelian',
        'total_harga',
        'metode_pembayaran',
        'status_pembayaran',
        'dibayar_at',
        'status_pembelian',
        'catatan',
        'status',
    ];

    protected $casts = [
        'total_harga' => 'decimal:2',
        'tanggal_pembelian' => 'date',
        'dibayar_at' => 'date',
        'status' => 'boolean',
    ];

    // Relasi: Pembelian belongs to Supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    // Relasi: Pembelian belongs to Pengguna (yang mencatat)
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

    // Relasi: Pembelian has many DetailPembelian
    public function detailPembelian()
    {
        return $this->hasMany(DetailPembelian::class, 'id_pembelian');
    }
}