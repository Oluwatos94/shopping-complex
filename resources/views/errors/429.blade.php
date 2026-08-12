<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slow down — jiidaa</title>
    {{-- Inlined: this page has to render when the app is under load, without a build manifest. --}}
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: #faf9f6;
            color: #1f2421;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
        }
        .card {
            max-width: 28rem;
            width: 100%;
            background: #fff;
            border: 1px solid #e7e5df;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
        }
        .code { font-size: .75rem; letter-spacing: .12em; text-transform: uppercase; color: #8a8a80; margin: 0 0 .75rem; }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; }
        p { margin: 0 0 1.5rem; color: #5c5f5a; font-size: .9375rem; }
        a {
            display: inline-block;
            padding: .625rem 1.25rem;
            border-radius: .5rem;
            background: #4a5d3a;
            color: #fff;
            text-decoration: none;
            font-size: .875rem;
            font-weight: 600;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #14171a; color: #e8e6e1; }
            .card { background: #1c2024; border-color: #2c3238; }
            p { color: #a8aca6; }
        }
    </style>
</head>
<body>
    <div class="card">
        <p class="code">Error 429</p>
        <h1>Slow down a moment</h1>
        <p>{{ $message ?? 'Too many requests. Please wait a moment and try again.' }}</p>
        <a href="{{ url('/') }}">Back to home</a>
    </div>
</body>
</html>
