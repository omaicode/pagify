<?php

namespace Modules\Content\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Content\Http\Requests\Admin\StoreContentEntryRequest;
use Modules\Content\Http\Requests\Admin\UpdateContentEntryRequest;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentType;
use Modules\Content\Services\ContentEntryService;
use Modules\Content\Services\EntrySchemaResolver;
use Modules\Core\Services\AuditLogger;

class ContentEntryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContentEntryService $contentEntryService,
        private readonly EntrySchemaResolver $schemaResolver,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function index(string $contentTypeSlug): View
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $this->authorize('viewAny', [ContentEntry::class, $contentType]);

        $entries = ContentEntry::query()
            ->where('content_type_id', $contentType->id)
            ->latest('id')
            ->paginate(20);

        return view('content::entries.index', [
            'contentType' => $contentType,
            'entries' => $entries,
            'formFields' => $this->schemaResolver->formDefinition($contentType),
        ]);
    }

    public function create(string $contentTypeSlug): View
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $this->authorize('create', [ContentEntry::class, $contentType]);

        return view('content::entries.create', [
            'contentType' => $contentType,
            'formFields' => $this->schemaResolver->formDefinition($contentType),
            'defaultStatus' => 'draft',
        ]);
    }

    public function store(StoreContentEntryRequest $request, string $contentTypeSlug): RedirectResponse
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $this->authorize('create', [ContentEntry::class, $contentType]);

        try {
            $entry = $this->contentEntryService->create($contentType, $request->validated());
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $this->auditLogger->log(
            action: 'content.entry.created',
            entityType: ContentEntry::class,
            entityId: $entry->id,
            metadata: [
                'content_type_id' => $contentType->id,
                'content_type_slug' => $contentType->slug,
                'entry_slug' => $entry->slug,
            ],
        );

        return redirect()
            ->route('content.admin.entries.edit', [$contentType->slug, $entry->id])
            ->with('status', 'Content entry created successfully.');
    }

    public function edit(string $contentTypeSlug, int $entryId): View
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $entry = $this->resolveEntry($contentType, $entryId);
        $this->authorize('update', [ContentEntry::class, $contentType, $entry]);

        return view('content::entries.edit', [
            'contentType' => $contentType,
            'entry' => $entry,
            'formFields' => $this->schemaResolver->formDefinition($contentType),
        ]);
    }

    public function update(UpdateContentEntryRequest $request, string $contentTypeSlug, int $entryId): RedirectResponse
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $entry = $this->resolveEntry($contentType, $entryId);
        $this->authorize('update', [ContentEntry::class, $contentType, $entry]);

        try {
            $updated = $this->contentEntryService->update($contentType, $entry, $request->validated());
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $this->auditLogger->log(
            action: 'content.entry.updated',
            entityType: ContentEntry::class,
            entityId: $updated->id,
            metadata: [
                'content_type_id' => $contentType->id,
                'content_type_slug' => $contentType->slug,
                'entry_slug' => $updated->slug,
            ],
        );

        return redirect()
            ->route('content.admin.entries.edit', [$contentType->slug, $updated->id])
            ->with('status', 'Content entry updated successfully.');
    }

    public function destroy(Request $request, string $contentTypeSlug, int $entryId): RedirectResponse
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $entry = $this->resolveEntry($contentType, $entryId);
        $this->authorize('delete', [ContentEntry::class, $contentType, $entry]);

        $entryIdForAudit = $entry->id;
        $entrySlug = $entry->slug;

        $this->contentEntryService->delete($entry);

        $this->auditLogger->log(
            action: 'content.entry.deleted',
            entityType: ContentEntry::class,
            entityId: $entryIdForAudit,
            metadata: [
                'content_type_id' => $contentType->id,
                'content_type_slug' => $contentType->slug,
                'entry_slug' => $entrySlug,
            ],
        );

        return redirect()
            ->route('content.admin.entries.index', $contentType->slug)
            ->with('status', 'Content entry deleted successfully.');
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
}
