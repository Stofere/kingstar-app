<?php

namespace Database\Seeders;

use App\Models\Produk;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Seeding Produk...');
        Produk::factory()->count(2)->create(); 
        Produk::factory()->count(2)->berserial()->create(); 
        Produk::factory()->count(1)->tanpaSerial()->create(); 

        Produk::create([
            'id_merk' => null, 
            'kode_produk' => 'HJBL19',
            'nama' => 'Hardcase JBL19',
            'deskripsi' => 'Hardcase JBL19',
            'harga_jual_standart' => 2500000,
            'gambar' => null,
            'satuan' => 'Unit',
            'memiliki_serial' => false,
            'durasi_garansi_standar_bulan' => 0,
            'status' => true,
        ]);
        Produk::create([
            'id_merk' => null,
            'kode_produk' => 'HA160',
            'nama' => 'Huper 160',
            'deskripsi' => 'Huper 160',
            'harga_jual_standart' => 5000000,
            'gambar' => null,
            'satuan' => 'Set',
            'memiliki_serial' => true,
            'durasi_garansi_standar_bulan' => 12,
            'status' => true,
        ]);
        Produk::create([
            'id_merk' => null,
            'kode_produk' => 'KJXLR',
            'nama' => 'Kabel Jack XLR',
            'deskripsi' => 'Kabel Jack XLR',
            'harga_jual_standart' => 120000,
            'gambar' => null,
            'satuan' => 'Pcs',
            'memiliki_serial' => false,
            'durasi_garansi_standar_bulan' => 0,
            'status' => true,
        ]);
    }
}