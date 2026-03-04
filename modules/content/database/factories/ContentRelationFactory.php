<?php

namespace Modules\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentRelation;

class ContentRelationFactory extends Factory
{
    protected $model = ContentRelation::class;

    public function definition(): array
    {
        return [
            'site_id' => null,
            'source_entry_id' => ContentEntry::factory(),
            'target_entry_id' => ContentEntry::factory(),
            'field_key' => 'related',
            'relation_type' => fake()->randomElement(['hasOne', 'hasMany', 'manyToMany']),
            'position' => 0,
            'metadata_json' => [],
        ];
    }
}
