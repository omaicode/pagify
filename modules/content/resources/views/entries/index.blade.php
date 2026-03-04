<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entries</title>
</head>
<body>
    <main>
        <h1>Entries for {{ $contentType->name }} ({{ $contentType->slug }})</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <p><a href="{{ route('content.admin.entries.create', $contentType->slug) }}">Create entry</a></p>

        <ul>
            @forelse($entries as $entry)
                <li>
                    <strong>{{ $entry->slug }}</strong>
                    [{{ $entry->status }}]
                    - <a href="{{ route('content.admin.entries.edit', [$contentType->slug, $entry->id]) }}">Edit</a>

                    @if (! empty($hydratedRelations[$entry->id] ?? []))
                        <ul>
                            @foreach (($hydratedRelations[$entry->id] ?? []) as $fieldKey => $relations)
                                <li>
                                    {{ $fieldKey }}:
                                    {{ collect($relations)->pluck('target_slug')->filter()->join(', ') }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @empty
                <li>No entries yet.</li>
            @endforelse
        </ul>

        {{ $entries->links() }}

        <p><a href="{{ route('content.admin.types.edit', $contentType) }}">Back to content type</a></p>
    </main>
</body>
</html>
