<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>{{ $title ?? 'Tech Aid' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#152a9e',
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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <style>
        [x-cloak]{ display:none!important; }
        html, body{ height: 100%; margin: 0; }
        body{ font-family: 'Poppins', system-ui, sans-serif; background: #f6f7fb; }
    </style>
</head>
<body class="h-full overflow-hidden">

<div x-data="{ sidebarOpen: false, notifOpen: false }" class="h-full flex">

    <!-- Mobile topbar -->
    <div class="lg:hidden fixed top-0 left-0 right-0 z-30 h-14 flex items-center justify-between px-4 bg-white border-b border-gray-100 shadow-sm">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-brand flex items-center justify-center">
                <svg viewBox="0 0 48 48" class="w-4 h-4">
                    <defs>
                        <linearGradient id="lg1" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#2dd4bf"/><stop offset="100%" stop-color="#fff"/>
                        </linearGradient>
                    </defs>
                    <path d="M14 28 C18 18, 26 14, 34 16 C28 18, 24 24, 26 32 C20 32, 15 32, 14 28 Z" fill="url(#lg1)"/>
                </svg>
            </div>
            <span class="font-display font-bold text-brand text-sm">Tech Aid</span>
        </div>
        <button @click="sidebarOpen = !sidebarOpen" class="text-brand">
            <i data-lucide="menu" class="w-5 h-5" x-show="!sidebarOpen"></i>
            <i data-lucide="x" class="w-5 h-5" x-show="sidebarOpen"></i>
        </button>
    </div>

    <!-- Sidebar -->
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed lg:relative lg:translate-x-0 top-0 left-0 h-full w-64 bg-brand z-40
               transition-transform duration-200 pt-14 lg:pt-0 flex flex-col"
    >
        <div class="hidden lg:flex items-center gap-2.5 px-6 py-6">
            <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center">
                <svg viewBox="0 0 48 48" class="w-5 h-5">
                    <defs>
                        <linearGradient id="lg2" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#2dd4bf"/><stop offset="100%" stop-color="#152a9e"/>
                        </linearGradient>
                    </defs>
                    <path d="M14 28 C18 18, 26 14, 34 16 C28 18, 24 24, 26 32 C20 32, 15 32, 14 28 Z" fill="url(#lg2)"/>
                </svg>
            </div>
            <span class="font-display font-bold text-white text-base">Tech Aid</span>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <p class="font-display text-[10px] uppercase tracking-wider text-white/40 px-3 mb-2 mt-2">Queue</p>
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                      {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
            </a>
            <a href="{{ Route::has('tickets.index') ? route('tickets.index') : '#' }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm
                      {{ request()->routeIs('tickets.*') ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="ticket" class="w-4 h-4"></i> My Tickets
            </a>
            <a href="{{ Route::has('tickets.create') ? route('tickets.create') : '#' }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 hover:bg-white/10 hover:text-white text-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> New Ticket
            </a>

            <p class="font-display text-[10px] uppercase tracking-wider text-white/40 px-3 mb-2 mt-6">Account</p>
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 hover:bg-white/10 hover:text-white text-sm">
                <i data-lucide="settings" class="w-4 h-4"></i> Settings
            </a>
        </nav>

        <div class="p-4">
            <div class="flex items-center gap-2.5 bg-white/10 rounded-lg px-3 py-2.5">
                <div class="w-8 h-8 rounded-full bg-teal/25 flex items-center justify-center">
                    <span class="font-display text-[11px] text-white font-semibold">
                        {{ collect(explode(' ', auth()->user()->name ?? 'U N'))->map(fn($n) => strtoupper($n[0] ?? ''))->join('') }}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-white truncate">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-[10px] text-white/50 truncate">{{ method_exists(auth()->user(), 'getRoleNames') ? (auth()->user()->getRoleNames()->first() ?? '') : '' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-white/50 hover:text-white">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak class="lg:hidden fixed inset-0 bg-black/40 z-30"></div>

    <!-- Main -->
    <main class="flex-1 overflow-y-auto pt-14 lg:pt-0">

        <div class="hidden lg:flex items-center justify-between px-8 py-5 bg-white border-b border-gray-100 sticky top-0 z-20">
            <div>
                <h1 class="font-display font-bold text-xl text-gray-800">{{ $pageTitle ?? 'Dashboard' }}</h1>
                <p class="text-xs text-gray-400 mt-0.5">optimusbank.com · welcome back, {{ explode(' ', auth()->user()->name ?? 'there')[0] }}</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative">
                    <button @click="notifOpen = !notifOpen" class="relative w-9 h-9 rounded-full bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-brand text-white text-[9px] font-bold flex items-center justify-center">3</span>
                    </button>
                    <div x-show="notifOpen" x-cloak @click.outside="notifOpen = false"
                         class="absolute right-0 mt-2 w-80 bg-white border border-gray-100 rounded-xl shadow-xl z-30">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-600">Notifications</span>
                            <span class="text-[11px] text-brand cursor-pointer">mark all read</span>
                        </div>
                        <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto" id="notification-list">
                            {{-- populated via Alpine setInterval + fetch() polling, see docs/06-data-model.md --}}
                        </div>
                    </div>
                </div>
                <a href="{{ Route::has('tickets.create') ? route('tickets.create') : '#' }}" class="flex items-center gap-2 bg-brand hover:bg-brand-dark text-white font-display font-semibold text-xs px-4 py-2.5 rounded-lg transition-colors">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> New Ticket
                </a>
            </div>
        </div>

        <div class="lg:hidden px-5 pt-5">
            <h1 class="font-display font-bold text-xl text-gray-800">{{ $pageTitle ?? 'Dashboard' }}</h1>
            <p class="text-xs text-gray-400 mt-0.5">optimusbank.com</p>
        </div>

        <div class="p-5 sm:p-8">
            {{ $slot ?? '' }}
            @yield('content')
        </div>
    </main>
</div>

<script>
    lucide.createIcons();

    // Defense-in-depth alongside the route's Cache-Control: no-store header
    // (see docs/04-tech-stack.md, Security): force a fresh request if the
    // browser ever restores this page from its back/forward cache, so a
    // logged-out session can never keep showing a stale authenticated page.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) window.location.reload();
    });
    
</script>
</body>
</html>
