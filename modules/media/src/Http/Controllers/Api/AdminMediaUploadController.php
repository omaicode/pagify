<?php

namespace Modules\Media\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\Http\Controllers\Api\ApiController;
use Modules\Media\Jobs\GenerateMediaImageTransformsJob;
use Modules\Media\Http\Requests\Admin\CreateUploadSessionRequest;
use Modules\Media\Http\Requests\Admin\UploadChunkRequest;
use Modules\Media\Http\Resources\MediaAssetResource;
use Modules\Media\Models\MediaAsset;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Models\MediaUploadSession;
use Modules\Media\Services\MediaStorageManager;

class AdminMediaUploadController extends ApiController
{
    public function store(CreateUploadSessionRequest $request, MediaStorageManager $storage): JsonResponse
    {
        $this->authorize('create', MediaAsset::class);

        $validated = $request->validated();

        if (! empty($validated['folder_id']) && ! MediaFolder::query()->whereKey((int) $validated['folder_id'])->exists()) {
            return $this->error(__('media::messages.api.folder_not_found'), 422, 'FOLDER_NOT_FOUND');
        }

        /** @var \Modules\Core\Models\Admin|null $admin */
        $admin = $request->user('web');
        $uuid = (string) Str::uuid();

        $session = MediaUploadSession::query()->create([
            'uuid' => $uuid,
            'disk' => $storage->resolveDisk(),
            'temp_prefix' => sprintf('%s/tmp/uploads/%s', $storage->resolveBasePath(), $uuid),
            'original_name' => (string) $validated['filename'],
            'mime_type' => $validated['mime_type'] ?? null,
            'extension' => strtolower((string) pathinfo((string) $validated['filename'], PATHINFO_EXTENSION)),
            'total_size_bytes' => (int) $validated['total_size_bytes'],
            'total_chunks' => (int) $validated['total_chunks'],
            'uploaded_chunks' => [],
            'status' => 'pending',
            'folder_id' => $validated['folder_id'] ?? null,
            'uploaded_by_admin_id' => $admin?->id,
            'expires_at' => now()->addHours(6),
        ]);

        return $this->success([
            'session_uuid' => $session->uuid,
            'total_chunks' => $session->total_chunks,
            'max_chunk_size_bytes' => (int) config('media.uploads.chunk_size_bytes', 1024 * 1024 * 10),
            'chunk_route' => route('media.api.v1.admin.upload-sessions.chunk', ['session' => $session->uuid]),
            'complete_route' => route('media.api.v1.admin.upload-sessions.complete', ['session' => $session->uuid]),
        ], 201);
    }

    public function uploadChunk(UploadChunkRequest $request, string $session): JsonResponse
    {
        $this->authorize('create', MediaAsset::class);

        $uploadSession = $this->resolveSession($session);
        if ($uploadSession === null) {
            return $this->error(__('media::messages.api.upload_session_not_found'), 404, 'UPLOAD_SESSION_NOT_FOUND');
        }

        $validated = $request->validated();
        $chunkIndex = (int) $validated['chunk_index'];

        if ($chunkIndex > $uploadSession->total_chunks) {
            return $this->error(__('media::messages.api.invalid_chunk_index'), 422, 'INVALID_CHUNK_INDEX');
        }

        $chunkPath = $uploadSession->temp_prefix . '/' . $chunkIndex . '.part';
        Storage::disk($uploadSession->disk)->putFileAs(dirname($chunkPath), $request->file('chunk'), basename($chunkPath));

        $uploadedChunks = collect((array) ($uploadSession->uploaded_chunks ?? []))
            ->map(static fn ($item): int => (int) $item)
            ->push($chunkIndex)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $uploadSession->forceFill([
            'uploaded_chunks' => $uploadedChunks,
            'status' => count($uploadedChunks) >= $uploadSession->total_chunks ? 'uploaded' : 'pending',
        ])->save();

        return $this->success([
            'session_uuid' => $uploadSession->uuid,
            'uploaded_chunks' => $uploadedChunks,
            'total_chunks' => $uploadSession->total_chunks,
            'is_complete' => count($uploadedChunks) >= $uploadSession->total_chunks,
        ]);
    }

    public function complete(string $session, MediaStorageManager $storage): JsonResponse
    {
        $this->authorize('create', MediaAsset::class);

        $uploadSession = $this->resolveSession($session);
        if ($uploadSession === null) {
            return $this->error(__('media::messages.api.upload_session_not_found'), 404, 'UPLOAD_SESSION_NOT_FOUND');
        }

        $uploadedChunks = collect((array) ($uploadSession->uploaded_chunks ?? []))
            ->map(static fn ($item): int => (int) $item)
            ->unique()
            ->sort()
            ->values();

        if ($uploadedChunks->count() < $uploadSession->total_chunks) {
            return $this->error(__('media::messages.api.upload_incomplete'), 422, 'UPLOAD_INCOMPLETE');
        }

        $tmpFilePath = tempnam(sys_get_temp_dir(), 'media_upload_');
        if ($tmpFilePath === false) {
            return $this->error(__('media::messages.api.upload_temp_create_failed'), 500, 'UPLOAD_TEMP_CREATE_FAILED');
        }

        $tmpHandle = fopen($tmpFilePath, 'wb');
        if ($tmpHandle === false) {
            return $this->error(__('media::messages.api.upload_temp_create_failed'), 500, 'UPLOAD_TEMP_CREATE_FAILED');
        }

        foreach ($uploadedChunks as $chunkIndex) {
            $chunkPath = $uploadSession->temp_prefix . '/' . $chunkIndex . '.part';
            $chunkStream = Storage::disk($uploadSession->disk)->readStream($chunkPath);

            if ($chunkStream === false) {
                fclose($tmpHandle);
                @unlink($tmpFilePath);

                return $this->error(__('media::messages.api.upload_chunk_missing'), 422, 'UPLOAD_CHUNK_MISSING');
            }

            stream_copy_to_stream($chunkStream, $tmpHandle);
            fclose($chunkStream);
        }

        fflush($tmpHandle);
        fclose($tmpHandle);

        $extension = $uploadSession->extension !== null && $uploadSession->extension !== ''
            ? '.' . strtolower($uploadSession->extension)
            : '';

        $folderSlug = null;
        if ($uploadSession->folder_id !== null) {
            $folderSlug = MediaFolder::query()->whereKey($uploadSession->folder_id)->value('slug');
        }

        $targetFileName = Str::uuid()->toString() . $extension;
        $folderPath = $folderSlug !== null && $folderSlug !== '' ? trim($folderSlug, '/') : date('Y/m');
        $targetPath = trim($storage->resolveBasePath() . '/' . $folderPath, '/') . '/' . $targetFileName;

        $source = fopen($tmpFilePath, 'rb');
        if ($source === false) {
            @unlink($tmpFilePath);

            return $this->error(__('media::messages.api.upload_temp_create_failed'), 500, 'UPLOAD_TEMP_CREATE_FAILED');
        }

        Storage::disk($uploadSession->disk)->writeStream($targetPath, $source);
        fclose($source);
        @unlink($tmpFilePath);

        /** @var \Modules\Core\Models\Admin|null $admin */
        $admin = request()->user('web');

        $asset = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'folder_id' => $uploadSession->folder_id,
            'disk' => $uploadSession->disk,
            'path' => $targetPath,
            'path_hash' => hash('sha256', $targetPath),
            'filename' => $targetFileName,
            'original_name' => $uploadSession->original_name,
            'mime_type' => $uploadSession->mime_type,
            'extension' => $uploadSession->extension,
            'size_bytes' => $uploadSession->total_size_bytes,
            'kind' => $this->resolveKind((string) $uploadSession->mime_type),
            'uploaded_by_admin_id' => $admin?->id,
            'uploaded_at' => now(),
        ]);

        Storage::disk($uploadSession->disk)->deleteDirectory($uploadSession->temp_prefix);

        $uploadSession->forceFill([
            'status' => 'completed',
            'uploaded_chunks' => $uploadedChunks->all(),
        ])->save();

        $asset->load(['folder', 'tags', 'transforms'])->loadCount('usages');

        if (str_starts_with((string) $asset->mime_type, 'image/')) {
            GenerateMediaImageTransformsJob::dispatch($asset->id)
                ->onQueue((string) config('media.image_transforms.queue', 'default'));
        }

        return $this->success((new MediaAssetResource($asset))->resolve(), 201);
    }

    private function resolveSession(string $sessionUuid): ?MediaUploadSession
    {
        return MediaUploadSession::query()
            ->where('uuid', $sessionUuid)
            ->where('expires_at', '>=', now())
            ->first();
    }

    private function resolveKind(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_contains($mimeType, 'pdf') || str_starts_with($mimeType, 'text/') || str_contains($mimeType, 'officedocument')) {
            return 'document';
        }

        return 'other';
    }
}
