<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Graphflix' }}</title>
    @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <nav class="border-b border-slate-800 bg-slate-900">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}" class="text-xl font-semibold text-emerald-400">Graphflix</a>
            <div class="flex gap-4 text-sm text-slate-300">
                <a href="{{ url('/home') }}" class="hover:text-white">Movies</a>
                <a href="{{ url('/users') }}" class="hover:text-white">Popular</a>
                <a href="{{ url('/about') }}" class="hover:text-white">About</a>
            </div>
        </div>
    </nav>
    <main class="mx-auto max-w-6xl px-6 py-10">
        @if (!empty($error))
            <div class="mb-8 rounded-lg border border-amber-500/40 bg-amber-500/10 p-4 text-amber-200">
                <p class="font-medium">{{ $error }}</p>
                <p class="mt-1 text-sm text-amber-300/80">Check your CognoDB settings and network connection.</p>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
