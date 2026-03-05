<?php

namespace Pagify\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Pagify\Core\Models\Admin;
use Pagify\Media\Services\MediaAssetManager;
use Tests\TestCase;

class MediaAssetManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_resolves_url_and_deletes_asset(): void
    {
        Storage::fake('public');

        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        /** @var MediaAssetManager $manager */
        $manager = app(MediaAssetManager::class);

        $asset = $manager->upload(
            file: UploadedFile::fake()->image('avatar.png', 120, 120),
            siteId: $admin->site_id,
            uploadedByAdminId: $admin->id,
        );

        $this->assertNotNull($asset->id);
        $this->assertSame('image', $asset->kind);
        $this->assertSame($admin->id, $asset->uploaded_by_admin_id);
        Storage::disk($asset->disk)->assertExists($asset->path);

        $resolved = $manager->findByPath($asset->path);
        $this->assertNotNull($resolved);

        $url = $manager->resolveUrlByPath($asset->path);
        $this->assertIsString($url);
        $this->assertNotSame('', $url);

        $manager->delete($asset->fresh());

        $this->assertNull($manager->findByPath($asset->path));
    }

    public function test_it_resizes_uploaded_image_when_target_width_is_provided(): void
    {
        Storage::fake('public');

        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        /** @var MediaAssetManager $manager */
        $manager = app(MediaAssetManager::class);

        $asset = $manager->upload(
            file: UploadedFile::fake()->image('wide-avatar.png', 1024, 512),
            siteId: $admin->site_id,
            uploadedByAdminId: $admin->id,
            maxImageWidth: 256,
        );

        $this->assertSame(256, $asset->width);
        $this->assertSame(128, $asset->height);

        $binary = Storage::disk($asset->disk)->get($asset->path);
        $image = imagecreatefromstring($binary);

        $this->assertNotFalse($image);
        $this->assertSame(256, imagesx($image));
        $this->assertSame(128, imagesy($image));
        imagedestroy($image);
    }
}
