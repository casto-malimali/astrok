<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @vite('resources/css/app.css')
</head>

<body style="font-family: system-ui, sans-serif; margin: 2rem">
    @if ($greet)
        <h1 style="margin-bottom: .5rem">Hello, {{ e($name) }} 👋</h1>
    @else
        <h1 style="margin-bottom: .5rem">Welcome, {{ e($name) }}</h1>
    @endif

    <p>Route name: <code>{{ Route::currentRouteName() }}</code></p>

    <p style="margin-top:1rem">
        Try querystrings:
        <a href="{{ route('hello', ['name' => 'Casto']) }}?greet=0">greet=0</a> |
        <a href="{{ route('hello', ['name' => 'Malimali']) }}?greet=1">greet=1</a>
    </p>
</body>

</html>
