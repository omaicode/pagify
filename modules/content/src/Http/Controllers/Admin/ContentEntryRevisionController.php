<?php

namespace Modules\Content\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentEntryRevision;
use Modules\Content\Models\ContentType;
use Modules\Content\Services\EntryDiffService;
use Modules\Content\Services\EntryRevisionService;
use Modules\Core\Services\AuditLogger;

class ContentEntryRevisionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EntryRevisionService $entryRevisionService,
        private readonly EntryDiffService $entryDiffService,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function index(Request $request, string $contentTypeSlug, int $entryId): View
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $entry = $this->resolveEntry($contentType, $entryId);
        $this->authorize('viewRevisions', [ContentEntry::class, $contentType, $entry]);

        $revisions = ContentEntryRevision::query()
            ->where('content_entry_id', $entry->id)
            ->latest('revision_no')
            ->get();

        $leftRevision = null;
        $rightRevision = null;
        $diff = ['changed' => false, 'changes' => []];

        if ($revisions->count() >= 2) {
            $leftRevisionId = (int) ($request->integer('left_revision_id') ?: $revisions->get(1)->id);
            $rightRevisionId = (int) ($request->integer('right_revision_id') ?: $revisions->first()->id);

            $leftRevision = $revisions->firstWhere('id', $leftRevisionId) ?? $revisions->get(1);
            $rightRevision = $revisions->firstWhere('id', $rightRevisionId) ?? $revisions->first();

            if ($leftRevision !== null && $rightRevision !== null) {
                $diff = $this->entryDiffService->diff(
                    (array) ($leftRevision->snapshot_json ?? []),
                    (array) ($rightRevision->snapshot_json ?? []),
                );
            }
        }

        return view('content::entries.revisions', [
            'contentType' => $contentType,
            'entry' => $entry,
            'revisions' => $revisions,
            'leftRevision' => $leftRevision,
            'rightRevision' => $rightRevision,
            'diff' => $diff,
        ]);
    }

    public function rollback(Request $request, string $contentTypeSlug, int $entryId, int $revisionId): RedirectResponse
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $entry = $this->resolveEntry($contentType, $entryId);
        $revision = $this->resolveRevision($entry, $revisionId);

        $this->authorize('rollbackRevision', [ContentEntry::class, $contentType, $entry]);

        /** @var \Modules\Core\Models\Admin|null $admin */
        $admin = $request->user('web');

        $this->entryRevisionService->rollback($entry, $revision, $admin?->id);

        $this->auditLogger->log(
            action: 'content.entry.rollback',
            entityType: ContentEntry::class,
            entityId: $entry->id,
            metadata: [
                'content_type_slug' => $contentType->slug,
                'rolled_back_to_revision_id' => $revision->id,
                'rolled_back_to_revision_no' => $revision->revision_no,
            ],
        );

        return redirect()
            ->route('content.admin.entries.revisions.index', [$contentType->slug, $entry->id])
            ->with('status', 'Entry rollback completed.');
    }

    private function resolveType(string $contentTypeSlug): ContentType
    {
        return ContentType::query()
            ->with('fields')
            ->where('slug', $contentTypeSlug)
            ->firstOrFail();
    }

    private function resolveEntry(ContentType $contentType, int $entryId): ContentEntry
    {
        return ContentEntry::query()
            ->where('content_type_id', $contentType->id)
            ->whereKey($entryId)
            ->firstOrFail();
    }

    private function resolveRevision(ContentEntry $entry, int $revisionId): ContentEntryRevision
    {
        return ContentEntryRevision::query()
            ->where('content_entry_id', $entry->id)
            ->whereKey($revisionId)
            ->firstOrFail();
    }
}
