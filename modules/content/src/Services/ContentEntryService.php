<?php

namespace Modules\Content\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentType;
use Modules\Media\Models\MediaAsset;

class ContentEntryService
{
    public function __construct(
        private readonly EntrySchemaResolver $schemaResolver,
        private readonly EntryRevisionService $entryRevisionService,
        private readonly RelationResolver $relationResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(ContentType $contentType, array $payload, ?int $adminId = null): ContentEntry
    {
        $validated = $this->validatePayload($contentType, $payload);

        return DB::transaction(function () use ($contentType, $validated, $adminId): ContentEntry {
            $entry = ContentEntry::query()->create([
                'content_type_id' => $contentType->id,
                'slug' => (string) $validated['slug'],
                'status' => (string) ($validated['status'] ?? 'draft'),
                'data_json' => $this->normalizeData($contentType, (array) $validated['data']),
            ]);

            $this->relationResolver->syncForEntry($entry, $contentType, (array) $validated['data']);
            $this->syncMediaUsages($entry, $contentType, (array) $validated['data']);

            $this->entryRevisionService->snapshot(
                entry: $entry,
                action: 'created',
                adminId: $adminId,
                beforeSnapshot: [],
            );

            return $entry;
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(ContentType $contentType, ContentEntry $entry, array $payload, ?int $adminId = null): ContentEntry
    {
        $validated = $this->validatePayload($contentType, $payload, $entry);

        return DB::transaction(function () use ($entry, $contentType, $validated, $adminId): ContentEntry {
            $beforeSnapshot = $this->entryRevisionService->snapshotFromEntry($entry);

            $entry->fill([
                'slug' => (string) $validated['slug'],
                'status' => (string) ($validated['status'] ?? $entry->status),
                'data_json' => $this->normalizeData($contentType, (array) $validated['data']),
            ])->save();

            $this->relationResolver->syncForEntry($entry, $contentType, (array) $validated['data']);
            $this->syncMediaUsages($entry, $contentType, (array) $validated['data']);

            $this->entryRevisionService->snapshot(
                entry: $entry,
                action: 'updated',
                adminId: $adminId,
                beforeSnapshot: $beforeSnapshot,
            );

            return $entry->refresh();
        });
    }

    public function delete(ContentEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $this->removeMediaUsages($entry);
            $entry->delete();
        });
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function validatePayload(ContentType $contentType, array $payload, ?ContentEntry $entry = null): array
    {
        $payload = $this->preprocessPayloadByFieldType($contentType, $payload);

        $rules = $this->schemaResolver->buildValidationRules($contentType);
        $rules['slug'][] = Rule::unique('content_entries', 'slug')
            ->where(static fn ($query) => $query
                ->where('site_id', $contentType->site_id)
                ->where('content_type_id', $contentType->id)
            )
            ->ignore($entry?->id);

        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function preprocessPayloadByFieldType(ContentType $contentType, array $payload): array
    {
        $data = (array) ($payload['data'] ?? []);

        foreach ($contentType->fields as $field) {
            $fieldType = (string) $field->field_type;

            if ($fieldType !== 'repeater' && $fieldType !== 'relation') {
                continue;
            }

            $key = (string) $field->key;

            if (! array_key_exists($key, $data)) {
                continue;
            }

            if ($fieldType === 'repeater' && is_string($data[$key])) {
                $decoded = json_decode($data[$key], true);

                if (is_array($decoded)) {
                    $data[$key] = $decoded;
                }
            }

            if ($fieldType === 'relation') {
                $normalizedTargets = $this->relationResolver->normalizeRelationValue($field, $data[$key]);
                $config = (array) ($field->config_json ?? []);
                $relationType = isset($config['relation_type']) ? (string) $config['relation_type'] : 'hasOne';

                $data[$key] = $relationType === 'hasOne'
                    ? ($normalizedTargets[0] ?? null)
                    : $normalizedTargets;
            }
        }

        $payload['data'] = $data;

        return $payload;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeData(ContentType $contentType, array $data): array
    {
        $allowedKeys = $contentType->fields->pluck('key')->map(static fn ($key): string => (string) $key)->all();
        $result = Arr::only($data, $allowedKeys);

        foreach ($allowedKeys as $allowedKey) {
            if (! array_key_exists($allowedKey, $result)) {
                $result[$allowedKey] = null;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payloadData
     */
    private function syncMediaUsages(ContentEntry $entry, ContentType $contentType, array $payloadData): void
    {
        if (! Schema::hasTable('media_usages') || ! Schema::hasTable('media_assets')) {
            return;
        }

        $this->removeMediaUsages($entry);

        $mediaFieldKeys = $contentType->fields
            ->filter(static fn ($field): bool => (string) $field->field_type === 'media')
            ->pluck('key')
            ->map(static fn ($key): string => (string) $key)
            ->values();

        if ($mediaFieldKeys->isEmpty()) {
            return;
        }

        $recordsByField = [];
        foreach ($mediaFieldKeys as $fieldKey) {
            $raw = $payloadData[$fieldKey] ?? null;
            $values = is_array($raw) ? $raw : [$raw];

            $uuids = collect($values)
                ->map(static fn ($value): string => trim((string) $value))
                ->filter(static fn (string $value): bool => $value !== '')
                ->unique()
                ->values();

            if ($uuids->isNotEmpty()) {
                $recordsByField[$fieldKey] = $uuids->all();
            }
        }

        if ($recordsByField === []) {
            return;
        }

        $allUuids = collect($recordsByField)->flatten()->unique()->values()->all();
        $assetIdByUuid = MediaAsset::query()
            ->whereIn('uuid', $allUuids)
            ->pluck('id', 'uuid');

        $rows = [];
        foreach ($recordsByField as $fieldKey => $uuids) {
            foreach ($uuids as $uuid) {
                $assetId = $assetIdByUuid[$uuid] ?? null;

                if ($assetId === null) {
                    continue;
                }

                $rows[] = [
                    'site_id' => $entry->site_id,
                    'asset_id' => (int) $assetId,
                    'context_type' => 'content_entry',
                    'context_id' => (string) $entry->id,
                    'field_key' => $fieldKey,
                    'reference_path' => 'data.' . $fieldKey,
                    'meta' => json_encode([
                        'content_type_id' => $contentType->id,
                        'content_type_slug' => $contentType->slug,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($rows !== []) {
            DB::table('media_usages')->insert($rows);
        }
    }

    private function removeMediaUsages(ContentEntry $entry): void
    {
        if (! Schema::hasTable('media_usages')) {
            return;
        }

        DB::table('media_usages')
            ->where('context_type', 'content_entry')
            ->where('context_id', (string) $entry->id)
            ->delete();
    }
}
