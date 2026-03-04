<?php

namespace Modules\Content\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentType;
use Modules\Core\Http\Controllers\Api\ApiController;

class AdminRelationPickerController extends ApiController
{
    public function __invoke(Request $request, string $contentTypeSlug): JsonResponse
    {
        $contentType = ContentType::query()
            ->with('fields')
            ->where('slug', $contentTypeSlug)
            ->firstOrFail();

        $admin = $request->user('web');
        $canView = $admin?->can('content.relation.view')
            || $admin?->can('viewAny', [ContentEntry::class, $contentType]);

        abort_if(! $canView, 403);

        $validated = $request->validate([
            'field_key' => ['required', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $fieldKey = (string) $validated['field_key'];
        $field = $contentType->fields
            ->first(static fn ($item): bool => (string) $item->key === $fieldKey && (string) $item->field_type === 'relation');

        if ($field === null) {
            return $this->error('Relation field not found.', 422, 'RELATION_FIELD_NOT_FOUND');
        }

        $config = (array) ($field->config_json ?? []);
        $targetTypeSlug = isset($config['target_content_type_slug']) ? (string) $config['target_content_type_slug'] : '';
        $relationType = isset($config['relation_type']) ? (string) $config['relation_type'] : 'hasOne';
        $limit = (int) ($validated['limit'] ?? 20);

        $targetContentType = $targetTypeSlug !== ''
            ? ContentType::query()->where('slug', $targetTypeSlug)->first()
            : null;

        $query = ContentEntry::query()->orderByDesc('id');

        if ($targetContentType !== null) {
            $query->where('content_type_id', $targetContentType->id);
        }

        if (! empty($validated['q'])) {
            $keyword = (string) $validated['q'];
            $query->where('slug', 'like', '%' . $keyword . '%');
        }

        $options = $query
            ->limit($limit)
            ->get()
            ->map(static fn (ContentEntry $entry): array => [
                'id' => $entry->id,
                'slug' => $entry->slug,
                'status' => $entry->status,
            ])
            ->values();

        return $this->success([
            'field_key' => $fieldKey,
            'relation_type' => $relationType,
            'target_content_type_slug' => $targetContentType?->slug,
            'options' => $options,
        ]);
    }
}
