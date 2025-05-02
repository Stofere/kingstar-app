<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPenjualan extends Model
{
    use HasFactory;

    protected $table = 'retur_penjualan';

    protected $fillable = [
        'id_detail_penjualan',
        'nomor_seri_diretur',
        'id_pengguna',
        'jumlah_retur',
        'alasan',
        'catatan_pelanggan',
        'status_retur',
        'tanggal_retur',
        'tindakan_lanjut',
    ];

    protected $casts = [
        'tanggal_retur' => 'date',
    ];

    // Relasi: ReturPenjualan belongs to DetailPenjualan (asal item)
    public function detailPenjualan()
    {
        return $this->belongsTo(DetailPenjualan::class, 'id_detail_penjualan');
    }

    // Relasi: ReturPenjualan belongs to Pengguna (yang memproses)
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }
}