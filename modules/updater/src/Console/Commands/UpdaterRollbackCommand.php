<?php

namespace Pagify\Updater\Console\Commands;

use Illuminate\Console\Command;
use Pagify\Updater\Models\UpdaterExecution;
use Pagify\Updater\Services\UpdaterExecutionService;

class UpdaterRollbackCommand extends Command
{
    protected $signature = 'updater:rollback {execution : Execution id}';

    protected $description = 'Rollback an updater execution using lock snapshot';

    public function handle(UpdaterExecutionService $service): int
    {
        $executionId = (int) $this->argument('execution');
        $execution = UpdaterExecution::query()->find($executionId);

        if ($execution === null) {
            $this->error(sprintf('Execution [%d] not found.', $executionId));

            return self::FAILURE;
        }

        $result = $service->rollback($execution);

        if (! $result['ok']) {
            $this->error((string) $result['message']);

            return self::FAILURE;
        }

        $this->info((string) $result['message']);

        return self::SUCCESS;
    }
}
