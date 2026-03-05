<?php

namespace Pagify\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pagify\Content\Models\ContentSchemaMigrationPlan;
use Pagify\Content\Models\ContentType;
use Pagify\Core\Models\Admin;

class ContentSchemaMigrationPlanFactory extends Factory
{
    protected $model = ContentSchemaMigrationPlan::class;

    public function definition(): array
    {
        return [
            'site_id' => null,
            'content_type_id' => ContentType::factory(),
            'requested_by_admin_id' => Admin::factory(),
            'status' => 'queued',
            'schema_before_json' => [],
            'schema_after_json' => [],
            'plan_json' => null,
            'error_message' => null,
            'planned_at' => null,
        ];
    }
}
