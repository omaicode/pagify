<?php

namespace Modules\Content\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Content\Models\ContentSchemaMigrationPlan;
use Modules\Content\Services\SchemaMigrationPlanner;

class QueueSchemaMigrationPlanJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $planId)
    {
    }

    public function handle(SchemaMigrationPlanner $planner): void
    {
        $plan = ContentSchemaMigrationPlan::query()
            ->withoutGlobalScopes()
            ->find($this->planId);

        if ($plan === null) {
            return;
        }

        $planner->buildPlan($plan);
    }
}
