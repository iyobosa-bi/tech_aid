{{--
    The signed-out screens' shell (design/style-notes.md, "Layout — Login"): blue panel with the
    logo on desktop, the background photo, and a floating white card holding $slot. Used by the
    login page and the forgot-password steps. No animations or transitions (paused, see style notes).

    Attributes go on the outer element (e.g. x-data). $inertWhen: an Alpine expression that makes
    both panels inert while true (the login page's OTP modal). $after: rendered after the panels,
    e.g. that modal.
--}}
@props(['inertWhen' => null])

<div {{ $attributes->merge(['class' => 'h-full flex']) }}>
    <div @if ($inertWhen) x-effect="$el.inert = {{ $inertWhen }}" @endif
         class="hidden md:flex md:w-[33%] bg-brand flex-col items-center justify-center relative px-8">
        <div class="rounded-2xl bg-white flex items-center justify-center mb-6 shadow-xl">
            <svg viewBox="0 0 50 50" class="w-40 h-40" aria-hidden="true">
                <defs>
                    <linearGradient id="logoGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#2dd4bf"/>
                        <stop offset="100%" stop-color="#152a9e"/>
                    </linearGradient>
                </defs>
                <circle cx="24" cy="24" r="22" fill="none" stroke="url(#logoGrad)" stroke-width="3"/>
                <path d="M14 28 C18 18, 26 14, 34 16 C28 18, 24 24, 26 32 C20 32, 15 32, 14 28 Z" fill="url(#logoGrad)"/>
            </svg>
        </div>

        <h1 class="font-display font-extrabold text-white text-3xl tracking-tight">Tech Aid</h1>

        <p class="font-display text-white/70 text-sm text-center mt-3 max-w-[220px] leading-relaxed">
            Internal technology support &amp; ticketing for optimusbank.com
        </p>
    </div>

    <!-- RIGHT: full-bleed photo + floating card. bg-brand matches the left panel, so there's no seam while the photo loads. -->
    <div class="flex-1 relative overflow-hidden bg-brand" @if ($inertWhen) x-effect="$el.inert = {{ $inertWhen }}" @endif>
        <img src="{{ asset('images/login-bg.jpg') }}" alt="" fetchpriority="high" width="890" height="1024"
             class="absolute inset-0 w-full h-full object-cover" />

        <!-- soft brand tint: blends the photo's left edge into the blue panel -->
        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgb(21_42_158/0.3)_41%,rgb(21_42_158/0.03)_100%)]"></div>

        <!-- dark overlay, mobile only — improves contrast since blue panel is hidden -->
        <div class="absolute inset-0 bg-brand-dark/50 md:hidden"></div>

        <!-- mobile-only compact header -->
        <div class="md:hidden absolute top-6 left-0 right-0 flex flex-col items-center px-4">
            <div class="w-14 h-14 rounded-xl bg-white flex items-center justify-center mb-2 shadow-lg">
                <svg viewBox="0 0 48 48" class="w-8 h-8" aria-hidden="true">
                    <defs>
                        <linearGradient id="logoGradM" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#2dd4bf"/>
                            <stop offset="100%" stop-color="#152a9e"/>
                        </linearGradient>
                    </defs>
                    <circle cx="24" cy="24" r="22" fill="none" stroke="url(#logoGradM)" stroke-width="3"/>
                    <path d="M14 28 C18 18, 26 14, 34 16 C28 18, 24 24, 26 32 C20 32, 15 32, 14 28 Z" fill="url(#logoGradM)"/>
                </svg>
            </div>
            <p class="font-display font-extrabold text-white text-xl tracking-tight">Tech Aid</p>
        </div>

        {{-- Centred when it fits; scrolls instead of being cut off when it doesn't (small or landscape phones). --}}
        <div class="absolute inset-0 overflow-y-auto flex px-[4%] pt-32 pb-6 md:p-8">
            <div class="m-auto w-full max-w-[520px]">
                <div class="bg-white rounded-2xl shadow-2xl p-7 sm:p-10">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    {{ $after ?? '' }}
</div>
