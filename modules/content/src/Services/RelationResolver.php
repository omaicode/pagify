<?php

namespace Modules\Content\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentField;
use Modules\Content\Models\ContentRelation;
use Modules\Content\Models\ContentType;

class RelationResolver
{
    /**
     * @param array<string, mixed> $entryData
     */
    public function syncForEntry(ContentEntry $entry, ContentType $contentType, array $entryData): void
    {
        $relationFields = $contentType->fields
            ->filter(static fn (ContentField $field): bool => (string) $field->field_type === 'relation');

        foreach ($relationFields as $field) {
            $this->syncFieldRelations($entry, $field, $entryData[(string) $field->key] ?? null);
        }
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function hydrateForEntry(ContentEntry $entry): array
    {
        $relations = ContentRelation::query()
            ->where('source_entry_id', $entry->id)
            ->with(['targetEntry.contentType'])
            ->orderBy('field_key')
            ->orderBy('position')
            ->get();

        return $this->formatHydratedRelations($relations);
    }

    /**
     * @param iterable<int, ContentEntry> $entries
     * @return array<int, array<string, array<int, array<string, mixed>>>>
     */
    public function hydrateForEntries(iterable $entries): array
    {
        $entryIds = collect($entries)
            ->map(static fn (ContentEntry $entry): int => (int) $entry->id)
            ->values();

        if ($entryIds->isEmpty()) {
            return [];
        }

        $relations = ContentRelation::query()
            ->whereIn('source_entry_id', $entryIds)
            ->with(['targetEntry.contentType'])
            ->orderBy('field_key')
            ->orderBy('position')
            ->get()
            ->groupBy('source_entry_id');

        $result = [];

        foreach ($relations as $sourceEntryId => $groupedRelations) {
            $result[(int) $sourceEntryId] = $this->formatHydratedRelations($groupedRelations);
        }

        return $result;
    }

    /**
     * @return array<int, int>
     */
    public function normalizeRelationValue(ContentField $field, mixed $value): array
    {
        $relationType = $this->relationTypeFromField($field);

        if ($value === null || $value === '') {
            return [];
        }

        if ($relationType === 'hasOne') {
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }

            return $value === null || $value === '' ? [] : [(int) $value];
        }

        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== '');
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn (mixed $item): bool => $item !== null && $item !== '')
            ->map(static fn (mixed $item): int => (int) $item)
            ->filter(static fn (int $item): bool => $item > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function syncFieldRelations(ContentEntry $entry, ContentField $field, mixed $rawValue): void
    {
        $relationType = $this->relationTypeFromField($field);
        $targetEntryIds = $this->normalizeRelationValue($field, $rawValue);
        $fieldKey = (string) $field->key;

        if ($relationType === 'hasOne' && count($targetEntryIds) > 1) {
            throw ValidationException::withMessages([
                'data.' . $fieldKey => 'The relation field only accepts one target entry.',
            ]);
        }

        $targetEntries = ContentEntry::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $targetEntryIds)
            ->with('contentType')
            ->get();

        if ($targetEntries->count() !== count($targetEntryIds)) {
            throw ValidationException::withMessages([
                'data.' . $fieldKey => 'One or more relation targets are invalid.',
            ]);
        }

        $targetTypeSlug = $this->targetTypeSlugFromField($field);

        foreach ($targetEntries as $targetEntry) {
            if ($targetEntry->site_id !== $entry->site_id) {
                throw ValidationException::withMessages([
                    'data.' . $fieldKey => 'Cross-site relations are not allowed.',
                ]);
            }

            if ($targetEntry->id === $entry->id) {
                throw ValidationException::withMessages([
                    'data.' . $fieldKey => 'An entry cannot relate to itself.',
                ]);
            }

            if ($targetTypeSlug !== null && (string) $targetEntry->contentType->slug !== $targetTypeSlug) {
                throw ValidationException::withMessages([
                    'data.' . $fieldKey => 'Target entry type is not allowed for this relation field.',
                ]);
            }

            if ($relationType === 'hasOne' && $this->wouldCreateHasOneCycle($entry->id, $targetEntry->id, $fieldKey, $entry->site_id)) {
                throw ValidationException::withMessages([
                    'data.' . $fieldKey => 'Relation would create a cycle.',
                ]);
            }
        }

        ContentRelation::query()
            ->where('source_entry_id', $entry->id)
            ->where('field_key', $fieldKey)
            ->delete();

        foreach (array_values($targetEntryIds) as $position => $targetEntryId) {
            ContentRelation::query()->create([
                'site_id' => $entry->site_id,
                'source_entry_id' => $entry->id,
                'target_entry_id' => $targetEntryId,
                'field_key' => $fieldKey,
                'relation_type' => $relationType,
                'position' => $position,
            ]);
        }
    }

    /**
     * @param EloquentCollection<int, ContentRelation>|Collection<int, ContentRelation> $relations
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function formatHydratedRelations(EloquentCollection|Collection $relations): array
    {
        return $relations
            ->groupBy('field_key')
            ->map(static function (Collection $group): array {
                return $group
                    ->map(static fn (ContentRelation $relation): array => [
                        'relation_id' => $relation->id,
                        'relation_type' => $relation->relation_type,
                        'target_entry_id' => $relation->target_entry_id,
                        'target_slug' => $relation->targetEntry?->slug,
                        'target_status' => $relation->targetEntry?->status,
                        'target_content_type_slug' => $relation->targetEntry?->contentType?->slug,
                    ])
                    ->values()
                    ->all();
            })
            ->all();
    }

    private function relationTypeFromField(ContentField $field): string
    {
        $config = (array) ($field->config_json ?? []);
        $configured = isset($config['relation_type']) ? (string) $config['relation_type'] : 'hasOne';
        $allowed = config('content.relation_types', ['hasOne', 'hasMany', 'manyToMany']);

        return in_array($configured, $allowed, true) ? $configured : 'hasOne';
    }

    private function targetTypeSlugFromField(ContentField $field): ?string
    {
        $configured = (array) ($field->config_json ?? []);
        $slug = isset($configured['target_content_type_slug']) ? (string) $configured['target_content_type_slug'] : '';

        return $slug !== '' ? $slug : null;
    }

    private function wouldCreateHasOneCycle(int $sourceEntryId, int $targetEntryId, string $fieldKey, ?int $siteId): bool
    {
        $current = $targetEntryId;
        $visited = [];

        while (true) {
            if ($current === $sourceEntryId) {
                return true;
            }

            if (in_array($current, $visited, true)) {
                return false;
            }

            $visited[] = $current;

            $next = ContentRelation::query()
                ->withoutGlobalScopes()
                ->where('relation_type', 'hasOne')
                ->where('field_key', $fieldKey)
                ->where('source_entry_id', $current)
                ->when($siteId !== null, static fn ($query) => $query->where('site_id', $siteId))
                ->value('target_entry_id');

            if ($next === null) {
                return false;
            }

            $current = (int) $next;
        }
    }
}
