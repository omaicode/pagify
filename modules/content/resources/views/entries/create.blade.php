<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create entry</title>
</head>
<body>
    <main>
        <h1>Create entry for {{ $contentType->name }}</h1>

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

        <form method="POST" action="{{ route('content.admin.entries.store', $contentType->slug) }}">
            @csrf

            <label>
                Entry slug
                <input type="text" name="slug" value="{{ old('slug') }}" required>
            </label>

            <label>
                Status
                <select name="status">
                    @foreach (['draft', 'published', 'scheduled'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $defaultStatus) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>

            @foreach ($formFields as $field)
                <fieldset>
                    <legend>{{ $field['label'] }} ({{ $field['field_type'] }})</legend>

                    @if ($field['field_type'] === 'boolean')
                        <input type="checkbox" name="data[{{ $field['key'] }}]" value="1" @checked(old('data.' . $field['key']))>
                    @elseif ($field['field_type'] === 'select')
                        <select name="data[{{ $field['key'] }}]">
                            <option value="">-- choose --</option>
                            @foreach (($field['config']['options'] ?? []) as $option)
                                <option value="{{ $option }}" @selected(old('data.' . $field['key']) == $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @elseif ($field['field_type'] === 'repeater')
                        <textarea name="data[{{ $field['key'] }}]">{{ old('data.' . $field['key'], '[]') }}</textarea>
                    @else
                        <input type="text" name="data[{{ $field['key'] }}]" value="{{ old('data.' . $field['key']) }}">
                    @endif
                </fieldset>
            @endforeach

            <button type="submit">Save</button>
        </form>

        <p><a href="{{ route('content.admin.entries.index', $contentType->slug) }}">Back to entries</a></p>
    </main>
</body>
</html>
