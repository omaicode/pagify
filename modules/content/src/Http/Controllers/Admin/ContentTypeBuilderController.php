<?php

namespace Modules\Content\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Content\Http\Requests\Admin\SaveSchemaBuilderRequest;
use Modules\Content\Models\ContentSchemaMigrationPlan;
use Modules\Content\Models\ContentType;
use Modules\Content\Services\ContentTypeService;
use Modules\Content\Services\SchemaMigrationPlanner;

class ContentTypeBuilderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContentTypeService $contentTypeService,
        private readonly SchemaMigrationPlanner $schemaMigrationPlanner,
    ) {
    }

    public function edit(ContentType $contentType): Response
    {
        $this->authorize('update', $contentType);

        $contentType->load('fields');

        return Inertia::render('Content/Types/Builder/Edit', [
            'contentType' => [
                'id' => $contentType->id,
                'name' => $contentType->name,
                'slug' => $contentType->slug,
            ],
            'fieldTypes' => config('content.field_types', []),
            'relationTypes' => config('content.relation_types', []),
            'initialFields' => $contentType->fields
                ->sortBy('sort_order')
                ->map(static fn ($field): array => [
                    'key' => (string) $field->key,
                    'label' => (string) $field->label,
                    'field_type' => (string) $field->field_type,
                    'config' => (array) ($field->config_json ?? []),
                    'validation' => (array) ($field->validation_json ?? []),
                    'conditional' => (array) ($field->conditional_json ?? []),
                    'sort_order' => (int) $field->sort_order,
                    'is_required' => (bool) $field->is_required,
                    'is_localized' => (bool) $field->is_localized,
                ])
                ->values()
                ->all(),
            'routes' => [
                'update' => route('content.admin.types.builder.update', $contentType),
                'status' => route('content.admin.types.builder.status', $contentType),
                'typeEdit' => route('content.admin.types.edit', $contentType),
            ],
        ]);
    }

    public function update(SaveSchemaBuilderRequest $request, ContentType $contentType): RedirectResponse
    {
        $this->authorize('update', $contentType);

        /** @var \Modules\Core\Models\Admin|null $admin */
        $admin = $request->user('web');

        $normalizedFields = $this->contentTypeService->normalizeBuilderFields((array) $request->validated('fields', []));
        $plan = $this->schemaMigrationPlanner->queue($contentType, $normalizedFields, $admin?->id);

        return redirect()
            ->route('content.admin.types.builder.status', $contentType)
            ->with('status', 'Schema builder saved. Migration plan queued #' . $plan->id . '.');
    }

    public function status(ContentType $contentType): Response
    {
        $this->authorize('update', $contentType);

        $plans = ContentSchemaMigrationPlan::query()
            ->where('content_type_id', $contentType->id)
            ->latest('id')
            ->paginate(20);

        return Inertia::render('Content/Types/Builder/Status', [
            'contentType' => [
                'id' => $contentType->id,
                'name' => $contentType->name,
                'slug' => $contentType->slug,
            ],
            'plans' => $plans->through(static fn (ContentSchemaMigrationPlan $plan): array => [
                'id' => $plan->id,
                'status' => $plan->status,
                'planned_at' => $plan->planned_at?->toDateTimeString(),
                'summary' => [
                    'additions' => (int) ($plan->plan_json['summary']['additions'] ?? 0),
                    'removals' => (int) ($plan->plan_json['summary']['removals'] ?? 0),
                    'updates' => (int) ($plan->plan_json['summary']['updates'] ?? 0),
                ],
                'error_message' => $plan->error_message,
            ]),
            'routes' => [
                'builderEdit' => route('content.admin.types.builder.edit', $contentType),
                'typeEdit' => route('content.admin.types.edit', $contentType),
            ],
        ]);
    }
}
