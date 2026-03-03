<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content types</title>
</head>
<body>
    <main>
        <h1>Content types</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <p><a href="{{ route('content.admin.types.create') }}">Create content type</a></p>

        <ul>
            @forelse($contentTypes as $contentType)
                <li>
                    <strong>{{ $contentType->name }}</strong> ({{ $contentType->slug }})
                    - <a href="{{ route('content.admin.types.edit', $contentType) }}">Edit</a>
                </li>
            @empty
                <li>No content types yet.</li>
            @endforelse
        </ul>

        {{ $contentTypes->links() }}
    </main>
</body>
</html>
