<?php

namespace Pagify\PageBuilder\Support;

class WebstudioComponentDefinitionBuilder
{
	/**
	 * @param array<string, mixed> $definition
	 */
	private function __construct(
		private array $definition,
	) {
	}

	public static function make(string $key, ?string $label = null): self
	{
		$key = trim($key);
		$label = is_string($label) ? trim($label) : null;

		return new self([
			'key' => $key,
			'label' => $label !== '' ? $label : $key,
		]);
	}

	public function description(string $description): self
	{
		$this->definition['description'] = trim($description);

		return $this;
	}

	public function icon(string $icon): self
	{
		$this->definition['icon'] = trim($icon);

		return $this;
	}

	public function category(string $category): self
	{
		$this->definition['category'] = trim($category);

		return $this;
	}

	public function owner(string $owner): self
	{
		$this->definition['owner'] = trim($owner);

		return $this;
	}

	public function ownerType(string $ownerType): self
	{
		$this->definition['owner_type'] = trim($ownerType);

		return $this;
	}

	public function element(string $tag): self
	{
		$this->definition['element'] = trim($tag);

		return $this;
	}

	public function tag(string $tag): self
	{
		$this->definition['tag'] = trim($tag);

		return $this;
	}

	/**
	 * @param string|array<int, string> $class
	 */
	public function classes(string|array $class): self
	{
		$this->definition['class'] = $class;

		return $this;
	}

	/**
	 * @param string|array<string, scalar> $style
	 */
	public function styles(string|array $style): self
	{
		$this->definition['style'] = $style;

		return $this;
	}

	/**
	 * @param array<string, scalar|bool> $attributes
	 */
	public function attributes(array $attributes): self
	{
		$this->definition['attributes'] = $attributes;

		return $this;
	}

	public function attribute(string $name, string|int|float|bool $value): self
	{
		$attributes = $this->definition['attributes'] ?? [];
		if (! is_array($attributes)) {
			$attributes = [];
		}

		$attributes[$name] = $value;
		$this->definition['attributes'] = $attributes;

		return $this;
	}

	public function text(string $text): self
	{
		$this->definition['text'] = $text;

		return $this;
	}

	public function innerHtml(string $innerHtml): self
	{
		$this->definition['inner_html'] = $innerHtml;

		return $this;
	}

	public function htmlTemplate(string $htmlTemplate): self
	{
		$this->definition['html_template'] = $htmlTemplate;

		return $this;
	}

	/**
	 * @param array<string, mixed> $propsSchema
	 */
	public function propsSchema(array $propsSchema): self
	{
		$this->definition['props_schema'] = $propsSchema;

		return $this;
	}

	/**
	 * @param array<int, mixed> $children
	 */
	public function children(array $children): self
	{
		$this->definition['children'] = $children;

		return $this;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return $this->definition;
	}
}
