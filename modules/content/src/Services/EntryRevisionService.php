<?php

namespace Pagify\Content\Services;

use Illuminate\Support\Facades\DB;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentEntryRevision;

class EntryRevisionService
{
    public function __construct(private readonly EntryDiffService $entryDiffService)
    {
    }

    /**
     * @param array<string, mixed>|null $beforeSnapshot
     * @param array<string, mixed> $metadata
     */
    public function snapshot(
        ContentEntry $entry,
        string $action,
        ?int $adminId = null,
        ?array $beforeSnapshot = null,
        array $metadata = [],
    ): ContentEntryRevision {
        return DB::transaction(function () use ($entry, $action, $adminId, $beforeSnapshot, $metadata): ContentEntryRevision {
            $latestRevisionNo = (int) (ContentEntryRevision::query()
                ->where('content_entry_id', $entry->id)
                ->max('revision_no') ?? 0);

            $currentSnapshot = $this->snapshotFromEntry($entry);
            $baseSnapshot = $beforeSnapshot;

            if ($baseSnapshot === null) {
                $latestRevision = ContentEntryRevision::query()
                    ->where('content_entry_id', $entry->id)
                    ->latest('revision_no')
                    ->first();

                $baseSnapshot = (array) ($latestRevision?->snapshot_json ?? []);
            }

            $diff = $this->entryDiffService->diff($baseSnapshot, $currentSnapshot);

            return ContentEntryRevision::query()->create([
                'content_entry_id' => $entry->id,
                'revision_no' => $latestRevisionNo + 1,
                'action' => $action,
                'snapshot_json' => $currentSnapshot,
                'diff_json' => $diff,
                'created_by_admin_id' => $adminId,
                'metadata_json' => $metadata,
            ]);
        });
    }

    public function rollback(ContentEntry $entry, ContentEntryRevision $targetRevision, ?int $adminId = null): ContentEntry
    {
        return DB::transaction(function () use ($entry, $targetRevision, $adminId): ContentEntry {
            $beforeSnapshot = $this->snapshotFromEntry($entry);
            $targetSnapshot = (array) $targetRevision->snapshot_json;

            $entry->fill([
                'slug' => (string) ($targetSnapshot['slug'] ?? $entry->slug),
                'status' => (string) ($targetSnapshot['status'] ?? $entry->status),
                'data_json' => (array) ($targetSnapshot['data'] ?? []),
            ])->save();

            $this->snapshot(
                entry: $entry->refresh(),
                action: 'rollback',
                adminId: $adminId,
                beforeSnapshot: $beforeSnapshot,
                metadata: [
                    'rolled_back_from_revision_id' => $targetRevision->id,
                    'rolled_back_from_revision_no' => $targetRevision->revision_no,
                ],
            );

            return $entry->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotFromEntry(ContentEntry $entry): array
    {
        return [
            'slug' => $entry->slug,
            'status' => $entry->status,
            'data' => (array) ($entry->data_json ?? []),
        ];
    }
}
