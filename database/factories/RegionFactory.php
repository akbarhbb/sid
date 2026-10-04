<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Region>
 */
class RegionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'RT 06 / RW 09',
            'neighborhood' => 'Kelurahan Pengasinan',
            'district' => 'Sawangan',
            'city' => 'Depok',
            'province' => 'Jawa Barat',
            'slug' => fake()->unique()->slug(3),
            'settings' => ['expense_approval_limit' => 500000],
        ];
    }
}
