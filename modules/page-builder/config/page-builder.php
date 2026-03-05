<?php

return [
	'breakpoints' => ['desktop', 'tablet', 'mobile'],

	'permissions' => [
		'page-builder.page.viewAny',
		'page-builder.page.view',
		'page-builder.page.create',
		'page-builder.page.update',
		'page-builder.page.delete',
		'page-builder.page.publish',
		'page-builder.page.revision.view',
		'page-builder.page.revision.rollback',
		'page-builder.library.manage',
	],

	'internal_blocks' => [
		[
			'key' => 'heading',
			'label' => 'Heading',
			'component' => 'PageBuilder/Blocks/HeadingBlock',
			'props_schema' => [
				'text' => 'Heading',
				'tag' => 'h2',
			],
		],
		[
			'key' => 'paragraph',
			'label' => 'Paragraph',
			'component' => 'PageBuilder/Blocks/ParagraphBlock',
			'props_schema' => [
				'text' => 'Describe your section here.',
			],
		],
		[
			'key' => 'button',
			'label' => 'Button',
			'component' => 'PageBuilder/Blocks/ButtonBlock',
			'props_schema' => [
				'label' => 'Get started',
				'href' => '#',
			],
		],
		[
			'key' => 'image',
			'label' => 'Image',
			'component' => 'PageBuilder/Blocks/ImageBlock',
			'props_schema' => [
				'src' => '',
				'alt' => '',
			],
		],
	],

	'default_page_templates' => [
		[
			'slug' => 'landing',
			'name' => 'Landing',
			'category' => 'landing',
			'description' => 'Simple conversion-focused landing layout.',
			'schema_json' => [
				'sections' => [
					[
						'id' => 'hero',
						'blocks' => [
							['type' => 'heading', 'props' => ['text' => 'Landing headline']],
							['type' => 'paragraph', 'props' => ['text' => 'Landing page subtitle']],
							['type' => 'button', 'props' => ['label' => 'Start now', 'href' => '#']],
						],
					],
				],
			],
		],
		[
			'slug' => 'blog',
			'name' => 'Blog',
			'category' => 'blog',
			'description' => 'Header, posts list and CTA.',
			'schema_json' => [
				'sections' => [
					[
						'id' => 'intro',
						'blocks' => [
							['type' => 'heading', 'props' => ['text' => 'Blog title']],
							['type' => 'paragraph', 'props' => ['text' => 'Latest insights and stories.']],
						],
					],
				],
			],
		],
		[
			'slug' => 'docs',
			'name' => 'Docs',
			'category' => 'docs',
			'description' => 'Documentation layout starter.',
			'schema_json' => [
				'sections' => [
					[
						'id' => 'docs-intro',
						'blocks' => [
							['type' => 'heading', 'props' => ['text' => 'Documentation']],
							['type' => 'paragraph', 'props' => ['text' => 'Write guides for your users.']],
						],
					],
				],
			],
		],
		[
			'slug' => 'portfolio',
			'name' => 'Portfolio',
			'category' => 'portfolio',
			'description' => 'Portfolio showcase starter.',
			'schema_json' => [
				'sections' => [
					[
						'id' => 'portfolio-hero',
						'blocks' => [
							['type' => 'heading', 'props' => ['text' => 'Featured work']],
							['type' => 'paragraph', 'props' => ['text' => 'Highlight your best projects.']],
						],
					],
				],
			],
		],
	],
];
