<?php

namespace Plugins\DemoWebstudioRegister\Webstudio\Components;

use Pagify\PageBuilder\Webstudio\Contracts\CustomComponent;
use Pagify\PageBuilder\Webstudio\Support\ComponentDefinitionBuilder;

class HeroBannerComponent implements CustomComponent
{
	/**
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		return ComponentDefinitionBuilder::make('hero-banner', 'Demo Hero Banner')
			->description('Demo component from plugin class registration. {{ dynamic.summary }}')
			->icon('🧪')
			->category('Demo Components')
			->dynamicData([
				'summary' => 'Page: {{ page.title }}',
			])
			->element('section')
			->classes(['demo-hero', 'demo-hero--primary'])
			->styles([
				'padding' => '24px',
				'border-radius' => '12px',
				'background' => 'linear-gradient(120deg, #d1fae5 0%, #ecfeff 100%)',
			])
			->attribute('data-variant', '{{ page.slug }}')
			->text('Demo Hero Banner for {{ page.title }}')
			->toArray();
	}
}
