<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageTemplate;
use Pagify\PageBuilder\Models\SectionTemplate;
use Pagify\PageBuilder\Services\BlockRegistryService;

class AdminPageBuilderRegistryController extends ApiController
{
	public function registry(BlockRegistryService $blockRegistry): JsonResponse
	{
		$this->authorize('viewAny', Page::class);

		return $this->success([
			'blocks' => $blockRegistry->all(),
			'breakpoints' => array_values((array) config('page-builder.breakpoints', ['desktop', 'tablet', 'mobile'])),
		]);
	}

	public function templates(): JsonResponse
	{
		$this->authorize('viewAny', Page::class);

		$defaultTemplates = collect((array) config('page-builder.default_page_templates', []))
			->filter(static fn (mixed $item): bool => is_array($item))
			->values()
			->all();

		$siteTemplates = PageTemplate::query()
			->where('is_active', true)
			->latest('id')
			->get()
			->map(static fn (PageTemplate $template): array => [
				'id' => $template->id,
				'slug' => $template->slug,
				'name' => $template->name,
				'category' => $template->category,
				'description' => $template->description,
				'schema_json' => (array) ($template->schema_json ?? []),
			])
			->values()
			->all();

		return $this->success([
			'default' => $defaultTemplates,
			'site' => $siteTemplates,
		]);
	}

	public function sections(): JsonResponse
	{
		$this->authorize('viewAny', SectionTemplate::class);

		$sections = SectionTemplate::query()
			->where('is_active', true)
			->latest('id')
			->get()
			->map(static fn (SectionTemplate $template): array => [
				'id' => $template->id,
				'slug' => $template->slug,
				'name' => $template->name,
				'schema_json' => (array) ($template->schema_json ?? []),
			])
			->values()
			->all();

		return $this->success($sections);
	}
}
