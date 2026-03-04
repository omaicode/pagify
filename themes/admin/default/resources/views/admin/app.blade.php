<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Pagify') }}</title>

    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @inertiaHead
</head>
<body class="bg-white text-slate-900 antialiased">
    @inertia
</body>
</html>
