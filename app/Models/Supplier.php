<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'supplier';

    protected $fillable = [
        'nama',
        'telepon',
        'email',
        'alamat',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Relasi: Supplier bisa memiliki banyak pembelian
    public function pembelian()
    {
        return $this->hasMany(Pembelian::class, 'id_supplier');
    }

    // Relasi: Supplier bisa memiliki banyak batch stok (terutama konsinyasi)
    public function stokBarang()
    {
        return $this->hasMany(StokBarang::class, 'id_supplier');
    }
}