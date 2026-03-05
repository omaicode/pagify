<?php

namespace Pagify\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentEntryRevision;
use Pagify\Core\Models\Admin;

class ContentEntryRevisionFactory extends Factory
{
    protected $model = ContentEntryRevision::class;

    public function definition(): array
    {
        return [
            'content_entry_id' => ContentEntry::factory(),
            'revision_no' => 1,
            'action' => fake()->randomElement(['created', 'updated', 'published']),
            'snapshot_json' => [
                'slug' => fake()->slug(),
                'status' => 'draft',
                'data' => ['title' => fake()->sentence()],
            ],
            'diff_json' => ['changed' => true, 'changes' => []],
            'created_by_admin_id' => Admin::factory(),
            'metadata_json' => [],
        ];
    }
}
