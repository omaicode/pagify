<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ data_get($translations, 'title', 'Pagify Installer') }}</title>

    @foreach (($assets['css'] ?? []) as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
</head>
<body>
<div id="installer-app"></div>
<script>
    window.Pagify = window.Pagify || {};
    window.Pagify.installerPayload = {{ \Illuminate\Support\Js::from($payload) }};
</script>

@if (($assets['js'] ?? []) !== [])
    @foreach ($assets['js'] as $src)
        <script type="module" src="{{ $src }}"></script>
    @endforeach
@else
    <div style="margin: 24px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-family: sans-serif;">
        <h2 style="margin: 0 0 8px;">Installer UI build is missing</h2>
        <p style="margin: 0; color: #475569;">Run npm install && npm run build inside modules/installer/frontend to generate public/installer assets.</p>
    </div>
@endif
</body>
</html>
