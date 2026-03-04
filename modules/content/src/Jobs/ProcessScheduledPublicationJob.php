<?php

namespace Modules\Content\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Services\PublishingWorkflowService;

class ProcessScheduledPublicationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $entryId)
    {
    }

    public function handle(PublishingWorkflowService $publishingWorkflowService): void
    {
        $entry = ContentEntry::query()
            ->withoutGlobalScopes()
            ->find($this->entryId);

        if ($entry === null) {
            return;
        }

        $publishingWorkflowService->processDueForEntry($entry);
    }
}
