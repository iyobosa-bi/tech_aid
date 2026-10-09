@extends('layouts.guest', ['title' => 'Enter your code — Tech Aid'])

{{-- Forgot password, step 2 of 3: the 6-digit code (PasswordResetController::showCode / verifyCode / resendCode).
     Reads the same whether or not the email has an account. --}}
@push('head')
    <link rel="preload" as="image" href="{{ asset('images/login-bg.jpg') }}" fetchpriority="high" />
@endpush

@section('content')
<x-auth-shell x-data="resetCode({{ Js::from(['secondsLeft' => $secondsLeft, 'debugCode' => session('debug_code')]) }})">
    <h2 class="font-display font-extrabold text-brand text-2xl sm:text-[1.75rem] leading-tight">Enter your code</h2>
    <p class="mt-3 text-sm sm:text-base text-gray-500 leading-relaxed">
        If an account exists for <strong class="font-semibold text-gray-700 break-words">{{ $email }}</strong>, we've sent it a
        6-digit code. It expires in {{ intdiv(\App\Enums\OtpPurpose::PasswordReset->lifetimeSeconds(), 60) }} minutes.
    </p>

    @include('auth.partials.notices')

    <form method="POST" action="{{ route('password.code.verify') }}" class="mt-7" novalidate @submit="verifying = true">
        @csrf
        <input type="hidden" name="code" :value="code.join('')" />

        <fieldset>
            <legend class="sr-only">6-digit code</legend>
            <div class="grid grid-cols-6 gap-2 sm:gap-3 max-w-[27rem] mx-auto" x-ref="boxes" @paste.prevent="onPaste($event)">
                <template x-for="(digit, i) in code" :key="i">
                    <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1"
                           x-model="code[i]" @input="onInput(i, $event)" @keydown="onKeydown(i, $event)"
                           :aria-label="`Digit ${i + 1} of 6`"
                           class="w-full min-w-0 h-14 sm:h-16 text-center text-xl sm:text-2xl font-semibold rounded-xl border text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand
                                  {{ $errors->has('code') ? 'border-red-400' : 'border-gray-300' }}" />
                </template>
            </div>
        </fieldset>

        @error('code')
            <p class="mt-4 text-sm text-red-600 text-center" role="alert">{{ $message }}</p>
        @enderror

        <p x-show="secondsLeft > 0" class="mt-6 text-center text-sm text-gray-500">
            You can resend the code in <strong class="font-semibold text-gray-700 tabular-nums" x-text="countdown"></strong>
        </p>
        <p x-show="secondsLeft === 0" x-cloak class="mt-6 text-center text-sm text-gray-500">Didn't get it, or did it expire? Send a new one below.</p>

        <button type="submit" :disabled="verifying"
                class="mt-6 w-full inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark disabled:opacity-80 disabled:cursor-wait text-white text-base font-semibold py-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40">
            <span x-show="!verifying">Verify code</span>
            <span x-show="verifying" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') Verifying</span>
        </button>
    </form>

    {{-- Its own form, so it never sends the typed digits. Locked until the countdown ends. --}}
    <form method="POST" action="{{ route('password.code.resend') }}" @submit="resending = true">
        @csrf
        <button type="submit" :disabled="secondsLeft > 0 || resending"
                :class="secondsLeft > 0 ? 'border-gray-200 text-gray-400 cursor-not-allowed' : 'border-brand text-brand hover:bg-brand/5'"
                class="mt-3 w-full flex items-center justify-between gap-3 bg-white border text-base font-medium px-5 py-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40">
            <span x-show="!resending">Resend code</span>
            <span x-show="resending" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner', ['tone' => 'brand']) Sending</span>
            <span x-show="secondsLeft > 0" class="text-sm tabular-nums" x-text="countdown" aria-hidden="true"></span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('password.request') }}" class="font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Use a different email</a>
    </p>
</x-auth-shell>

<script src="{{ asset('js/password-reset.js') }}?v={{ filemtime(public_path('js/password-reset.js')) }}"></script>
@endsection
