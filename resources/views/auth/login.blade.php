@extends('layouts.guest', ['title' => 'Login — Tech Aid'])

{{-- Start downloading the photo straight away, alongside the CDN scripts, so it's ready by first paint. --}}
@push('head')
    <link rel="preload" as="image" href="{{ asset('images/login-bg.jpg') }}" fetchpriority="high" />
@endpush

@section('content')

<x-auth-shell
    x-data="loginPage()"
    data-verify-url="{{ route('login.otp.verify') }}"
    data-resend-url="{{ route('login.otp.resend') }}"
    data-cancel-url="{{ route('login.otp.cancel') }}"
    data-email-domain="{{ config('app.staff_email_domain') }}"
    data-old-email="{{ old('email') }}"
    inert-when="otpOpen"
>
                {{-- Both panels go inert while the OTP modal is open (inert-when): nothing behind it can be focused,
                     clicked or typed into. No animations or transitions on this screen for now (design/style-notes.md). --}}
                <h2 class="font-display font-extrabold text-brand text-2xl sm:text-[1.75rem] leading-tight mb-7 sm:mb-8">
                    Sign in to Tech Aid
                </h2>

                {{-- After a password reset ("status"), or when a deactivated account was signed out ("error"). --}}
                @if (session('status'))
                    <div role="status" class="mb-6 flex items-start gap-2 px-3 py-2.5 border border-green-200 bg-green-50 rounded-lg">
                        <i data-lucide="circle-check" class="w-4 h-4 mt-0.5 text-green-600 shrink-0"></i>
                        <p class="text-sm font-medium leading-5 text-green-800">{{ session('status') }}</p>
                    </div>
                @endif
                @if (session('error'))
                    <div role="alert" class="mb-6 flex items-start gap-2 px-3 py-2.5 border border-red-200 bg-red-50 rounded-lg">
                        <i data-lucide="circle-alert" class="w-4 h-4 mt-0.5 text-red-600 shrink-0"></i>
                        <p class="text-sm font-semibold leading-5 text-red-700">{{ session('error') }}</p>
                    </div>
                @endif

                <div
                    x-show="loginError"
                    x-cloak
                    role="alert"
                    class="mb-6 flex items-start gap-2 px-3 py-2.5 border border-red-200 bg-red-50 rounded-lg"
                >
                  <i data-lucide="circle-alert" class="w-4 h-4 text-red-600 shrink-0"></i>
                  <p x-text="loginError" class="text-sm font-semibold leading-5 text-red-700"></p>
                </div>

                {{-- novalidate: our live messages replace the browser's own validation bubbles. --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-6" novalidate @submit.prevent="login()">
                    @csrf

                    <div>
                        <label for="email" class="block font-display font-semibold text-brand text-sm mb-2">Email</label>
                        <div class="relative">
                            <i data-lucide="mail" class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2"
                               :class="emailInvalid ? 'text-red-500' : 'text-gray-400'"></i>
                            <input
                                id="email" name="email" type="email" required autofocus autocomplete="username"
                                placeholder="Email"
                                x-ref="emailInput" x-model="email"
                                @input.debounce.500ms="touched.email = true" @blur="touched.email = true"
                                :aria-invalid="emailInvalid.toString()" aria-describedby="email-error"
                                class="w-full pl-11 pr-3 py-3 rounded-lg border text-gray-800 text-base placeholder:text-gray-400
                                       focus:outline-none focus:ring-2"
                                :class="emailInvalid
                                    ? 'border-red-400 bg-red-50/40 focus:ring-red-500/20 focus:border-red-500'
                                    : 'border-gray-200 bg-white focus:ring-brand/20 focus:border-brand'"
                            />
                        </div>
                        <p id="email-error" x-show="emailInvalid" x-cloak
                           class="mt-2 flex items-start gap-1.5 text-sm font-medium text-red-600">
                            <i data-lucide="circle-alert" class="w-4 h-4 mt-0.5 shrink-0"></i>
                            <span x-text="emailError"></span>
                        </p>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between gap-3 mb-2">
                            <label for="password" class="font-display font-semibold text-brand text-sm">Password</label>
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <i data-lucide="lock" class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2"
                               :class="passwordInvalid ? 'text-red-500' : 'text-gray-400'"></i>
                            <input
                                :type="showPw ? 'text' : 'password'"
                                id="password" name="password" required placeholder="Password" autocomplete="current-password"
                                x-ref="passwordInput" x-model="password"
                                @input.debounce.500ms="touched.password = true" @blur="touched.password = true"
                                :aria-invalid="passwordInvalid.toString()" aria-describedby="password-error"
                                class="w-full pl-11 pr-11 py-3 rounded-lg border text-gray-800 text-base placeholder:text-gray-400
                                       focus:outline-none focus:ring-2"
                                :class="passwordInvalid
                                    ? 'border-red-400 bg-red-50/40 focus:ring-red-500/20 focus:border-red-500'
                                    : 'border-gray-200 bg-white focus:ring-brand/20 focus:border-brand'"
                            />
                            <button type="button" @click="showPw = !showPw" :aria-label="showPw ? 'Hide password' : 'Show password'"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <span x-show="!showPw"><i data-lucide="eye" class="w-5 h-5"></i></span>
                                <span x-show="showPw" x-cloak><i data-lucide="eye-off" class="w-5 h-5"></i></span>
                            </button>
                        </div>
                        <p id="password-error" x-show="passwordInvalid" x-cloak
                           class="mt-2 flex items-start gap-1.5 text-sm font-medium text-red-600">
                            <i data-lucide="circle-alert" class="w-4 h-4 mt-0.5 shrink-0"></i>
                            <span x-text="passwordError"></span>
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="loggingIn"
                        class="w-full inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark disabled:opacity-80 disabled:cursor-wait text-white text-base font-semibold py-3 rounded-lg mt-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40"
                        >
                        <span x-show="!loggingIn">Login</span>
                        <span x-show="loggingIn" x-cloak class="inline-flex items-center gap-2" role="status">
                            @include('partials.spinner') Signing in
                        </span>
                    </button>
                </form>

    <x-slot:after>
    <!-- OTP MODAL (design/screenshots/otpmodal.JPG) -->
    <div x-show="otpOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 overflow-y-auto"
         role="dialog" aria-modal="true" aria-labelledby="otp-title" aria-describedby="otp-intro">
        <div class="relative w-full max-w-[35rem] bg-white rounded-2xl shadow-2xl p-6 sm:p-10 my-auto">
            {{-- Closing cancels the pending login (and its code), back to the sign-in form. --}}
            <button type="button" @click="cancel()" aria-label="Close and sign in again"
                    class="absolute top-4 right-4 sm:top-6 sm:right-6 p-1.5 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <h3 id="otp-title" class="font-display font-bold text-xl sm:text-2xl text-brand pr-10">OTP Verification</h3>
            <p id="otp-intro" class="mt-3 text-sm sm:text-base text-gray-500 leading-relaxed">
                Enter the 6-digit code sent to <strong class="font-semibold text-gray-700 break-words" x-text="email.trim()"></strong>.
            </p>

            <p class="mt-8 sm:mt-10 text-center text-sm text-gray-500">Step 2 of 2: Verify your account</p>

            <div class="mt-6 sm:mt-8 grid grid-cols-6 gap-2 sm:gap-3 max-w-[27rem] mx-auto" x-ref="otpInputs">
                <template x-for="(digit, i) in code" :key="i">
                    <input
                        type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1"
                        x-model="code[i]"
                        @input="onDigitInput(i, $event)"
                        @keydown="onDigitKeydown(i, $event)"
                        :aria-label="`Digit ${i + 1} of 6`"
                        class="w-full min-w-0 h-14 sm:h-16 text-center text-xl sm:text-2xl font-semibold rounded-xl border border-gray-300
                               text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
                    />
                </template>
            </div>

            <p x-show="otpError" x-cloak x-text="otpError" class="mt-4 text-sm text-red-600 text-center" role="alert"></p>

            {{-- Counting down: when Resend unlocks. At zero: the code is dead, so say so (announced once). --}}
            <p x-show="!codeExpired" class="mt-8 text-center text-sm text-gray-500">
                You can resend OTP in <strong class="font-semibold text-gray-700 tabular-nums" x-text="countdown"></strong>
            </p>
            <p x-show="codeExpired" x-cloak class="mt-8 text-center text-sm text-gray-500" role="status">
                Your code has expired. Request a new one below.
            </p>

            <button type="button" @click="verify()" :disabled="verifying || !canVerify"
                    :class="verifying ? 'cursor-wait opacity-80' : (canVerify ? '' : 'cursor-not-allowed opacity-50')"
                    class="mt-6 w-full inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark disabled:hover:bg-brand text-white text-base font-semibold py-3.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40">
                <span x-show="!verifying">Verify OTP</span>
                <span x-show="verifying" x-cloak class="inline-flex items-center gap-2" role="status">
                    @include('partials.spinner') Verifying
                </span>
            </button>

            {{-- Locked (gray) while the code is still valid; brand blue once it has expired. --}}
            <button type="button" @click="resend()" :disabled="!codeExpired || resending"
                    :class="resending
                        ? 'border-brand text-brand cursor-wait'
                        : (codeExpired ? 'border-brand text-brand hover:bg-brand/5' : 'border-gray-200 text-gray-400 cursor-not-allowed')"
                    :aria-label="codeExpired ? 'Resend OTP' : `Resend OTP, available in ${countdown}`"
                    class="mt-3 w-full flex items-center justify-between gap-3 bg-white border text-base font-medium px-5 py-3.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40">
                <span x-show="!resending">Resend OTP</span>
                <span x-show="resending" x-cloak class="inline-flex items-center gap-2" role="status">
                    @include('partials.spinner', ['tone' => 'brand']) Sending
                </span>
                <span x-show="!codeExpired" class="text-sm text-gray-400 tabular-nums" x-text="countdown" aria-hidden="true"></span>
                <span x-show="codeExpired && !resending" x-cloak aria-hidden="true"><i data-lucide="rotate-cw" class="w-4 h-4"></i></span>
            </button>

            {{-- Always takes up its line, so the card doesn't jump when the message appears. --}}
            <p x-text="resendMessage" class="mt-8 min-h-[1.25rem] text-center text-sm text-gray-500" role="status" aria-live="polite"></p>
        </div>
    </div>
    </x-slot:after>
</x-auth-shell>

<script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
@endsection
