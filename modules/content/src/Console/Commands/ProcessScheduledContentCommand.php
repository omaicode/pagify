<?php

namespace Pagify\Content\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Pagify\Content\Jobs\ProcessScheduledPublicationJob;
use Pagify\Content\Models\ContentEntry;

class ProcessScheduledContentCommand extends Command
{
    protected $signature = 'content:publication:process {--limit=200 : Max number of scheduled entries to process}';

    protected $description = 'Process scheduled publish and unpublish transitions for content entries.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $now = Carbon::now();

        $entryIds = ContentEntry::query()
            ->withoutGlobalScopes()
            ->where(static function ($query) use ($now): void {
                $query
                    ->whereNotNull('scheduled_publish_at')
                    ->where('scheduled_publish_at', '<=', $now)
                    ->orWhere(static function ($inner) use ($now): void {
                        $inner
                            ->where('status', 'published')
                            ->whereNotNull('scheduled_unpublish_at')
                            ->where('scheduled_unpublish_at', '<=', $now);
                    });
            })
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        if ($entryIds->isEmpty()) {
            $this->info('No scheduled content entries are due for processing.');

            return self::SUCCESS;
        }

        foreach ($entryIds as $entryId) {
            Bus::dispatchSync(new ProcessScheduledPublicationJob((int) $entryId));
        }

        $this->info(sprintf('Processed %d scheduled content entr%s.', $entryIds->count(), $entryIds->count() === 1 ? 'y' : 'ies'));

        return self::SUCCESS;
    }
}
