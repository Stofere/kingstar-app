<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'pelanggan';

    protected $fillable = [
        'nama',
        'telepon',
        'alamat',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Relasi: Pelanggan bisa memiliki banyak penjualan
    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'id_pelanggan');
    }
}