<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schema migration plans</title>
</head>
<body>
    <main>
        <h1>Schema migration plans for {{ $contentType->name }}</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <ul>
            @forelse($plans as $plan)
                <li style="margin-bottom: 1rem;">
                    <strong>#{{ $plan->id }}</strong>
                    [{{ $plan->status }}]
                    @if ($plan->planned_at)
                        planned at {{ $plan->planned_at }}
                    @endif

                    @if (! empty($plan->plan_json['summary'] ?? []))
                        <div>
                            additions: {{ $plan->plan_json['summary']['additions'] ?? 0 }},
                            removals: {{ $plan->plan_json['summary']['removals'] ?? 0 }},
                            updates: {{ $plan->plan_json['summary']['updates'] ?? 0 }}
                        </div>
                    @endif

                    @if ($plan->error_message)
                        <div>Error: {{ $plan->error_message }}</div>
                    @endif
                </li>
            @empty
                <li>No migration plans yet.</li>
            @endforelse
        </ul>

        {{ $plans->links() }}

        <p><a href="{{ route('content.admin.types.builder.edit', $contentType) }}">Back to builder</a></p>
        <p><a href="{{ route('content.admin.types.edit', $contentType) }}">Back to content type edit</a></p>
    </main>
</body>
</html>
