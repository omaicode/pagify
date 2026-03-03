<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create content type</title>
</head>
<body>
    <main>
        <h1>Create content type</h1>

        @if ($errors->any())
            <div>
                <strong>Validation failed:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('content.admin.types.store') }}">
            @csrf

            <label>
                Name
                <input type="text" name="name" value="{{ old('name') }}" required>
            </label>

            <label>
                Slug
                <input type="text" name="slug" value="{{ old('slug') }}" required>
            </label>

            <label>
                Description
                <textarea name="description">{{ old('description') }}</textarea>
            </label>

            <label>
                Active
                <input type="checkbox" name="is_active" value="1" checked>
            </label>

            <h2>First field</h2>

            <label>
                Key
                <input type="text" name="fields[0][key]" value="{{ old('fields.0.key', 'title') }}" required>
            </label>

            <label>
                Label
                <input type="text" name="fields[0][label]" value="{{ old('fields.0.label', 'Title') }}" required>
            </label>

            <label>
                Field type
                <select name="fields[0][field_type]">
                    @foreach ($fieldTypes as $fieldType)
                        <option value="{{ $fieldType }}" @selected(old('fields.0.field_type', 'text') === $fieldType)>{{ $fieldType }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                Config JSON
                <textarea name="fields[0][config]">{{ old('fields.0.config', '{}') }}</textarea>
            </label>

            <label>
                Validation JSON
                <textarea name="fields[0][validation]">{{ old('fields.0.validation', '{}') }}</textarea>
            </label>

            <label>
                Conditional JSON
                <textarea name="fields[0][conditional]">{{ old('fields.0.conditional', '{}') }}</textarea>
            </label>

            <label>
                Sort order
                <input type="number" name="fields[0][sort_order]" value="{{ old('fields.0.sort_order', 0) }}">
            </label>

            <label>
                Required
                <input type="checkbox" name="fields[0][is_required]" value="1" @checked(old('fields.0.is_required', true))>
            </label>

            <label>
                Localized
                <input type="checkbox" name="fields[0][is_localized]" value="1" @checked(old('fields.0.is_localized', false))>
            </label>

            <button type="submit">Save</button>
        </form>
    </main>
</body>
</html>
