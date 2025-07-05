<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPembelian extends Model
{
    use HasFactory;
    protected $table = 'retur_pembelian';
    protected $fillable = [
        'nomor_retur',
        'id_pengguna',
        'id_supplier_tujuan',
        'tanggal_retur',
        'catatan_internal_retur',
        'status',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier_tujuan');
    }

    // Relasi ke detail (satu header punya banyak detail)
    public function detailReturPembelian()
    {
        return $this->hasMany(DetailReturPembelian::class, 'id_retur_pembelian');
    }

    /**
     * Mendapatkan pengguna (Admin) yang memproses retur pembelian ini.
     */
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

    public function penerimaanPengganti()
    {
        // Sebuah nota retur bisa memiliki banyak riwayat penerimaan pengganti (meski jarang)
        return $this->hasMany(RiwayatPergerakanStok::class, 'id_referensi')->where('tipe_referensi', self::class)->where('tipe_transaksi', 'PENERIMAAN_PENGGANTI_RETUR');
    }

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

    public function poPengganti()
    {
        // Asumsi menyimpan 'replacement_po_id:123' di catatan internal
        return $this->belongsTo(Pembelian::class, 'catatan_internal_retur');
    }
}