<?php

namespace Pagify\Updater\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pagify\Updater\Models\UpdaterExecution;
use Pagify\Updater\Services\UpdaterExecutionService;

class RunUpdaterExecutionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $executionId,
    ) {
        $connection = config('updater.queue.connection');
        $queue = config('updater.queue.name', 'default');

        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }

        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function handle(UpdaterExecutionService $service): void
    {
        $execution = UpdaterExecution::query()->find($this->executionId);

        if ($execution === null) {
            return;
        }

        $service->execute($execution);
    }
}
