<?php

namespace Pagify\Content\Http\Controllers\Admin;

use Illuminate\Support\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Content\Http\Requests\Admin\StoreContentEntryRequest;
use Pagify\Content\Http\Requests\Admin\UpdateContentEntryRequest;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentType;
use Pagify\Content\Services\ContentEntryService;
use Pagify\Content\Services\EntrySchemaResolver;
use Pagify\Content\Services\PublishingWorkflowService;
use Pagify\Content\Services\RelationResolver;
use Pagify\Core\Services\AuditLogger;

class ContentEntryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContentEntryService $contentEntryService,
        private readonly EntrySchemaResolver $schemaResolver,
        private readonly PublishingWorkflowService $publishingWorkflowService,
        private readonly RelationResolver $relationResolver,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function index(string $contentTypeSlug): Response
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $this->authorize('viewAny', [ContentEntry::class, $contentType]);

        $entries = ContentEntry::query()
            ->where('content_type_id', $contentType->id)
            ->latest('id')
            ->paginate(20);

        $hydratedRelations = $this->relationResolver->hydrateForEntries($entries->getCollection());

        $entriesPayload = $entries->through(static function (ContentEntry $entry) use ($contentType, $hydratedRelations): array {
            $relations = (array) ($hydratedRelations[$entry->id] ?? []);

            return [
                'id' => $entry->id,
                'slug' => $entry->slug,
                'status' => $entry->status,
                'relations' => $relations,
                'relation_slugs' => collect($relations)
                    ->flatMap(static fn (array $items): array => array_values($items))
                    ->pluck('target_slug')
                    ->filter()
                    ->values()
                    ->all(),
                'routes' => [
                    'edit' => route('content.admin.entries.edit', [$contentType->slug, $entry->id]),
                ],
            ];
        });

        return Inertia::render('Content/Entries/Index', [
            'contentType' => [
                'id' => $contentType->id,
                'name' => $contentType->name,
                'slug' => $contentType->slug,
            ],
            'entries' => $entriesPayload,
            'formFields' => $this->schemaResolver->formDefinition($contentType),
            'routes' => [
                'create' => route('content.admin.entries.create', $contentType->slug),
                'typeEdit' => route('content.admin.types.edit', $contentType),
            ],
        ]);
    }

    public function create(string $contentTypeSlug): Response
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $this->authorize('create', [ContentEntry::class, $contentType]);

        return Inertia::render('Content/Entries/Create', [
            'contentType' => [
                'id' => $contentType->id,
                'name' => $contentType->name,
                'slug' => $contentType->slug,
            ],
            'formFields' => $this->schemaResolver->formDefinition($contentType),
            'defaultStatus' => 'draft',
            'routes' => [
                'store' => route('content.admin.entries.store', $contentType->slug),
                'index' => route('content.admin.entries.index', $contentType->slug),
            ],
        ]);
    }

    public function store(StoreContentEntryRequest $request, string $contentTypeSlug): RedirectResponse
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $this->authorize('create', [ContentEntry::class, $contentType]);

        /** @var \Pagify\Core\Models\Admin|null $admin */
        $admin = $request->user('web');

        try {
            $entry = $this->contentEntryService->create($contentType, $request->validated(), $admin?->id);
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
            ->with('status', __('content::messages.status.content_entry_created'));
    }

    public function edit(string $contentTypeSlug, int $entryId): Response
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $entry = $this->resolveEntry($contentType, $entryId);
        $this->authorize('update', [ContentEntry::class, $contentType, $entry]);

        return Inertia::render('Content/Entries/Edit', [
            'contentType' => [
                'id' => $contentType->id,
                'name' => $contentType->name,
                'slug' => $contentType->slug,
            ],
            'entry' => [
                'id' => $entry->id,
                'slug' => $entry->slug,
                'status' => $entry->status,
                'data' => (array) ($entry->data_json ?? []),
                'published_at' => $entry->published_at?->toDateTimeString(),
                'scheduled_publish_at' => $entry->scheduled_publish_at?->format('Y-m-d\\TH:i'),
                'scheduled_unpublish_at' => $entry->scheduled_unpublish_at?->format('Y-m-d\\TH:i'),
            ],
            'formFields' => $this->schemaResolver->formDefinition($contentType),
            'resolvedRelations' => $this->relationResolver->hydrateForEntry($entry),
            'publishActionsAllowed' => [
                'publish' => request()->user('web')?->can('publish', [ContentEntry::class, $contentType, $entry]) ?? false,
                'unpublish' => request()->user('web')?->can('unpublish', [ContentEntry::class, $contentType, $entry]) ?? false,
                'schedule' => request()->user('web')?->can('schedule', [ContentEntry::class, $contentType, $entry]) ?? false,
            ],
            'routes' => [
                'update' => route('content.admin.entries.update', [$contentType->slug, $entry->id]),
                'destroy' => route('content.admin.entries.destroy', [$contentType->slug, $entry->id]),
                'publish' => route('content.admin.entries.publish', [$contentType->slug, $entry->id]),
                'unpublish' => route('content.admin.entries.unpublish', [$contentType->slug, $entry->id]),
                'schedule' => route('content.admin.entries.schedule', [$contentType->slug, $entry->id]),
                'revisions' => route('content.admin.entries.revisions.index', [$contentType->slug, $entry->id]),
                'index' => route('content.admin.entries.index', $contentType->slug),
            ],
        ]);
    }

    public function update(UpdateContentEntryRequest $request, string $contentTypeSlug, int $entryId): RedirectResponse
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $entry = $this->resolveEntry($contentType, $entryId);
        $this->authorize('update', [ContentEntry::class, $contentType, $entry]);

        /** @var \Pagify\Core\Models\Admin|null $admin */
        $admin = $request->user('web');

        try {
            $updated = $this->contentEntryService->update($contentType, $entry, $request->validated(), $admin?->id);
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
            ->with('status', __('content::messages.status.content_entry_updated'));
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
            ->with('status', __('content::messages.status.content_entry_deleted'));
    }

    public function publish(Request $request, string $contentTypeSlug, int $entryId): RedirectResponse
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $entry = $this->resolveEntry($contentType, $entryId);
        $this->authorize('publish', [ContentEntry::class, $contentType, $entry]);

        /** @var \Pagify\Core\Models\Admin|null $admin */
        $admin = $request->user('web');

        $this->publishingWorkflowService->publishNow($entry, $admin?->id);

        return redirect()
            ->route('content.admin.entries.edit', [$contentType->slug, $entry->id])
            ->with('status', __('content::messages.status.content_entry_published'));
    }

    public function unpublish(Request $request, string $contentTypeSlug, int $entryId): RedirectResponse
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $entry = $this->resolveEntry($contentType, $entryId);
        $this->authorize('unpublish', [ContentEntry::class, $contentType, $entry]);

        /** @var \Pagify\Core\Models\Admin|null $admin */
        $admin = $request->user('web');

        $this->publishingWorkflowService->unpublishNow($entry, $admin?->id);

        return redirect()
            ->route('content.admin.entries.edit', [$contentType->slug, $entry->id])
            ->with('status', __('content::messages.status.content_entry_moved_to_draft'));
    }

    public function schedule(Request $request, string $contentTypeSlug, int $entryId): RedirectResponse
    {
        $contentType = $this->resolveType($contentTypeSlug);
        $entry = $this->resolveEntry($contentType, $entryId);
        $this->authorize('schedule', [ContentEntry::class, $contentType, $entry]);

        $validated = $request->validate([
            'scheduled_publish_at' => ['nullable', 'date', 'required_without:scheduled_unpublish_at'],
            'scheduled_unpublish_at' => ['nullable', 'date', 'after:scheduled_publish_at', 'required_without:scheduled_publish_at'],
        ]);

        /** @var \Pagify\Core\Models\Admin|null $admin */
        $admin = $request->user('web');

        $publishAt = isset($validated['scheduled_publish_at']) && $validated['scheduled_publish_at'] !== null
            ? Carbon::parse((string) $validated['scheduled_publish_at'])
            : null;

        $unpublishAt = isset($validated['scheduled_unpublish_at']) && $validated['scheduled_unpublish_at'] !== null
            ? Carbon::parse((string) $validated['scheduled_unpublish_at'])
            : null;

        $this->publishingWorkflowService->schedule($entry, $publishAt, $unpublishAt, $admin?->id);

        return redirect()
            ->route('content.admin.entries.edit', [$contentType->slug, $entry->id])
            ->with('status', __('content::messages.status.content_schedule_updated'));
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
