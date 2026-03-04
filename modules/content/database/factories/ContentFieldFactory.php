<?php

namespace Modules\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Content\Models\ContentField;
use Modules\Content\Models\ContentType;

class ContentFieldFactory extends Factory
{
    protected $model = ContentField::class;

    public function definition(): array
    {
        return [
            'content_type_id' => ContentType::factory(),
            'key' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'field_type' => 'text',
            'config_json' => [],
            'validation_json' => [],
            'conditional_json' => [],
            'sort_order' => 0,
            'is_required' => false,
            'is_localized' => false,
        ];
    }
}
