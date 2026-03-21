<?php

namespace Plugins\DemoWebstudioRegister\Webstudio\Components;

use Pagify\PageBuilder\Contracts\WebstudioCustomComponent;
use Pagify\PageBuilder\Support\WebstudioComponentDefinitionBuilder;

class HeroBannerComponent implements WebstudioCustomComponent
{
	/**
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		return WebstudioComponentDefinitionBuilder::make('hero-banner', 'Demo Hero Banner')
			->description('Demo component from plugin class registration.')
			->icon('🧪')
			->category('Demo Components')
			->element('section')
			->classes(['demo-hero', 'demo-hero--primary'])
			->styles([
				'padding' => '24px',
				'border-radius' => '12px',
				'background' => 'linear-gradient(120deg, #d1fae5 0%, #ecfeff 100%)',
			])
			->attribute('data-variant', 'hero')
			->text('Demo Hero Banner')
			->toArray();
	}
}
