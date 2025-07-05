<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPenjualan extends Model
{
    use HasFactory;

    protected $table = 'retur_penjualan';

    protected $fillable = [
        'nomor_retur',
        'id_penjualan_asal',
        'id_pengguna',
        'tanggal_retur',
        'status_retur',
        'catatan_internal_retur',
    ];

    protected $casts = [
        'tanggal_retur' => 'datetime', // Atau 'date' jika tidak perlu waktu
    ];

    public function detailPenjualan()
    {
        return $this->belongsTo(DetailPenjualan::class, 'id_detail_penjualan');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

    // ACCESSOR: Untuk mendapatkan nomor_seri_diretur sebagai array
    public function getNomorSeriDireturArrayAttribute()
    {
        if (!empty($this->attributes['nomor_seri_diretur'])) {
            return array_map('trim', explode(',', $this->attributes['nomor_seri_diretur']));
        }
        return [];
    }

    // MUTATOR: Untuk menyimpan array nomor seri sebagai string comma-separated
    // Penggunaan: $returPenjualan->nomor_seri_diretur_array = ['SN1', 'SN2'];
    public function setNomorSeriDireturArrayAttribute(array $serials = null)
    {
        if (!empty($serials)) {
            $this->attributes['nomor_seri_diretur'] = implode(',', array_map('trim', $serials));
        } else {
            $this->attributes['nomor_seri_diretur'] = null;
        }
    }


    // Relasi tidak langsung (nested relationship) untuk mendapatkan produk dari retur ini.
    public function produk()
    {
        return $this->hasOneThrough(
            Produk::class,
            DetailPenjualan::class,
            'id', // Foreign key on DetailPenjualan table...
            'id', // Foreign key on Produk table...
            'id_detail_penjualan', // Local key on ReturPenjualan table...
            'id_produk' // Local key on DetailPenjualan table...
        );
    }
    
    /**
     * Mendefinisikan relasi "hasMany" ke DetailReturPenjualan.
     * Setiap nota retur dapat memiliki BANYAK rincian item retur.
     */
    public function detailReturPenjualan()
    {
        return $this->hasMany(DetailReturPenjualan::class, 'id_retur_penjualan');
    }

    // Relasi ke nota penjualan asal
    public function penjualanAsal()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan_asal');
    }
}