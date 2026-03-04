<?php

namespace Modules\Media\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Core\Http\Controllers\Api\ApiController;
use Modules\Media\Models\MediaAsset;
use Modules\Media\Models\MediaFolder;

class AdminMediaFolderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaAsset::class);

        $folders = MediaFolder::query()
            ->withCount('assets')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success($folders->map(static fn (MediaFolder $folder): array => [
            'id' => $folder->id,
            'parent_id' => $folder->parent_id,
            'name' => $folder->name,
            'slug' => $folder->slug,
            'sort_order' => $folder->sort_order,
            'assets_count' => $folder->assets_count,
        ])->values()->all());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MediaAsset::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'parent_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        if ($parentId !== null && ! MediaFolder::query()->whereKey($parentId)->exists()) {
            return $this->error(__('media::messages.api.folder_not_found'), 422, 'FOLDER_NOT_FOUND');
        }

        $baseSlug = Str::slug((string) $validated['name']);
        $slugSeed = $baseSlug !== '' ? $baseSlug : Str::lower(Str::random(8));
        $slug = $slugSeed;
        $counter = 1;

        while (MediaFolder::query()
            ->where('parent_id', $parentId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = sprintf('%s-%d', $slugSeed, $counter);
            $counter += 1;
        }

        $folder = MediaFolder::query()->create([
            'parent_id' => $parentId,
            'name' => (string) $validated['name'],
            'slug' => $slug,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return $this->success([
            'id' => $folder->id,
            'parent_id' => $folder->parent_id,
            'name' => $folder->name,
            'slug' => $folder->slug,
            'sort_order' => $folder->sort_order,
        ], 201);
    }
}
