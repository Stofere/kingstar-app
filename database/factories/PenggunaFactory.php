<?php

namespace Database\Factories;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PenggunaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Pengguna::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'nama' => $this->faker->name(),
            'username' => $this->faker->unique()->userName(),
            'password' => Hash::make('password'), // Default password 'password'
            'role' => $this->faker->randomElement(['ADMIN', 'KASIR', 'GUDANG']), // Assign role random
            'status' => true,
            // 'remember_token' => Str::random(10), // Jika menggunakan remember token
        ];
    }

    /**
     * Indicate that the user is an admin.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'ADMIN',
            ];
        });
    }

    /**
     * Indicate that the user is a kasir.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function kasir()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'KASIR',
            ];
        });
    }

    /**
     * Indicate that the user is a gudang.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function gudang()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'GUDANG',
            ];
        });
    }
}