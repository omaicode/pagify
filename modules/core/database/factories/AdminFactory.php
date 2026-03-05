<?php

namespace Pagify\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Pagify\Core\Models\Admin;
use Pagify\Core\Models\Site;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Pagify\Core\Models\Model>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'locale' => 'en',
            'password' => Hash::make('password'),
            'remember_token' => fake()->regexify('[A-Za-z0-9]{10}'),
        ];
    }
}
