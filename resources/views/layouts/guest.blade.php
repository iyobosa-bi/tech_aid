<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'Tech Aid' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    <!-- Tailwind CDN, no build step -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#003399',
                        'brand-dark': '#0e1d70',
                        teal: '#2dd4bf',
                    },
                    fontFamily: {
                        display: ['Poppins', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <style>
        [x-cloak]{ display:none!important; }
        html, body{ height: 100%; margin: 0; }
        body{ font-family: 'Poppins', system-ui, sans-serif; }
        @keyframes letterIn { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform: translateY(0); } }
        .letter-anim { display:inline-block; opacity:0; animation: letterIn 0.4s ease-out forwards; }
    </style>
</head>
<body class="h-full">

    {{ $slot ?? '' }}
    @yield('content')

    <script>
        lucide.createIcons();

        // Defense-in-depth alongside the route's Cache-Control: no-store header
        // (see docs/04-tech-stack.md, Security): force a fresh request if the
        // browser ever restores this page from its back/forward cache, so a
        // stale pre-login form (or one still showing an old error) can never
        // reappear without the server getting a chance to re-check things.
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) window.location.reload();
        });
    </script>
</body>
</html>
