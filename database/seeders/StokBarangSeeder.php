<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\StokBarang;
use Illuminate\Database\Seeder;

class StokBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Seeding Stok Barang...');
        $products = Produk::all();

        if ($products->isEmpty()) {
             $this->command->warn('Tidak ada produk untuk di-seed stoknya. Jalankan ProdukSeeder terlebih dahulu.');
             return;
        }

        foreach ($products as $produk) {
             // Buat 1 sampai 5 batch stok untuk setiap produk
             $jumlahBatch = rand(1, 5);
             for ($i = 0; $i < $jumlahBatch; $i++) {
                // Gunakan factory untuk membuat batch, override id_produk
                StokBarang::factory()->create([
                    'id_produk' => $produk->id,
                    // Kita bisa juga menggunakan state di sini jika perlu
                    // Contoh: Jika ingin 1 batch konsinyasi per produk
                    // if ($i == 0) {
                    //     StokBarang::factory()->konsinyasi()->create(['id_produk' => $produk->id]);
                    // } else {
                    //     StokBarang::factory()->reguler()->create(['id_produk' => $produk->id]);
                    // }
                ]);
             }
        }

        // Atau cara lebih simpel: Buat 100 batch stok acak untuk produk acak
        // StokBarang::factory()->count(100)->create();
    }
}