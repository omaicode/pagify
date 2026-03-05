<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentType;
use Pagify\Core\Events\EntryPublished;
use Pagify\Core\Models\Admin;
use Pagify\Core\Services\EventBus;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentHookEventIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_transition_dispatches_event_and_hooks(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.entry.create',
            'content.entry.publish',
        ]);

        $contentType = ContentType::factory()->create([
            'slug' => 'article',
            'name' => 'Article',
        ]);

        $contentType->fields()->create([
            'key' => 'title',
            'label' => 'Title',
            'field_type' => 'text',
            'sort_order' => 0,
            'is_required' => true,
            'is_localized' => false,
        ]);

        $entry = ContentEntry::factory()->create([
            'content_type_id' => $contentType->id,
            'slug' => 'event-entry',
            'status' => 'draft',
            'data_json' => ['title' => 'Event entry'],
        ]);

        $publishedEventEntryId = null;
        $statusHookPayload = null;
        $publishedHookPayload = null;

        $eventBus = app(EventBus::class);

        $eventBus->subscribe(EntryPublished::class, static function (EntryPublished $event) use (&$publishedEventEntryId): void {
            $publishedEventEntryId = (int) $event->entryId;
        });

        $eventBus->onHook('content.entry.status.changed', static function (array $payload) use (&$statusHookPayload): void {
            $statusHookPayload = $payload;
        });

        $eventBus->onHook('content.entry.published', static function (array $payload) use (&$publishedHookPayload): void {
            $publishedHookPayload = $payload;
        });

        $this->actingAs($admin, 'web')
            ->post('/admin/content/article/entries/' . $entry->id . '/publish')
            ->assertRedirect();

        $this->assertSame($entry->id, $publishedEventEntryId);
        $this->assertNotNull($statusHookPayload);
        $this->assertNotNull($publishedHookPayload);
        $this->assertSame('draft', $statusHookPayload['from_status'] ?? null);
        $this->assertSame('published', $statusHookPayload['to_status'] ?? null);
        $this->assertSame('published', $publishedHookPayload['transition'] ?? null);
    }

    /**
     * @param array<int, string> $permissions
     */
    private function makeAdminWithPermissions(array $permissions): Admin
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $admin->givePermissionTo($permission);
        }

        return $admin;
    }
}
