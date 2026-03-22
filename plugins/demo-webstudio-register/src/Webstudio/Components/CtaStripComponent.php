<?php

namespace Plugins\DemoWebstudioRegister\Webstudio\Components;

use Pagify\PageBuilder\Webstudio\Contracts\CustomComponent;
use Pagify\PageBuilder\Webstudio\Support\ComponentDefinitionBuilder;

class CtaStripComponent implements CustomComponent
{
	/**
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		return ComponentDefinitionBuilder::make('cta-strip', 'Demo CTA Strip')
			->description('Simple CTA strip registered by plugin class.')
			->icon('📣')
			->category('Demo Components')
			->tag('div')
			->classes('demo-cta-strip')
			->styles('display:flex; gap:12px; align-items:center; justify-content:space-between; padding:16px; border:1px solid #cbd5e1;')
			->attributes([
				'role' => 'region',
				'aria-label' => 'call-to-action strip',
			])
			->children([
				'hero-banner',
				[
					'element' => 'p',
					'class' => ['demo-cta-strip__note'],
					'text' => 'Simple CTA strip',
				],
			])
			->toArray();
	}
}
