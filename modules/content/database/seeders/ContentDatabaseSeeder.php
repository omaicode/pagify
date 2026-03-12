<?php

namespace Pagify\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentType;
use Pagify\Core\Models\Site;
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

        $this->seedVisualBuilderQaDemoType($site?->id, $contentType->slug);
        $this->seedDocsContent($site?->id);
    }

    private function seedDocsContent(?int $siteId): void
    {
        $docsFields = [
            [
                'key' => 'title',
                'label' => 'Title',
                'field_type' => 'text',
                'config' => [],
                'validation' => ['rules' => ['required', 'string', 'max:180']],
                'conditional' => [],
                'sort_order' => 0,
                'is_required' => true,
                'is_localized' => true,
            ],
            [
                'key' => 'summary',
                'label' => 'Summary',
                'field_type' => 'richtext',
                'config' => [],
                'validation' => [],
                'conditional' => [],
                'sort_order' => 1,
                'is_required' => false,
                'is_localized' => true,
            ],
            [
                'key' => 'body',
                'label' => 'Body',
                'field_type' => 'richtext',
                'config' => [],
                'validation' => [],
                'conditional' => [],
                'sort_order' => 2,
                'is_required' => false,
                'is_localized' => true,
            ],
            [
                'key' => 'category',
                'label' => 'Category',
                'field_type' => 'select',
                'config' => [
                    'options' => ['quickstart', 'architecture', 'operations'],
                ],
                'validation' => ['rules' => ['required']],
                'conditional' => [],
                'sort_order' => 3,
                'is_required' => true,
                'is_localized' => false,
            ],
        ];

        $docsType = ContentType::withoutGlobalScopes()->updateOrCreate(
            [
                'site_id' => $siteId,
                'slug' => 'docs-page',
            ],
            [
                'name' => 'Docs Page',
                'description' => 'Seeded documentation content type for the Unified default theme.',
                'is_active' => true,
                'schema_json' => [
                    'version' => 1,
                    'fields' => $docsFields,
                ],
            ]
        );

        foreach ($docsFields as $field) {
            $docsType->fields()->updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'field_type' => $field['field_type'],
                    'config_json' => $field['config'],
                    'validation_json' => $field['validation'],
                    'conditional_json' => $field['conditional'],
                    'sort_order' => $field['sort_order'],
                    'is_required' => $field['is_required'],
                    'is_localized' => $field['is_localized'],
                ]
            );
        }

        $docsEntries = [
            [
                'slug' => 'quickstart',
                'category' => 'quickstart',
                'title' => 'Quickstart Pagify CMS',
                'summary' => 'Install dependencies, migrate, seed, and publish your first page.',
                'body' => 'Run composer setup, verify site context, then publish pages from Page Builder.',
            ],
            [
                'slug' => 'architecture-overview',
                'category' => 'architecture',
                'title' => 'Architecture Overview',
                'summary' => 'Understand module boundaries and domain responsibilities.',
                'body' => 'Core manages permissions and site context. Content handles schema-driven data. Page Builder handles visual pages.',
            ],
            [
                'slug' => 'operations-checklist',
                'category' => 'operations',
                'title' => 'Operations Checklist',
                'summary' => 'Baseline runbook for queue workers, caching, and safe deploy checks.',
                'body' => 'Monitor queue workers, clear caches after theme updates, and run focused module tests before release.',
            ],
        ];

        foreach ($docsEntries as $entry) {
            ContentEntry::withoutGlobalScopes()->updateOrCreate(
                [
                    'site_id' => $siteId,
                    'content_type_id' => $docsType->id,
                    'slug' => $entry['slug'],
                ],
                [
                    'status' => 'published',
                    'published_at' => Carbon::now()->subHours(2),
                    'unpublished_at' => null,
                    'scheduled_publish_at' => null,
                    'scheduled_unpublish_at' => null,
                    'data_json' => [
                        'title' => $entry['title'],
                        'summary' => $entry['summary'],
                        'body' => $entry['body'],
                        'category' => $entry['category'],
                    ],
                    'schedule_metadata_json' => [],
                ]
            );
        }
    }

    private function seedVisualBuilderQaDemoType(?int $siteId, string $articleTypeSlug): void
    {
        $qaFields = [
            [
                'key' => 'title',
                'label' => 'Title',
                'field_type' => 'text',
                'config' => [],
                'validation' => [
                    'rules' => ['required', 'string', 'max:180'],
                ],
                'conditional' => [],
                'sort_order' => 0,
                'is_required' => true,
                'is_localized' => true,
            ],
            [
                'key' => 'body',
                'label' => 'Body',
                'field_type' => 'richtext',
                'config' => [],
                'validation' => [],
                'conditional' => [],
                'sort_order' => 1,
                'is_required' => false,
                'is_localized' => true,
            ],
            [
                'key' => 'reading_time',
                'label' => 'Reading time (minutes)',
                'field_type' => 'number',
                'config' => [],
                'validation' => [
                    'min' => 1,
                    'max' => 300,
                    'step' => 1,
                    'rules' => ['integer', 'min:1'],
                ],
                'conditional' => [],
                'sort_order' => 2,
                'is_required' => false,
                'is_localized' => false,
            ],
            [
                'key' => 'published_at',
                'label' => 'Published at',
                'field_type' => 'date',
                'config' => [],
                'validation' => [
                    'rules' => ['nullable', 'date'],
                ],
                'conditional' => [],
                'sort_order' => 3,
                'is_required' => false,
                'is_localized' => false,
            ],
            [
                'key' => 'is_featured',
                'label' => 'Featured',
                'field_type' => 'boolean',
                'config' => [],
                'validation' => [],
                'conditional' => [],
                'sort_order' => 4,
                'is_required' => false,
                'is_localized' => false,
            ],
            [
                'key' => 'category',
                'label' => 'Category',
                'field_type' => 'select',
                'config' => [
                    'options' => ['news', 'blog', 'guide', 'announcement'],
                ],
                'validation' => [
                    'rules' => ['required'],
                ],
                'conditional' => [],
                'sort_order' => 5,
                'is_required' => true,
                'is_localized' => false,
            ],
            [
                'key' => 'cover_media',
                'label' => 'Cover media',
                'field_type' => 'media',
                'config' => [
                    'multiple' => false,
                    'accept' => ['image/jpeg', 'image/png', 'image/webp'],
                ],
                'validation' => [],
                'conditional' => [],
                'sort_order' => 6,
                'is_required' => false,
                'is_localized' => false,
            ],
            [
                'key' => 'related_article',
                'label' => 'Related article',
                'field_type' => 'relation',
                'config' => [
                    'relation_type' => 'hasOne',
                    'target_content_type_slug' => $articleTypeSlug,
                ],
                'validation' => [],
                'conditional' => [],
                'sort_order' => 7,
                'is_required' => false,
                'is_localized' => false,
            ],
            [
                'key' => 'faq_items',
                'label' => 'FAQ items',
                'field_type' => 'repeater',
                'config' => [
                    'min_items' => 0,
                    'max_items' => 10,
                    'fields' => [
                        [
                            'key' => 'question',
                            'label' => 'Question',
                            'field_type' => 'text',
                        ],
                        [
                            'key' => 'answer',
                            'label' => 'Answer',
                            'field_type' => 'richtext',
                        ],
                    ],
                ],
                'validation' => [],
                'conditional' => [],
                'sort_order' => 8,
                'is_required' => false,
                'is_localized' => false,
            ],
            [
                'key' => 'featured_badge',
                'label' => 'Featured badge',
                'field_type' => 'conditional',
                'config' => [],
                'validation' => [],
                'conditional' => [
                    'depends_on' => 'is_featured',
                    'operator' => 'eq',
                    'value' => true,
                ],
                'sort_order' => 9,
                'is_required' => false,
                'is_localized' => false,
            ],
        ];

        $demoType = ContentType::withoutGlobalScopes()->updateOrCreate(
            [
                'site_id' => $siteId,
                'slug' => 'qa-visual-builder',
            ],
            [
                'name' => 'QA Visual Builder Demo',
                'description' => 'Demo content type for QA drag-drop and no-code schema builder testing.',
                'is_active' => true,
                'schema_json' => [
                    'version' => 1,
                    'fields' => $qaFields,
                ],
            ]
        );

        foreach ($qaFields as $field) {
            $demoType->fields()->updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'field_type' => $field['field_type'],
                    'config_json' => $field['config'],
                    'validation_json' => $field['validation'],
                    'conditional_json' => $field['conditional'],
                    'sort_order' => $field['sort_order'],
                    'is_required' => $field['is_required'],
                    'is_localized' => $field['is_localized'],
                ]
            );
        }
    }
}
