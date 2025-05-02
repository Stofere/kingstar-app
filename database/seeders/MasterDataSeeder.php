<?php

namespace Database\Seeders;

use App\Models\Merk;
use App\Models\Pelanggan;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seed Merk
        $this->command->info('Seeding Merk...');
        Merk::factory()->count(10)->create(); // Buat 10 merk

        // Seed Supplier
        $this->command->info('Seeding Supplier...');
        Supplier::factory()->count(15)->create(); // Buat 15 supplier

        // Seed Pelanggan
        $this->command->info('Seeding Pelanggan...');
        Pelanggan::factory()->count(30)->create(); // Buat 30 pelanggan
    }
}