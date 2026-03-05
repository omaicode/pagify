<?php

namespace Pagify\Content\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Pagify\Content\Http\Requests\Api\ListContentEntriesRequest;
use Pagify\Content\Http\Requests\Api\ShowContentEntryRequest;
use Pagify\Content\Http\Resources\ContentEntryResource;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentType;
use Pagify\Content\Services\RelationResolver;
use Pagify\Core\Http\Controllers\Api\ApiController;

class ContentApiController extends ApiController
{
    public function __construct(private readonly RelationResolver $relationResolver)
    {
    }

    public function index(ListContentEntriesRequest $request, string $contentTypeSlug): JsonResponse
    {
        $contentType = $this->resolveContentType($contentTypeSlug);
        $this->authorize('apiRead', [ContentEntry::class, $contentType]);

        $validated = $request->validated();

        $query = ContentEntry::query()
            ->with('contentType')
            ->where('content_type_id', $contentType->id);

        if (! empty($validated['status'])) {
            $query->where('status', (string) $validated['status']);
        }

        if (! empty($validated['q'])) {
            $keyword = (string) $validated['q'];
            $query->where('slug', 'like', '%' . $keyword . '%');
        }

        $this->applyFieldFilters($query, $contentType, (array) ($validated['filter'] ?? []));

        $sortBy = (string) ($validated['sort_by'] ?? 'id');
        $sortDir = (string) ($validated['sort_dir'] ?? 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = (int) ($validated['per_page'] ?? 20);
        $paginator = $query->paginate($perPage)->appends($request->query());

        $collection = $paginator->getCollection();
        $relations = $this->relationResolver->hydrateForEntries($collection);

        $collection->transform(static function (ContentEntry $entry) use ($relations): ContentEntry {
            $entry->setAttribute('resolved_relations', $relations[$entry->id] ?? []);

            return $entry;
        });

        $resources = ContentEntryResource::collection($collection)->resolve();

        return $this->success($resources, meta: [
            'content_type' => [
                'id' => $contentType->id,
                'slug' => $contentType->slug,
                'name' => $contentType->name,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'sort' => [
                'by' => $sortBy,
                'dir' => $sortDir,
            ],
        ]);
    }

    public function show(ShowContentEntryRequest $request, string $contentTypeSlug, string $entrySlug): JsonResponse
    {
        $contentType = $this->resolveContentType($contentTypeSlug);
        $this->authorize('apiRead', [ContentEntry::class, $contentType]);

        $entry = ContentEntry::query()
            ->with('contentType')
            ->where('content_type_id', $contentType->id)
            ->where('slug', $entrySlug)
            ->first();

        if ($entry === null) {
            return $this->error(__('content::messages.api.content_entry_not_found'), 404, 'CONTENT_ENTRY_NOT_FOUND');
        }

        $entry->setAttribute('resolved_relations', $this->relationResolver->hydrateForEntry($entry));

        return $this->success((new ContentEntryResource($entry))->resolve(), meta: [
            'content_type' => [
                'id' => $contentType->id,
                'slug' => $contentType->slug,
                'name' => $contentType->name,
            ],
        ]);
    }

    private function resolveContentType(string $contentTypeSlug): ContentType
    {
        return ContentType::query()
            ->with('fields')
            ->where('slug', $contentTypeSlug)
            ->firstOrFail();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFieldFilters(Builder $query, ContentType $contentType, array $filters): void
    {
        if ($filters === []) {
            return;
        }

        $allowedKeys = $contentType->fields
            ->pluck('key')
            ->map(static fn ($key): string => (string) $key)
            ->all();

        foreach ($filters as $fieldKey => $value) {
            $fieldKey = (string) $fieldKey;

            if (! in_array($fieldKey, $allowedKeys, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $query->where('data_json->' . $fieldKey, (string) $value);
        }
    }
}
