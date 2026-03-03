<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
</head>
<body>
    <h1>Admin Login</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('core.admin.login.store') }}">
        @csrf
        <label>
            Username
            <input name="username" type="text" value="{{ old('username') }}" required>
        </label>

        <label>
            Password
            <input name="password" type="password" required>
        </label>

        <label>
            <input name="remember" type="checkbox" value="1"> Remember me
        </label>

        <button type="submit">Sign in</button>
    </form>
</body>
</html>
