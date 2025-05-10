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
        // Buat 50 produk dengan variasi (serial/tanpa serial)
        Produk::factory()->count(5)->create(); // Produk dengan setting acak dari factory
        Produk::factory()->count(3)->berserial()->create(); // Pastikan ada 10 produk berserial
        Produk::factory()->count(2)->tanpaSerial()->create(); // Pastikan ada 10 produk tanpa serial
    }
}