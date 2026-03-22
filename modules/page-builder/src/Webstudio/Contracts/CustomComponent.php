<?php

namespace Pagify\PageBuilder\Webstudio\Contracts;

interface CustomComponent
{
	/**
	 * @return array<string, mixed>
	 */
	public function definition(): array;
}
