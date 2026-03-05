<?php

namespace Pagify\Media\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Media\Http\Requests\Admin\ListMediaAssetsRequest;
use Pagify\Media\Http\Requests\Admin\StoreMediaAssetRequest;
use Pagify\Media\Http\Requests\Admin\UpdateMediaAssetRequest;
use Pagify\Media\Http\Resources\MediaAssetResource;
use Pagify\Media\Models\MediaAsset;
use Pagify\Media\Models\MediaFolder;
use Pagify\Media\Services\MediaAssetManager;

class AdminMediaAssetController extends ApiController
{
    public function index(ListMediaAssetsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', MediaAsset::class);

        $validated = $request->validated();
        $sortBy = (string) ($validated['sort_by'] ?? 'created_at');
        $sortDir = (string) ($validated['sort_dir'] ?? 'desc');
        $perPage = (int) ($validated['per_page'] ?? 24);

        $query = MediaAsset::query()
            ->with(['folder', 'tags', 'transforms'])
            ->withCount('usages');

        if (! empty($validated['q'])) {
            $keyword = '%' . (string) $validated['q'] . '%';
            $query->where(static function ($builder) use ($keyword): void {
                $builder
                    ->where('filename', 'like', $keyword)
                    ->orWhere('original_name', 'like', $keyword)
                    ->orWhere('mime_type', 'like', $keyword);
            });
        }

        if (! empty($validated['kind'])) {
            $query->where('kind', (string) $validated['kind']);
        }

        if (! empty($validated['folder_id'])) {
            $query->where('folder_id', (int) $validated['folder_id']);
        }

        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', Carbon::parse((string) $validated['from'])->toDateString());
        }

        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', Carbon::parse((string) $validated['to'])->toDateString());
        }

        $assets = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->appends($request->query());

        return $this->success(MediaAssetResource::collection($assets)->resolve(), meta: [
            'pagination' => [
                'current_page' => $assets->currentPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
                'last_page' => $assets->lastPage(),
            ],
        ]);
    }

    public function store(StoreMediaAssetRequest $request, MediaAssetManager $assetManager): JsonResponse
    {
        $this->authorize('create', MediaAsset::class);

        $validated = $request->validated();
        $folder = null;

        if (! empty($validated['folder_id'])) {
            $folder = MediaFolder::query()->find((int) $validated['folder_id']);

            if ($folder === null) {
                return $this->error(__('media::messages.api.folder_not_found'), 422, 'FOLDER_NOT_FOUND');
            }
        }

        $uploaded = $request->file('file');

        /** @var \Pagify\Core\Models\Admin|null $admin */
        $admin = $request->user('web');

        $asset = $assetManager->upload(
            file: $uploaded,
            folder: $folder,
            siteId: $admin?->site_id,
            uploadedByAdminId: $admin?->id,
            altText: $validated['alt_text'] ?? null,
            caption: $validated['caption'] ?? null,
        );

        $asset->load(['folder', 'tags', 'transforms'])->loadCount('usages');

        return $this->success((new MediaAssetResource($asset))->resolve(), 201);
    }

    public function preview(MediaAsset $asset): StreamedResponse
    {
        $this->authorize('view', $asset);

        if (! Storage::disk($asset->disk)->exists($asset->path)) {
            abort(404);
        }

        $stream = Storage::disk($asset->disk)->readStream($asset->path);
        if ($stream === false) {
            abort(404);
        }

        return response()->stream(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => (string) ($asset->mime_type ?: 'application/octet-stream'),
            'Content-Disposition' => 'inline; filename="' . addslashes((string) $asset->original_name) . '"',
        ]);
    }

    public function download(MediaAsset $asset): StreamedResponse
    {
        $this->authorize('view', $asset);

        if (! Storage::disk($asset->disk)->exists($asset->path)) {
            abort(404);
        }

        $stream = Storage::disk($asset->disk)->readStream($asset->path);
        if ($stream === false) {
            abort(404);
        }

        return response()->streamDownload(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $asset->original_name, [
            'Content-Type' => (string) ($asset->mime_type ?: 'application/octet-stream'),
        ]);
    }

    public function update(UpdateMediaAssetRequest $request, MediaAsset $asset): JsonResponse
    {
        $this->authorize('update', $asset);

        $validated = $request->validated();

        if (! empty($validated['folder_id'])) {
            $folder = MediaFolder::query()->find((int) $validated['folder_id']);

            if ($folder === null) {
                return $this->error(__('media::messages.api.folder_not_found'), 422, 'FOLDER_NOT_FOUND');
            }
        }

        $asset->fill([
            'original_name' => $validated['name'] ?? $asset->original_name,
            'alt_text' => $validated['alt_text'] ?? $asset->alt_text,
            'caption' => $validated['caption'] ?? $asset->caption,
            'folder_id' => $validated['folder_id'] ?? $asset->folder_id,
            'focal_point_x' => $validated['focal_point_x'] ?? $asset->focal_point_x,
            'focal_point_y' => $validated['focal_point_y'] ?? $asset->focal_point_y,
        ]);

        $asset->save();

        if (isset($validated['tag_ids']) && is_array($validated['tag_ids'])) {
            $asset->tags()->sync(array_values(array_unique(array_map('intval', $validated['tag_ids']))));
        }

        $asset->load(['folder', 'tags', 'transforms'])->loadCount('usages');

        return $this->success((new MediaAssetResource($asset))->resolve());
    }

    public function destroy(\Illuminate\Http\Request $request, MediaAsset $asset, MediaAssetManager $assetManager): JsonResponse
    {
        $this->authorize('delete', $asset);

        $force = (bool) $request->boolean('force', false);
        $usageCount = $asset->usages()->count();

        if ($usageCount > 0 && ! $force) {
            return $this->error(
                __('media::messages.api.asset_in_use', ['count' => $usageCount]),
                409,
                'ASSET_IN_USE',
                ['usage_count' => [$usageCount]],
            );
        }

        if ($force) {
            $asset->usages()->delete();
        }

        $assetManager->delete($asset);

        return $this->success(['deleted' => true]);
    }
}
