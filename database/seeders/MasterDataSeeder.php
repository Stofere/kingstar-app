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
        Merk::factory()->count(5)->create(); 

        // Seed Supplier
        $this->command->info('Seeding Supplier...');
        Supplier::factory()->count(5)->create(); 

        // Seed Pelanggan
        $this->command->info('Seeding Pelanggan...');
        Pelanggan::factory()->count(5)->create(); 
    }
}