@extends('layouts.guest', ['title' => 'Forgot password — Tech Aid'])

{{-- Forgot password, step 1 of 3: which email? (PasswordResetController::create / sendCode) --}}
@push('head')
    <link rel="preload" as="image" href="{{ asset('images/login-bg.jpg') }}" fetchpriority="high" />
@endpush

@section('content')
<x-auth-shell x-data="{ sending: false }">
    <h2 class="font-display font-extrabold text-brand text-2xl sm:text-[1.75rem] leading-tight">Forgot your password?</h2>
    <p class="mt-3 text-sm sm:text-base text-gray-500 leading-relaxed">
        Enter the email you sign in with. We'll send you a 6-digit code to reset your password.
    </p>

    @include('auth.partials.notices')

    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-6" novalidate @submit="sending = true">
        @csrf
        <div>
            <label for="email" class="block font-display font-semibold text-brand text-sm mb-2">Email</label>
            <div class="relative">
                <i data-lucide="mail" class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="email" name="email" type="email" required autofocus autocomplete="username" maxlength="255"
                       value="{{ old('email', $email) }}" placeholder="name@optimusbank.com"
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                       class="w-full pl-11 pr-3 py-3 rounded-lg border text-gray-800 text-base placeholder:text-gray-400 focus:outline-none focus:ring-2
                              {{ $errors->has('email') ? 'border-red-400 bg-red-50/40 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-200 bg-white focus:ring-brand/20 focus:border-brand' }}" />
            </div>
            @error('email')
                <p id="email-error" class="mt-2 flex items-start gap-1.5 text-sm font-medium text-red-600">
                    <i data-lucide="circle-alert" class="w-4 h-4 mt-0.5 shrink-0"></i> {{ $message }}
                </p>
            @enderror
        </div>

        <button type="submit" :disabled="sending"
                class="w-full inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark disabled:opacity-80 disabled:cursor-wait text-white text-base font-semibold py-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40">
            <span x-show="!sending">Send code</span>
            <span x-show="sending" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') Sending</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to sign in
        </a>
    </p>
</x-auth-shell>
@endsection
