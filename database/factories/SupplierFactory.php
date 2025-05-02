<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition()
    {
        return [
            'nama' => $this->faker->company() . ' ' . $this->faker->randomElement(['Distributor', 'Supplier', 'Grosir']),
            'telepon' => $this->faker->unique()->numerify('08##########'), // Format nomor telepon Indonesia
            'email' => $this->faker->unique()->safeEmail(),
            'alamat' => $this->faker->address(),
            'status' => true,
        ];
    }
}