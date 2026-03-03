<?php

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Core\Models\AuditLog;

class CleanupAuditLogsCommand extends Command
{
    protected $signature = 'core:audit:cleanup {--days= : Delete logs older than this number of days} {--chunk= : Batch size for delete operation} {--dry-run : Show affected rows without deleting}';

    protected $description = 'Delete old audit logs based on retention policy.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('core.audit.retention_days', 180));
        $chunk = (int) ($this->option('chunk') ?: config('core.audit.cleanup_chunk_size', 1000));
        $dryRun = (bool) $this->option('dry-run');

        if ($days <= 0) {
            $this->error('Retention days must be greater than zero.');

            return self::INVALID;
        }

        if ($chunk <= 0) {
            $this->error('Chunk size must be greater than zero.');

            return self::INVALID;
        }

        $threshold = Carbon::now()->subDays($days);
        $query = AuditLog::query()->where('created_at', '<', $threshold);

        $total = (clone $query)->count();

        if ($dryRun) {
            $this->info(sprintf('Dry run: %d audit logs older than %s would be deleted.', $total, $threshold->toDateTimeString()));

            return self::SUCCESS;
        }

        $deleted = 0;

        do {
            $ids = (clone $query)
                ->orderBy('id')
                ->limit($chunk)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += AuditLog::query()->whereIn('id', $ids->all())->delete();
        } while (true);

        $this->info(sprintf('Deleted %d/%d audit logs older than %s.', $deleted, $total, $threshold->toDateTimeString()));

        return self::SUCCESS;
    }
}
