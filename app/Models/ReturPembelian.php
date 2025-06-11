<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPembelian extends Model
{
    use HasFactory;

    protected $table = 'retur_pembelian';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor_retur',       
        'id_stok_barang',
        'id_pengguna',
        'jumlah_retur',
        'nomor_seri_diretur',
        'alasan_retur',        
        'catatan_ke_supplier', 
        'tindakan_lanjut_supplier', 
        'catatan_internal_retur', 
        'tanggal_retur',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_retur' => 'datetime', // Atau 'date' jika Anda hanya menyimpan tanggal tanpa waktu
        'jumlah_retur' => 'integer',
    ];

    /**
     * Mendapatkan batch StokBarang yang diretur.
     */
    public function stokBarang()
    {
        return $this->belongsTo(StokBarang::class, 'id_stok_barang');
    }

    /**
     * Mendapatkan pengguna (Admin) yang memproses retur pembelian ini.
     */
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

    // === OPSIONAL: Accessor dan Mutator untuk nomor_seri_diretur ===
    // Jika Anda ingin bekerja dengan nomor_seri_diretur sebagai array di kode PHP
    // meskipun di database disimpan sebagai string comma-separated.

    /**
     * Accessor: Mendapatkan nomor_seri_diretur sebagai array.
     *
     * @return array
     */
    public function getNomorSeriDireturArrayAttribute()
    {
        if (!empty($this->attributes['nomor_seri_diretur'])) {
            return array_map('trim', explode(',', $this->attributes['nomor_seri_diretur']));
        }
        return [];
    }

    /**
     * Mutator: Menyimpan array nomor seri sebagai string comma-separated.
     * Penggunaan: $returPembelian->nomor_seri_diretur_array = ['SN_A', 'SN_B'];
     *
     * @param array|null $serials
     * @return void
     */
    public function setNomorSeriDireturArrayAttribute(array $serials = null)
    {
        if (!empty($serials)) {
            $this->attributes['nomor_seri_diretur'] = implode(',', array_map('trim', $serials));
        } else {
            $this->attributes['nomor_seri_diretur'] = null;
        }
    }
}