<?php

namespace Modules\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Content\Models\ContentType;
use Modules\Core\Models\Site;

class ContentTypeFactory extends Factory
{
    protected $model = ContentType::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug();

        return [
            'site_id' => Site::factory(),
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'description' => fake()->sentence(),
            'is_active' => true,
            'schema_json' => [
                'version' => 1,
                'fields' => [],
            ],
        ];
    }
}
