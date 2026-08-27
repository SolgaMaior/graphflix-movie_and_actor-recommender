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
<body class="min-h-screen bg-[#f7f1df] text-[#171717]">
    <div id="page-loading" class="page-loading" aria-live="polite" aria-hidden="true">
        <div class="page-loading__card">
            <span class="page-loading__spinner" aria-hidden="true"></span>
            Loading graph data...
        </div>
    </div>
    <nav class="border-b-4 border-black bg-[#ffdf3f]">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <a href="{{ url('/') }}" class="text-2xl font-black uppercase tracking-tight">Graphflix<span class="text-[#ff5c8a]">.</span></a>
            <div class="flex gap-5 text-sm font-black uppercase">
                <a href="{{ url('/home') }}" class="hover:underline">Movies</a>
                <a href="{{ url('/users') }}" class="hover:underline">Popular</a>
                <a href="{{ url('/about') }}" class="hover:underline">About</a>
            </div>
        </div>
    </nav>
    <main class="mx-auto max-w-6xl px-6 py-10">
        @if (!empty($error))
            <div class="mb-8 border-4 border-black bg-[#ffb7d1] p-4 shadow-[6px_6px_0_#171717]">
                <p class="font-black">{{ $error }}</p>
                <p class="mt-1 text-sm font-medium">Check your CognoDB settings and network connection.</p>
            </div>
        @endif
        @yield('content')
    </main>
    <style>
        .page-loading { display: none; position: fixed; inset: 0; z-index: 50; align-items: center; justify-content: center; background: rgba(247, 241, 223, .88); }
        .page-loading.is-visible { display: flex; }
        .page-loading__card { border: 4px solid #171717; background: #ffdf3f; box-shadow: 6px 6px 0 #171717; padding: 1rem 1.25rem; font-weight: 900; text-transform: uppercase; }
        .page-loading__spinner { display: inline-block; width: 1rem; height: 1rem; margin-right: .5rem; border: 3px solid #171717; border-right-color: transparent; border-radius: 9999px; animation: graphflix-spin .7s linear infinite; vertical-align: -.15rem; }
        @keyframes graphflix-spin { to { transform: rotate(360deg); } }
    </style>
    <script>
        (() => {
            const loader = document.getElementById('page-loading');
            const showLoader = () => {
                loader.classList.add('is-visible');
                loader.setAttribute('aria-hidden', 'false');
            };
            const hideLoader = () => {
                loader.classList.remove('is-visible');
                loader.setAttribute('aria-hidden', 'true');
            };

            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('a[href]').forEach((link) => {
                    link.addEventListener('click', (event) => {
                        if (!event.defaultPrevented && link.origin === window.location.origin && link.target !== '_blank') {
                            showLoader();
                        }
                    });
                });
                document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', showLoader));
            });
            window.addEventListener('pageshow', hideLoader);
        })();
    </script>
</body>
</html>
