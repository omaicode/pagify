<?php

namespace Modules\Content\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Modules\Content\Jobs\QueueSchemaMigrationPlanJob;
use Modules\Content\Models\ContentSchemaMigrationPlan;
use Modules\Content\Models\ContentType;
use Throwable;

class SchemaMigrationPlanner
{
    /**
     * @param array<int, array<string, mixed>> $normalizedFields
     */
    public function queue(ContentType $contentType, array $normalizedFields, ?int $adminId = null): ContentSchemaMigrationPlan
    {
        return DB::transaction(function () use ($contentType, $normalizedFields, $adminId): ContentSchemaMigrationPlan {
            $beforeFields = (array) Arr::get($contentType->schema_json ?? [], 'fields', []);

            $contentType->fill([
                'schema_json' => [
                    'version' => 1,
                    'fields' => $normalizedFields,
                ],
            ])->save();

            $contentType->fields()->delete();

            foreach ($normalizedFields as $field) {
                $contentType->fields()->create([
                    'key' => $field['key'],
                    'label' => $field['label'],
                    'field_type' => $field['field_type'],
                    'config_json' => $field['config'],
                    'validation_json' => $field['validation'],
                    'conditional_json' => $field['conditional'],
                    'sort_order' => $field['sort_order'],
                    'is_required' => $field['is_required'],
                    'is_localized' => $field['is_localized'],
                ]);
            }

            $plan = ContentSchemaMigrationPlan::query()->create([
                'site_id' => $contentType->site_id,
                'content_type_id' => $contentType->id,
                'requested_by_admin_id' => $adminId,
                'status' => 'queued',
                'schema_before_json' => $beforeFields,
                'schema_after_json' => $normalizedFields,
            ]);

            Bus::dispatch(new QueueSchemaMigrationPlanJob($plan->id));

            return $plan;
        });
    }

    public function buildPlan(ContentSchemaMigrationPlan $plan): ContentSchemaMigrationPlan
    {
        try {
            $before = collect((array) $plan->schema_before_json)->keyBy(static fn (array $field): string => (string) ($field['key'] ?? ''));
            $after = collect((array) $plan->schema_after_json)->keyBy(static fn (array $field): string => (string) ($field['key'] ?? ''));

            $additions = [];
            $removals = [];
            $updates = [];

            foreach ($after as $key => $afterField) {
                if ($key === '') {
                    continue;
                }

                if (! $before->has($key)) {
                    $additions[] = $afterField;
                    continue;
                }

                $beforeField = (array) $before->get($key);

                if ($this->fieldHash($beforeField) !== $this->fieldHash((array) $afterField)) {
                    $updates[] = [
                        'key' => $key,
                        'before' => $beforeField,
                        'after' => $afterField,
                    ];
                }
            }

            foreach ($before as $key => $beforeField) {
                if ($key === '' || $after->has($key)) {
                    continue;
                }

                $removals[] = $beforeField;
            }

            $plan->fill([
                'status' => 'planned',
                'plan_json' => [
                    'summary' => [
                        'additions' => count($additions),
                        'removals' => count($removals),
                        'updates' => count($updates),
                    ],
                    'operations' => [
                        'add' => $additions,
                        'remove' => $removals,
                        'update' => $updates,
                    ],
                    'note' => 'Plan generated for review only. No database DDL was executed in web request context.',
                ],
                'error_message' => null,
                'planned_at' => Carbon::now(),
            ])->save();
        } catch (Throwable $throwable) {
            $plan->fill([
                'status' => 'failed',
                'error_message' => $throwable->getMessage(),
            ])->save();
        }

        return $plan->refresh();
    }

    /**
     * @param array<string, mixed> $field
     */
    private function fieldHash(array $field): string
    {
        $normalized = [
            'field_type' => (string) ($field['field_type'] ?? ''),
            'config' => (array) ($field['config'] ?? []),
            'validation' => (array) ($field['validation'] ?? []),
            'conditional' => (array) ($field['conditional'] ?? []),
            'is_required' => (bool) ($field['is_required'] ?? false),
            'is_localized' => (bool) ($field['is_localized'] ?? false),
        ];

        return md5(json_encode($normalized, JSON_UNESCAPED_UNICODE));
    }
}
