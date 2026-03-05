<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pagify\Content\Models\ContentField;
use Pagify\Content\Models\ContentType;
use Pagify\Content\Services\ContentEntryService;
use Pagify\Media\Models\MediaAsset;
use Tests\TestCase;

class ContentMediaUsageIndexingTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_usages_are_indexed_and_updated_for_content_entries(): void
    {
        $contentType = ContentType::query()->create([
            'name' => 'Article',
            'slug' => 'article',
            'description' => 'Article type',
            'is_active' => true,
        ]);

        ContentField::query()->create([
            'content_type_id' => $contentType->id,
            'key' => 'cover',
            'label' => 'Cover',
            'field_type' => 'media',
            'sort_order' => 0,
            'is_required' => false,
            'is_localized' => false,
        ]);

        $firstAsset = MediaAsset::query()->create([
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'disk' => 'public',
            'path' => 'media/test/first.jpg',
            'filename' => 'first.jpg',
            'original_name' => 'First.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 1024,
            'kind' => 'image',
            'uploaded_at' => now(),
        ]);

        $secondAsset = MediaAsset::query()->create([
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'disk' => 'public',
            'path' => 'media/test/second.jpg',
            'filename' => 'second.jpg',
            'original_name' => 'Second.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 1024,
            'kind' => 'image',
            'uploaded_at' => now(),
        ]);

        /** @var ContentEntryService $service */
        $service = app(ContentEntryService::class);

        $entry = $service->create($contentType, [
            'slug' => 'entry-1',
            'status' => 'draft',
            'data' => [
                'cover' => $firstAsset->uuid,
            ],
        ]);

        $this->assertDatabaseHas('media_usages', [
            'asset_id' => $firstAsset->id,
            'context_type' => 'content_entry',
            'context_id' => (string) $entry->id,
            'field_key' => 'cover',
        ]);

        $service->update($contentType, $entry, [
            'slug' => 'entry-1',
            'status' => 'draft',
            'data' => [
                'cover' => $secondAsset->uuid,
            ],
        ]);

        $this->assertDatabaseMissing('media_usages', [
            'asset_id' => $firstAsset->id,
            'context_type' => 'content_entry',
            'context_id' => (string) $entry->id,
            'field_key' => 'cover',
        ]);

        $this->assertDatabaseHas('media_usages', [
            'asset_id' => $secondAsset->id,
            'context_type' => 'content_entry',
            'context_id' => (string) $entry->id,
            'field_key' => 'cover',
        ]);

        $service->delete($entry);

        $this->assertDatabaseMissing('media_usages', [
            'context_type' => 'content_entry',
            'context_id' => (string) $entry->id,
        ]);
    }
}
