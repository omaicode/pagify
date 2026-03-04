<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentType;
use Modules\Core\Models\Site;
use Spatie\Permission\Models\Permission;

class ContentDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedSampleContent();
    }

    private function seedPermissions(): void
    {
        $permissions = config('content.permissions', []);

        if (! is_array($permissions)) {
            return;
        }

        foreach ($permissions as $permissionName) {
            if (! is_string($permissionName) || $permissionName === '') {
                continue;
            }

            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }

    private function seedSampleContent(): void
    {
        $site = Site::query()->where('is_active', true)->orderBy('id')->first();

        $contentType = ContentType::withoutGlobalScopes()->updateOrCreate(
            [
                'site_id' => $site?->id,
                'slug' => 'article',
            ],
            [
                'name' => 'Article',
                'description' => 'Default article content type seeded by content module.',
                'is_active' => true,
                'schema_json' => [
                    'version' => 1,
                    'fields' => [
                        [
                            'key' => 'title',
                            'label' => 'Title',
                            'field_type' => 'text',
                            'config' => [],
                            'validation' => [],
                            'conditional' => [],
                            'sort_order' => 0,
                            'is_required' => true,
                            'is_localized' => false,
                        ],
                        [
                            'key' => 'category',
                            'label' => 'Category',
                            'field_type' => 'select',
                            'config' => [
                                'options' => ['news', 'blog', 'guide'],
                            ],
                            'validation' => [],
                            'conditional' => [],
                            'sort_order' => 1,
                            'is_required' => true,
                            'is_localized' => false,
                        ],
                        [
                            'key' => 'summary',
                            'label' => 'Summary',
                            'field_type' => 'richtext',
                            'config' => [],
                            'validation' => [],
                            'conditional' => [],
                            'sort_order' => 2,
                            'is_required' => false,
                            'is_localized' => false,
                        ],
                    ],
                ],
            ]
        );

        $fields = [
            [
                'key' => 'title',
                'label' => 'Title',
                'field_type' => 'text',
                'config_json' => [],
                'validation_json' => [],
                'conditional_json' => [],
                'sort_order' => 0,
                'is_required' => true,
                'is_localized' => false,
            ],
            [
                'key' => 'category',
                'label' => 'Category',
                'field_type' => 'select',
                'config_json' => ['options' => ['news', 'blog', 'guide']],
                'validation_json' => [],
                'conditional_json' => [],
                'sort_order' => 1,
                'is_required' => true,
                'is_localized' => false,
            ],
            [
                'key' => 'summary',
                'label' => 'Summary',
                'field_type' => 'richtext',
                'config_json' => [],
                'validation_json' => [],
                'conditional_json' => [],
                'sort_order' => 2,
                'is_required' => false,
                'is_localized' => false,
            ],
        ];

        foreach ($fields as $field) {
            $contentType->fields()->updateOrCreate(
                ['key' => $field['key']],
                $field
            );
        }

        $samples = [
            [
                'slug' => 'hello-pagify',
                'status' => 'published',
                'published_at' => Carbon::now()->subDay(),
                'data_json' => [
                    'title' => 'Hello Pagify',
                    'category' => 'news',
                    'summary' => 'Initial seeded article for quick testing.',
                ],
            ],
            [
                'slug' => 'build-your-first-module',
                'status' => 'draft',
                'published_at' => null,
                'data_json' => [
                    'title' => 'Build your first module',
                    'category' => 'guide',
                    'summary' => 'A draft guide seeded for editorial workflow testing.',
                ],
            ],
        ];

        foreach ($samples as $sample) {
            ContentEntry::withoutGlobalScopes()->updateOrCreate(
                [
                    'site_id' => $site?->id,
                    'content_type_id' => $contentType->id,
                    'slug' => $sample['slug'],
                ],
                [
                    'status' => $sample['status'],
                    'published_at' => $sample['published_at'],
                    'unpublished_at' => null,
                    'scheduled_publish_at' => null,
                    'scheduled_unpublish_at' => null,
                    'data_json' => $sample['data_json'],
                    'schedule_metadata_json' => [],
                ]
            );
        }
    }
}
