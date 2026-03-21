<?php

namespace Pagify\PageBuilder\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdatePageFolderRequest extends FormRequest
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
			'name' => ['sometimes', 'required', 'string', 'max:160'],
			'slug' => ['sometimes', 'required', 'string', 'max:160'],
			'parent_folder_id' => ['sometimes', 'nullable', 'string', 'max:160'],
		];
	}

	protected function prepareForValidation(): void
	{
		$name = trim((string) $this->input('name', ''));
		$slug = trim((string) $this->input('slug', ''));

		if ($this->has('name') && ! $this->has('slug') && $name !== '') {
			$slug = Str::slug($name);
		}

		if ($this->has('slug')) {
			$this->merge(['slug' => $slug]);
		}
	}
}
