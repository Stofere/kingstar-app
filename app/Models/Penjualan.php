<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'penjualan';

    protected $fillable = [
        'id_pelanggan',
        'id_pengguna',
        'nomor_penjualan',
        'tanggal_penjualan',
        'total_harga',
        'metode_pembayaran',
        'kanal_transaksi',
        'tipe_transaksi',
        'uang_muka',
        'sisa_pembayaran',
        'estimasi_kirim_at',
        'status_pembayaran',
        'dibayar_at',
        'status_penjualan',
        'tanggal_pengiriman',
        'catatan',
        'status',
    ];

    protected $casts = [
        'tanggal_penjualan' => 'datetime',
        'total_harga' => 'decimal:2',
        'uang_muka' => 'decimal:2',
        'sisa_pembayaran' => 'decimal:2',
        'estimasi_kirim_at' => 'date',
        'dibayar_at' => 'date',
        'tanggal_pengiriman' => 'datetime',
        'status' => 'boolean',
    ];

    // Relasi: Penjualan belongs to Pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan');
    }

    // Relasi: Penjualan belongs to Pengguna (kasir)
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

    // Relasi: Penjualan has many DetailPenjualan
    public function detailPenjualan()
    {
        return $this->hasMany(DetailPenjualan::class, 'id_penjualan');
    }

    // Relasi: Penjualan (Pre-Order) bisa mengalokasi banyak StokBarang
    public function stokBarangDipesan()
    {
        return $this->hasMany(StokBarang::class, 'id_penjualan_alokasi');
    }
}