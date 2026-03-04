<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry revisions</title>
</head>
<body>
    <main>
        <h1>Revisions for {{ $entry->slug }} ({{ $contentType->slug }})</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <h2>Timeline</h2>
        <ul>
            @forelse ($revisions as $revision)
                <li>
                    #{{ $revision->revision_no }} - {{ $revision->action }} - {{ optional($revision->created_at)->toDateTimeString() }}
                    <form method="POST" action="{{ route('content.admin.entries.revisions.rollback', [$contentType->slug, $entry->id, $revision->id]) }}" style="display:inline-block; margin-left: 8px;">
                        @csrf
                        <button type="submit">Rollback to this revision</button>
                    </form>
                </li>
            @empty
                <li>No revisions yet.</li>
            @endforelse
        </ul>

        <h2>Diff compare</h2>
        <form method="GET" action="{{ route('content.admin.entries.revisions.index', [$contentType->slug, $entry->id]) }}">
            <label>
                Left revision
                <select name="left_revision_id">
                    @foreach ($revisions as $revision)
                        <option value="{{ $revision->id }}" @selected($leftRevision?->id === $revision->id)>#{{ $revision->revision_no }} - {{ $revision->action }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                Right revision
                <select name="right_revision_id">
                    @foreach ($revisions as $revision)
                        <option value="{{ $revision->id }}" @selected($rightRevision?->id === $revision->id)>#{{ $revision->revision_no }} - {{ $revision->action }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit">Compare</button>
        </form>

        @if (($diff['changed'] ?? false) === true)
            <ul>
                @foreach (($diff['changes'] ?? []) as $change)
                    <li>
                        <strong>{{ $change['key'] }}</strong>
                        <div>Before: {{ is_array($change['before']) ? json_encode($change['before'], JSON_UNESCAPED_UNICODE) : var_export($change['before'], true) }}</div>
                        <div>After: {{ is_array($change['after']) ? json_encode($change['after'], JSON_UNESCAPED_UNICODE) : var_export($change['after'], true) }}</div>
                    </li>
                @endforeach
            </ul>
        @else
            <p>No differences to show.</p>
        @endif

        <p><a href="{{ route('content.admin.entries.edit', [$contentType->slug, $entry->id]) }}">Back to entry</a></p>
    </main>
</body>
</html>
