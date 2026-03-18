<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Pagify Webstudio Editor</title>

    <script>
        window.__pagifyWebstudioBootstrap = @json($webstudioBootstrap, JSON_UNESCAPED_SLASHES);
    </script>

    @vite('resources/js/webstudio-vite-entry.js', 'build/page-builder')
</head>
<body>
</body>
</html>
