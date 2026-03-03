<?php

namespace Modules\Content\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Content\Http\Requests\Admin\StoreContentTypeRequest;
use Modules\Content\Http\Requests\Admin\UpdateContentTypeRequest;
use Modules\Content\Models\ContentType;
use Modules\Content\Services\ContentTypeService;

class ContentTypeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ContentTypeService $contentTypeService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ContentType::class);

        $contentTypes = ContentType::query()
            ->latest('id')
            ->paginate(20);

        return view('content::types.index', [
            'contentTypes' => $contentTypes,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', ContentType::class);

        return view('content::types.create', [
            'fieldTypes' => config('content.field_types', []),
            'relationTypes' => config('content.relation_types', []),
        ]);
    }

    public function store(StoreContentTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', ContentType::class);

        $contentType = $this->contentTypeService->create($request->validated());

        return redirect()
            ->route('content.admin.types.edit', $contentType)
            ->with('status', 'Content type created successfully.');
    }

    public function edit(ContentType $contentType): View
    {
        $this->authorize('update', $contentType);

        return view('content::types.edit', [
            'contentType' => $contentType->load('fields'),
            'fieldTypes' => config('content.field_types', []),
            'relationTypes' => config('content.relation_types', []),
        ]);
    }

    public function update(UpdateContentTypeRequest $request, ContentType $contentType): RedirectResponse
    {
        $this->authorize('update', $contentType);

        $this->contentTypeService->update($contentType, $request->validated());

        return redirect()
            ->route('content.admin.types.edit', $contentType)
            ->with('status', 'Content type updated successfully.');
    }

    public function destroy(ContentType $contentType): RedirectResponse
    {
        $this->authorize('delete', $contentType);

        $this->contentTypeService->delete($contentType);

        return redirect()
            ->route('content.admin.types.index')
            ->with('status', 'Content type deleted successfully.');
    }
}
