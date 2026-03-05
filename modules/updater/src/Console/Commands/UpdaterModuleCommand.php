<?php

namespace Pagify\Updater\Console\Commands;

use Illuminate\Console\Command;
use Pagify\Updater\Jobs\RunUpdaterExecutionJob;
use Pagify\Updater\Services\UpdaterExecutionService;

class UpdaterModuleCommand extends Command
{
    protected $signature = 'updater:module {module : Module slug} {--queue : Dispatch to queue instead of running immediately}';

    protected $description = 'Update one module package via Composer';

    public function handle(UpdaterExecutionService $service): int
    {
        $module = (string) $this->argument('module');
        $execution = $service->createExecution(null, 'module', $module);

        if ((bool) $this->option('queue')) {
            RunUpdaterExecutionJob::dispatch($execution->id);
            $this->info(sprintf('Queued updater execution #%d for module [%s].', $execution->id, $module));

            return self::SUCCESS;
        }

        $service->execute($execution);

        $fresh = $execution->fresh();

        if ($fresh?->status !== 'succeeded') {
            $this->error(sprintf('Updater execution #%d failed.', $execution->id));

            return self::FAILURE;
        }

        $this->info(sprintf('Updater execution #%d succeeded.', $execution->id));

        return self::SUCCESS;
    }
}
