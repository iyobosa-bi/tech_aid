@extends('layouts.guest', ['title' => 'Choose a new password — Tech Aid'])

{{-- Forgot password, step 3 of 3: the new password (PasswordResetController::edit / update).
     Only reachable for a few minutes after a code was verified. --}}
@push('head')
    <link rel="preload" as="image" href="{{ asset('images/login-bg.jpg') }}" fetchpriority="high" />
@endpush

@section('content')
<x-auth-shell x-data="newPassword()">
    <h2 class="font-display font-extrabold text-brand text-2xl sm:text-[1.75rem] leading-tight">Choose a new password</h2>
    <p class="mt-3 text-sm sm:text-base text-gray-500 leading-relaxed">
        You'll be signed out everywhere else. Next time you sign in, we'll still email you a login code.
    </p>

    @include('auth.partials.notices')

    <form method="POST" action="{{ route('password.store') }}" class="mt-7 space-y-6" novalidate @submit="saving = true">
        @csrf

        @foreach (['password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
            <div>
                <label for="{{ $field }}" class="block font-display font-semibold text-brand text-sm mb-2">{{ $label }}</label>
                <div class="relative">
                    <i data-lucide="lock" class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input id="{{ $field }}" name="{{ $field }}" :type="show ? 'text' : 'password'" required autocomplete="new-password"
                           x-model="{{ $field === 'password' ? 'password' : 'confirmation' }}" @if ($loop->first) autofocus @endif
                           @error($field) aria-invalid="true" @enderror
                           class="w-full pl-11 pr-11 py-3 rounded-lg border text-gray-800 text-base placeholder:text-gray-400 focus:outline-none focus:ring-2
                                  {{ $errors->has($field) ? 'border-red-400 bg-red-50/40 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-200 bg-white focus:ring-brand/20 focus:border-brand' }}" />
                    <button type="button" @click="show = !show" :aria-label="show ? 'Hide passwords' : 'Show passwords'"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <span x-show="!show"><i data-lucide="eye" class="w-5 h-5"></i></span>
                        <span x-show="show" x-cloak><i data-lucide="eye-off" class="w-5 h-5"></i></span>
                    </button>
                </div>
                @error($field)
                    <p class="mt-2 flex items-start gap-1.5 text-sm font-medium text-red-600">
                        <i data-lucide="circle-alert" class="w-4 h-4 mt-0.5 shrink-0"></i> {{ $message }}
                    </p>
                @enderror
            </div>
        @endforeach

        {{-- The same rules as Password::defaults() (AppServiceProvider); the server checks them again. --}}
        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 text-sm" aria-label="Password rules">
            <template x-for="rule in rules" :key="rule.label">
                <li class="flex items-center gap-2" :class="rule.met ? 'text-green-700' : 'text-gray-500'">
                    <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0 text-[10px] font-bold"
                          :class="rule.met ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'" x-text="rule.met ? '✓' : '•'" aria-hidden="true"></span>
                    <span x-text="rule.label"></span><span class="sr-only" x-text="rule.met ? '(done)' : '(not yet)'"></span>
                </li>
            </template>
        </ul>

        <button type="submit" :disabled="saving"
                class="w-full inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark disabled:opacity-80 disabled:cursor-wait text-white text-base font-semibold py-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40">
            <span x-show="!saving">Reset password</span>
            <span x-show="saving" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') Saving</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to sign in
        </a>
    </p>
</x-auth-shell>

<script src="{{ asset('js/password-reset.js') }}?v={{ filemtime(public_path('js/password-reset.js')) }}"></script>
@endsection
