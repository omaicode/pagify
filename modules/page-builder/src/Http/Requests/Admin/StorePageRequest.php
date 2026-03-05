<?php

namespace Pagify\PageBuilder\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StorePageRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function rules(): array
	{
		return [
			'title' => ['required', 'string', 'max:160'],
			'slug' => ['required', 'string', 'max:160', 'alpha_dash'],
			'status' => ['nullable', 'string', 'in:draft,published'],
			'layout' => ['nullable', 'array'],
			'layout.type' => ['nullable', 'string', 'in:grapesjs'],
			'layout.grapes' => ['nullable', 'array'],
			'layout.grapes.html' => ['nullable', 'string'],
			'layout.grapes.css' => ['nullable', 'string'],
			'layout.grapes.projectData' => ['nullable', 'array'],
			'layout.grapes.updated_at' => ['nullable', 'date'],
			'layout.sections' => ['nullable', 'array'],
			'layout.sections.*.id' => ['nullable', 'string', 'max:80'],
			'layout.sections.*.blocks' => ['nullable', 'array'],
			'layout.sections.*.blocks.*.type' => ['required_with:layout.sections.*.blocks', 'string', 'max:100'],
			'layout.sections.*.blocks.*.props' => ['nullable', 'array'],
			'layout.sections.*.blocks.*.styles' => ['nullable', 'array'],
			'layout.sections.*.blocks.*.styles.desktop' => ['nullable', 'array'],
			'layout.sections.*.blocks.*.styles.tablet' => ['nullable', 'array'],
			'layout.sections.*.blocks.*.styles.mobile' => ['nullable', 'array'],
			'seo_meta' => ['nullable', 'array'],
			'seo_meta.title' => ['nullable', 'string', 'max:160'],
			'seo_meta.description' => ['nullable', 'string', 'max:300'],
			'seo_meta.og_image' => ['nullable', 'string', 'max:255'],
			'seo_meta.canonical_url' => ['nullable', 'url', 'max:255'],
			'seo_meta.json_ld' => ['nullable', 'array'],
		];
	}

	protected function prepareForValidation(): void
	{
		$rawSlug = $this->input('slug');

		if (! is_string($rawSlug)) {
			return;
		}

		$normalizedSlug = Str::slug(trim($rawSlug));

		$this->merge([
			'slug' => $normalizedSlug !== '' ? $normalizedSlug : trim($rawSlug),
		]);
	}
}
