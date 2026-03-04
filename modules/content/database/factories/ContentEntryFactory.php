<?php

namespace Modules\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentType;

class ContentEntryFactory extends Factory
{
    protected $model = ContentEntry::class;

    public function definition(): array
    {
        return [
            'site_id' => null,
            'content_type_id' => ContentType::factory(),
            'slug' => fake()->unique()->slug(),
            'status' => fake()->randomElement(['draft', 'published']),
            'published_at' => null,
            'unpublished_at' => null,
            'scheduled_publish_at' => null,
            'scheduled_unpublish_at' => null,
            'data_json' => [
                'title' => fake()->sentence(),
            ],
            'schedule_metadata_json' => [],
        ];
    }
}
