{{-- Shown for every 404: unknown URLs (via Route::fallback in routes/web.php), abort(404), missing records. --}}
@php($signedIn = auth()->check())
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex" />
    <title>Page not found — Tech Aid</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { brand: '#152a9e', 'brand-dark': '#0e1d70', teal: '#2dd4bf' },
                fontFamily: { display: ['Poppins', 'system-ui', 'sans-serif'] },
            } }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>body { font-family: 'Poppins', system-ui, sans-serif; background: #f6f7fb; }</style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center px-4 py-10">

    <a href="{{ $signedIn ? route('dashboard') : route('login') }}" class="flex items-center gap-2.5 mb-8">
        <span class="w-9 h-9 rounded-xl bg-brand flex items-center justify-center shadow-sm">
            <svg viewBox="0 0 48 48" class="w-5 h-5" aria-hidden="true">
                <defs>
                    <linearGradient id="nf-logo" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#2dd4bf"/><stop offset="100%" stop-color="#fff"/>
                    </linearGradient>
                </defs>
                <path d="M14 28 C18 18, 26 14, 34 16 C28 18, 24 24, 26 32 C20 32, 15 32, 14 28 Z" fill="url(#nf-logo)"/>
            </svg>
        </span>
        <span class="font-display font-bold text-brand text-lg">Tech Aid</span>
    </a>

    <main class="w-full max-w-md bg-white border border-gray-100 rounded-xl shadow-sm px-6 py-10 sm:px-10 text-center">
        <div class="w-14 h-14 rounded-xl bg-brand/10 flex items-center justify-center mx-auto mb-5">
            <i data-lucide="map-pin-off" class="w-6 h-6 text-brand"></i>
        </div>

        <p class="font-display font-extrabold text-brand text-5xl tracking-tight">404</p>
        <h1 class="font-display font-bold text-gray-900 text-xl mt-2">Page not found</h1>
        <p class="text-sm text-gray-600 mt-2 leading-relaxed">
            The page you're looking for doesn't exist or may have been moved.
        </p>
        <p class="mt-3 inline-block max-w-full truncate rounded-md bg-gray-50 border border-gray-100 px-2.5 py-1 text-xs text-gray-500" title="/{{ request()->path() }}">
            /{{ request()->path() }}
        </p>

        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            @if ($signedIn)
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark text-white font-display font-semibold text-sm px-5 py-2.5 rounded-lg transition-colors">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Back to dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark text-white font-display font-semibold text-sm px-5 py-2.5 rounded-lg transition-colors">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Go to sign in
                </a>
            @endif
            <button type="button" onclick="history.length > 1 ? history.back() : (location.href = '{{ $signedIn ? route('dashboard') : route('login') }}')"
                    class="inline-flex items-center justify-center gap-2 border border-gray-200 hover:border-brand hover:text-brand text-gray-700 font-display font-semibold text-sm px-5 py-2.5 rounded-lg transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Go back
            </button>
        </div>
    </main>

    <p class="text-xs text-gray-500 mt-6">Internal technology support &amp; ticketing for optimusbank.com</p>

    <script>lucide.createIcons();</script>
</body>
</html>
