<?php

namespace Pagify\PageBuilder\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MovePageFolderItemRequest extends FormRequest
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
			'item_type' => ['required', 'string', 'in:folder,page'],
			'item_id' => ['required', 'string', 'max:160'],
			'parent_folder_id' => ['nullable', 'string', 'max:160'],
			'index_within_children' => ['nullable', 'integer', 'min:0'],
		];
	}
}
