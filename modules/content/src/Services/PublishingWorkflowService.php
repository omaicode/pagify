<?php

namespace Pagify\Content\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Pagify\Content\Models\ContentEntry;
use Pagify\Core\Events\EntryPublished;
use Pagify\Core\Services\AuditLogger;
use Pagify\Core\Services\EventBus;
use RuntimeException;

class PublishingWorkflowService
{
    public function __construct(
        private readonly EntryRevisionService $entryRevisionService,
        private readonly EventBus $eventBus,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function publishNow(ContentEntry $entry, ?int $adminId = null, string $source = 'manual'): ContentEntry
    {
        $beforeSnapshot = $this->entryRevisionService->snapshotFromEntry($entry);

        return DB::transaction(function () use ($entry, $adminId, $source, $beforeSnapshot): ContentEntry {
            $entry->fill([
                'status' => 'published',
                'published_at' => Carbon::now(),
                'unpublished_at' => null,
                'scheduled_publish_at' => null,
            ])->save();

            $entry = $entry->refresh();

            $this->entryRevisionService->snapshot(
                entry: $entry,
                action: $source === 'scheduler' ? 'auto_published' : 'published',
                adminId: $adminId,
                beforeSnapshot: $beforeSnapshot,
            );

            $this->emitStatusTransition($entry, $beforeSnapshot, 'published', $source);

            $this->eventBus->publish(new EntryPublished(
                entryType: 'content.' . $entry->contentType->slug,
                entryId: $entry->id,
                payload: [
                    'content_type_id' => $entry->content_type_id,
                    'content_type_slug' => $entry->contentType->slug,
                    'entry_slug' => $entry->slug,
                    'source' => $source,
                ],
            ));

            $this->auditLogger->log(
                action: 'content.entry.published',
                entityType: ContentEntry::class,
                entityId: $entry->id,
                metadata: [
                    'entry_slug' => $entry->slug,
                    'content_type_id' => $entry->content_type_id,
                    'source' => $source,
                ],
            );

            return $entry;
        });
    }

    public function unpublishNow(ContentEntry $entry, ?int $adminId = null, string $source = 'manual'): ContentEntry
    {
        $beforeSnapshot = $this->entryRevisionService->snapshotFromEntry($entry);

        return DB::transaction(function () use ($entry, $adminId, $source, $beforeSnapshot): ContentEntry {
            $entry->fill([
                'status' => 'draft',
                'published_at' => null,
                'unpublished_at' => Carbon::now(),
                'scheduled_unpublish_at' => null,
            ])->save();

            $entry = $entry->refresh();

            $this->entryRevisionService->snapshot(
                entry: $entry,
                action: $source === 'scheduler' ? 'auto_unpublished' : 'unpublished',
                adminId: $adminId,
                beforeSnapshot: $beforeSnapshot,
            );

            $this->emitStatusTransition($entry, $beforeSnapshot, 'unpublished', $source);

            $this->auditLogger->log(
                action: 'content.entry.unpublished',
                entityType: ContentEntry::class,
                entityId: $entry->id,
                metadata: [
                    'entry_slug' => $entry->slug,
                    'content_type_id' => $entry->content_type_id,
                    'source' => $source,
                ],
            );

            return $entry;
        });
    }

    public function schedule(ContentEntry $entry, ?Carbon $publishAt, ?Carbon $unpublishAt, ?int $adminId = null): ContentEntry
    {
        if ($publishAt === null && $unpublishAt === null) {
            throw new RuntimeException('At least one schedule datetime is required.');
        }

        $beforeSnapshot = $this->entryRevisionService->snapshotFromEntry($entry);

        return DB::transaction(function () use ($entry, $publishAt, $unpublishAt, $adminId, $beforeSnapshot): ContentEntry {
            $nextStatus = $entry->status;

            if ($publishAt !== null && $entry->status !== 'published') {
                $nextStatus = 'scheduled';
            }

            $metadata = (array) ($entry->schedule_metadata_json ?? []);
            $nowIso = Carbon::now()->toIso8601String();

            if ($publishAt !== null) {
                $metadata['publish_requested_at'] = $nowIso;
                $metadata['publish_requested_by_admin_id'] = $adminId;
            }

            if ($unpublishAt !== null) {
                $metadata['unpublish_requested_at'] = $nowIso;
                $metadata['unpublish_requested_by_admin_id'] = $adminId;
            }

            $entry->fill([
                'status' => $nextStatus,
                'scheduled_publish_at' => $publishAt,
                'scheduled_unpublish_at' => $unpublishAt,
                'schedule_metadata_json' => $metadata,
            ])->save();

            $entry = $entry->refresh();

            $this->entryRevisionService->snapshot(
                entry: $entry,
                action: 'scheduled',
                adminId: $adminId,
                beforeSnapshot: $beforeSnapshot,
                metadata: [
                    'scheduled_publish_at' => $publishAt?->toIso8601String(),
                    'scheduled_unpublish_at' => $unpublishAt?->toIso8601String(),
                ],
            );

            $this->emitStatusTransition($entry, $beforeSnapshot, 'scheduled', 'manual');

            $this->auditLogger->log(
                action: 'content.entry.scheduled',
                entityType: ContentEntry::class,
                entityId: $entry->id,
                metadata: [
                    'entry_slug' => $entry->slug,
                    'content_type_id' => $entry->content_type_id,
                    'scheduled_publish_at' => $publishAt?->toIso8601String(),
                    'scheduled_unpublish_at' => $unpublishAt?->toIso8601String(),
                ],
            );

            return $entry;
        });
    }

    public function processDueForEntry(ContentEntry $entry): bool
    {
        $now = Carbon::now();
        $processed = false;

        $entry = $entry->refresh();

        if ($entry->scheduled_publish_at !== null && $entry->scheduled_publish_at <= $now && $entry->status !== 'published') {
            $entry = $this->publishNow($entry, null, 'scheduler');
            $processed = true;
        }

        if ($entry->scheduled_unpublish_at !== null && $entry->scheduled_unpublish_at <= $now && $entry->status === 'published') {
            $this->unpublishNow($entry, null, 'scheduler');
            $processed = true;
        }

        return $processed;
    }

    /**
     * @param array<string, mixed> $beforeSnapshot
     */
    private function emitStatusTransition(ContentEntry $entry, array $beforeSnapshot, string $transition, string $source): void
    {
        $payload = [
            'entry_id' => $entry->id,
            'entry_slug' => $entry->slug,
            'content_type_id' => $entry->content_type_id,
            'content_type_slug' => $entry->contentType->slug,
            'from_status' => (string) ($beforeSnapshot['status'] ?? 'draft'),
            'to_status' => $entry->status,
            'transition' => $transition,
            'source' => $source,
        ];

        $this->eventBus->publish('content.entry.status.changed', $payload);
        $this->eventBus->emitHook('content.entry.status.changed', $payload);

        if ($transition === 'scheduled') {
            $this->eventBus->emitHook('content.entry.scheduled', $payload);

            return;
        }

        if ($transition === 'published') {
            $this->eventBus->emitHook('content.entry.published', $payload);

            return;
        }

        $this->eventBus->emitHook('content.entry.unpublished', $payload);
    }
}
