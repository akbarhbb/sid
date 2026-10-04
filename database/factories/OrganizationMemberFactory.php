<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganizationMember>
 */
class OrganizationMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region_id' => Region::factory(),
            'organization_type' => 'rt',
            'name' => fake()->name(),
            'position' => 'Ketua RT',
            'term_starts_at' => now()->subYears(2)->toDateString(),
            'term_ends_at' => now()->addYears(3)->toDateString(),
            'display_order' => 0,
        ];
    }
}
