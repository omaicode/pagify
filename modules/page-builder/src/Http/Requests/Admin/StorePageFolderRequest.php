<?php

namespace Pagify\PageBuilder\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StorePageFolderRequest extends FormRequest
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
			'folder_id' => ['nullable', 'string', 'max:160'],
			'name' => ['required', 'string', 'max:160'],
			'slug' => ['required', 'string', 'max:160'],
			'parent_folder_id' => ['nullable', 'string', 'max:160'],
		];
	}

	protected function prepareForValidation(): void
	{
		$name = trim((string) $this->input('name', ''));
		$slug = trim((string) $this->input('slug', ''));

		if ($slug === '' && $name !== '') {
			$slug = Str::slug($name);
		}

		$this->merge([
			'slug' => $slug,
		]);
	}
}
