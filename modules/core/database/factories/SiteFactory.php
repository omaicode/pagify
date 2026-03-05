<?php

namespace Pagify\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pagify\Core\Models\Site;

class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug();

        return [
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'domain' => $slug . '.local',
            'locale' => fake()->randomElement(['en', 'vi']),
            'is_active' => true,
        ];
    }
}
