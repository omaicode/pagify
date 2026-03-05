<?php

return [
	[
		'key' => 'page-builder.pages',
		'label' => 'Pages',
		'label_key' => 'pages',
		'route' => 'page-builder.admin.pages.index',
		'group' => 'Content',
		'order' => 25,
		'permission' => 'page-builder.page.viewAny',
		'active_patterns' => [
			'/admin/page-builder',
		],
	],
];
