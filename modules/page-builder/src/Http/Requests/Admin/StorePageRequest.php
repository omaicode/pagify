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
			'layout.type' => ['required_with:layout', 'string', 'in:webstudio'],
			'layout.webstudio' => ['nullable', 'array'],
			'layout.webstudio.html' => ['nullable', 'string'],
			'layout.webstudio.css' => ['nullable', 'string'],
			'layout.webstudio.document' => ['nullable', 'array'],
			'layout.webstudio.document.html' => ['nullable', 'string'],
			'layout.webstudio.styles' => ['nullable', 'array'],
			'layout.webstudio.assets' => ['nullable', 'array'],
			'layout.webstudio.meta' => ['nullable', 'array'],
			'layout.webstudio.updated_at' => ['nullable', 'date'],
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
		$title = $this->input('title');

		$slugSource = is_string($rawSlug) ? trim($rawSlug) : '';

		if ($slugSource === '' && is_string($title)) {
			$slugSource = trim($title);
		}

		$normalizedSlug = Str::slug($slugSource);

		$this->merge([
			'slug' => $normalizedSlug !== '' ? $normalizedSlug : $slugSource,
		]);
	}
}
