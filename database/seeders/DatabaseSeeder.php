<?php

namespace Database\Seeders;

use App\Models\Pelanggan;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Memulai Database Seeding...');

        // Panggil seeder dalam urutan dependensi:
        $this->call([
            PenggunaSeeder::class,     // Buat pengguna dulu (terutama admin)
            SupplierSeeder::class,     // Buat Supplier
            MerkSeeder::class,
            ProdukSeeder::class,       // Buat produk (membutuhkan Merk)
            PelangganSeeder::class,    // Buat Pelanggan
        ]);

        $this->command->info('Database Seeding Selesai.');
    }
}