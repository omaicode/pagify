<?php

namespace Pagify\Media\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaAssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'folder_id' => $this->folder_id,
            'disk' => $this->disk,
            'path' => $this->path,
            'filename' => $this->filename,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size_bytes' => $this->size_bytes,
            'kind' => $this->kind,
            'width' => $this->width,
            'height' => $this->height,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'focal_point_x' => $this->focal_point_x,
            'focal_point_y' => $this->focal_point_y,
            'uploaded_at' => optional($this->uploaded_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
            'tags' => $this->whenLoaded('tags', fn (): array => $this->tags->map(fn ($tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()->all()),
            'folder' => $this->whenLoaded('folder', fn (): ?array => $this->folder?->only(['id', 'name', 'slug', 'parent_id'])),
            'usage_count' => $this->when(isset($this->usage_count), $this->usage_count),
            'transforms' => $this->whenLoaded('transforms', fn (): array => $this->transforms->map(fn ($transform): array => [
                'id' => $transform->id,
                'profile' => $transform->profile,
                'variant' => $transform->variant,
                'status' => $transform->status,
                'disk' => $transform->disk,
                'path' => $transform->path,
                'mime_type' => $transform->mime_type,
                'width' => $transform->width,
                'height' => $transform->height,
                'size_bytes' => $transform->size_bytes,
                'error_message' => $transform->error_message,
                'generated_at' => optional($transform->generated_at)?->toDateTimeString(),
            ])->values()->all()),
        ];
    }
}
