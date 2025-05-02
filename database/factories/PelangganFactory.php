<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PelangganFactory extends Factory
{
    protected $model = Pelanggan::class;

    public function definition()
    {
        return [
            'nama' => $this->faker->unique()->name(),
            'telepon' => $this->faker->optional(0.8)->numerify('08##########'), // 80% punya telepon
            'alamat' => $this->faker->optional(0.7)->address(), // 70% punya alamat
            'status' => true,
        ];
    }
}