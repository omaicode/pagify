<?php

namespace Pagify\Content\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Content\Http\Requests\Admin\StoreContentTypeRequest;
use Pagify\Content\Http\Requests\Admin\UpdateContentTypeRequest;
use Pagify\Content\Models\ContentType;
use Pagify\Content\Services\ContentTypeService;
use Pagify\Content\Services\SchemaMigrationPlanner;

class ContentTypeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContentTypeService $contentTypeService,
        private readonly SchemaMigrationPlanner $schemaMigrationPlanner,
    )
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ContentType::class);

        $contentTypes = ContentType::query()
            ->latest('id')
            ->paginate(20)
            ->through(static fn (ContentType $contentType): array => [
                'id' => $contentType->id,
                'name' => $contentType->name,
                'slug' => $contentType->slug,
                'is_active' => (bool) $contentType->is_active,
                'updated_at' => optional($contentType->updated_at)?->toDateTimeString(),
                'routes' => [
                    'edit' => route('content.admin.types.edit', $contentType),
                    'entries' => route('content.admin.entries.index', $contentType->slug),
                    'builder' => route('content.admin.types.builder.edit', $contentType),
                ],
            ]);

        return Inertia::render('Content/Types/Index', [
            'contentTypes' => $contentTypes,
            'routes' => [
                'create' => route('content.admin.types.create'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ContentType::class);

        return Inertia::render('Content/Types/Create', [
            'fieldTypes' => config('content.field_types', []),
            'relationTypes' => config('content.relation_types', []),
            'routes' => [
                'store' => route('content.admin.types.store'),
                'index' => route('content.admin.types.index'),
            ],
        ]);
    }

    public function store(StoreContentTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', ContentType::class);

        /** @var \Pagify\Core\Models\Admin|null $admin */
        $admin = $request->user('web');

        $validated = $request->validated();
        $contentType = $this->contentTypeService->createMetadataOnly($validated);
        $normalizedFields = $this->contentTypeService->normalizeBuilderFields((array) ($validated['fields'] ?? []));
        $plan = $this->schemaMigrationPlanner->queue($contentType, $normalizedFields, $admin?->id);

        return redirect()
            ->route('content.admin.types.builder.status', $contentType)
            ->with('status', __('content::messages.status.content_type_created', ['plan_id' => $plan->id]));
    }

    public function edit(ContentType $contentType): Response
    {
        $this->authorize('update', $contentType);

        $contentType->load('fields');

        return Inertia::render('Content/Types/Edit', [
            'contentType' => [
                'id' => $contentType->id,
                'name' => $contentType->name,
                'slug' => $contentType->slug,
                'description' => $contentType->description,
                'is_active' => (bool) $contentType->is_active,
                'fields' => $contentType->fields
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
            ],
            'fieldTypes' => config('content.field_types', []),
            'relationTypes' => config('content.relation_types', []),
            'routes' => [
                'update' => route('content.admin.types.update', $contentType),
                'destroy' => route('content.admin.types.destroy', $contentType),
                'entries' => route('content.admin.entries.index', $contentType->slug),
                'builder' => route('content.admin.types.builder.edit', $contentType),
                'builderStatus' => route('content.admin.types.builder.status', $contentType),
                'index' => route('content.admin.types.index'),
            ],
            'breadcrumbs' => [
                [
                    'href' => route('core.admin.dashboard'),
                    'label_key' => 'dashboard',
                ],
                [
                    'href' => route('content.admin.types.index'),
                    'label_key' => 'content_types',
                ],
                [
                    'label' => $contentType->name,
                ],
            ],
        ]);
    }

    public function update(UpdateContentTypeRequest $request, ContentType $contentType): RedirectResponse
    {
        $this->authorize('update', $contentType);

        /** @var \Pagify\Core\Models\Admin|null $admin */
        $admin = $request->user('web');

        $validated = $request->validated();

        $this->contentTypeService->updateMetadataOnly($contentType, $validated);
        $normalizedFields = $this->contentTypeService->normalizeBuilderFields((array) ($validated['fields'] ?? []));
        $plan = $this->schemaMigrationPlanner->queue($contentType, $normalizedFields, $admin?->id);

        return redirect()
            ->route('content.admin.types.builder.status', $contentType)
            ->with('status', __('content::messages.status.content_type_updated', ['plan_id' => $plan->id]));
    }

    public function destroy(ContentType $contentType): RedirectResponse
    {
        $this->authorize('delete', $contentType);

        $this->contentTypeService->delete($contentType);

        return redirect()
            ->route('content.admin.types.index')
            ->with('status', __('content::messages.status.content_type_deleted'));
    }
}
