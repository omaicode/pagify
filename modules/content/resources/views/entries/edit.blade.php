<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit entry</title>
</head>
<body>
    <main>
        <h1>Edit entry {{ $entry->slug }} for {{ $contentType->name }}</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

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

        <form method="POST" action="{{ route('content.admin.entries.update', [$contentType->slug, $entry->id]) }}">
            @csrf
            @method('PUT')

            <label>
                Entry slug
                <input type="text" name="slug" value="{{ old('slug', $entry->slug) }}" required>
            </label>

            <label>
                Status
                <select name="status">
                    @foreach (['draft', 'published', 'scheduled'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $entry->status) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>

            @foreach ($formFields as $field)
                @php
                    $currentValue = old('data.' . $field['key'], $entry->data_json[$field['key']] ?? null);
                @endphp
                <fieldset>
                    <legend>{{ $field['label'] }} ({{ $field['field_type'] }})</legend>

                    @if ($field['field_type'] === 'boolean')
                        <input type="checkbox" name="data[{{ $field['key'] }}]" value="1" @checked((bool) $currentValue)>
                    @elseif ($field['field_type'] === 'select')
                        <select name="data[{{ $field['key'] }}]">
                            <option value="">-- choose --</option>
                            @foreach (($field['config']['options'] ?? []) as $option)
                                <option value="{{ $option }}" @selected($currentValue == $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @elseif ($field['field_type'] === 'repeater')
                        <textarea name="data[{{ $field['key'] }}]">{{ is_array($currentValue) ? json_encode($currentValue, JSON_UNESCAPED_UNICODE) : $currentValue }}</textarea>
                    @else
                        <input type="text" name="data[{{ $field['key'] }}]" value="{{ $currentValue }}">
                    @endif
                </fieldset>
            @endforeach

            <button type="submit">Update</button>
        </form>

        <form method="POST" action="{{ route('content.admin.entries.destroy', [$contentType->slug, $entry->id]) }}" style="margin-top: 1rem;">
            @csrf
            @method('DELETE')
            <button type="submit">Delete</button>
        </form>

        <p><a href="{{ route('content.admin.entries.revisions.index', [$contentType->slug, $entry->id]) }}">View revisions</a></p>
        <p><a href="{{ route('content.admin.entries.index', $contentType->slug) }}">Back to entries</a></p>
    </main>
</body>
</html>
