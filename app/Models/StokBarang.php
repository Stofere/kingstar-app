<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokBarang extends Model
{
    use HasFactory;

    protected $table = 'stok_barang';

    protected $fillable = [
        'id_produk',
        'id_detail_pembelian',
        'id_supplier',
        'harga_beli',
        'jumlah',
        'diterima_at',
        'tipe_garansi',
        'garansi_berakhir_at',
        'tipe_stok',
        'lokasi',
        'kondisi',
        'id_penjualan_alokasi',
    ];

    protected $casts = [
        'harga_beli' => 'decimal:2',
        'diterima_at' => 'datetime',
        'garansi_berakhir_at' => 'date',
    ];

    // Relasi: StokBarang belongs to Produk
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    // Relasi: StokBarang belongs to DetailPembelian (asal penerimaan)
    public function detailPembelian()
    {
        return $this->belongsTo(DetailPembelian::class, 'id_detail_pembelian');
    }

    // Relasi: StokBarang belongs to Supplier (terutama konsinyasi)
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    // Relasi: StokBarang bisa dialokasikan ke satu Penjualan (Pre-Order)
    public function penjualanAlokasi()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan_alokasi');
    }

    // Relasi: Satu batch StokBarang bisa dijual dalam banyak DetailPenjualan (jika qty > 1)
    public function detailPenjualan()
    {
        return $this->hasMany(DetailPenjualan::class, 'id_stok_barang');
    }

    // Relasi: Satu batch StokBarang bisa diretur (ReturPembelian)
    public function returPembelian()
    {
        // Biasanya 1 batch hanya diretur sekali, tapi bisa jadi parsial? hasMany lebih fleksibel
        return $this->hasMany(ReturPembelian::class, 'id_stok_barang');
    }

     // Relasi: Satu batch StokBarang bisa memiliki banyak riwayat perpindahan
     public function riwayatPerpindahanStok()
     {
         return $this->hasMany(RiwayatPerpindahanStok::class, 'id_stok_barang');
     }

     // Relasi: Satu batch StokBarang bisa dihitung dalam banyak detail opname
     public function detailStokOpname()
     {
         return $this->hasMany(DetailStokOpname::class, 'id_stok_barang');
     }

     // Relasi: Satu batch StokBarang bisa mengalami banyak penyesuaian
     public function penyesuaianStok()
     {
         return $this->hasMany(PenyesuaianStok::class, 'id_stok_barang');
     }

     // Relasi: Satu batch StokBarang bisa memiliki banyak log nomor seri (saat diterima)
     public function logNomorSeriAsal()
     {
         return $this->hasMany(LogNomorSeri::class, 'id_stok_barang_asal');
     }
}