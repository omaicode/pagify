<?php

namespace Modules\Content\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Content\Models\ContentSchemaMigrationPlan;
use Modules\Content\Services\ContentSchemaMigrationExecutor;
use Modules\Content\Services\SchemaMigrationPlanner;
use Throwable;

class QueueSchemaMigrationPlanJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 20, 60];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('content-schema-plan-' . $this->planId))
                ->releaseAfter(10),
        ];
    }

    public function __construct(public readonly int $planId)
    {
    }

    public function handle(SchemaMigrationPlanner $planner, ContentSchemaMigrationExecutor $executor): void
    {
        $plan = ContentSchemaMigrationPlan::query()
            ->withoutGlobalScopes()
            ->find($this->planId);

        if ($plan === null) {
            return;
        }

        try {
            if ($plan->status === 'applied') {
                return;
            }

            $planned = $planner->buildPlan($plan);
            $executor->execute($planned);
        } catch (Throwable $throwable) {
            $plan->fill([
                'status' => 'retryable',
                'error_message' => $throwable->getMessage(),
            ])->save();

            Log::error('Content schema migration plan execution failed.', [
                'plan_id' => $plan->id,
                'content_type_id' => $plan->content_type_id,
                'attempts' => $plan->execution_attempts,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }
}
