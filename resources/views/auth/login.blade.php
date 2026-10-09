@extends('layouts.guest', ['title' => 'Login — Tech Aid'])

@section('content')

<div x-data="loginPage()"
     data-verify-url="{{ route('login.otp.verify') }}"
     data-resend-url="{{ route('login.otp.resend') }}"
     data-cancel-url="{{ route('login.otp.cancel') }}"
     data-email-domain="{{ config('app.staff_email_domain') }}"
     data-old-email="{{ old('email') }}"
     class="h-full flex"
>
    {{-- Both panels go inert while the OTP modal is open: nothing behind it can be focused, clicked or typed into. --}}
    <div
        x-show="revealed"
        x-effect="$el.inert = otpOpen"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 -translate-x-4"
        x-transition:enter-end="opacity-100 translate-x-0"
        class="hidden md:flex md:w-[33%] bg-brand flex-col items-center justify-center relative px-8"
    >
        <div class="w-432 h-4366 rounded-2xl bg-white flex items-center justify-center mb-6 shadow-xl">
            <svg viewBox="0 0 50 50" class="w-40 h-40">
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

        <h1 class="font-display font-extrabold text-white text-3xl tracking-tight flex" aria-label="Tech Aid">
            <span class="letter-anim" style="animation-delay: 0.05s">T</span>
            <span class="letter-anim" style="animation-delay: 0.10s">e</span>
            <span class="letter-anim" style="animation-delay: 0.15s">c</span>
            <span class="letter-anim" style="animation-delay: 0.20s">h</span>
            <span class="letter-anim" style="animation-delay: 0.28s">&nbsp;</span>
            <span class="letter-anim" style="animation-delay: 0.33s">A</span>
            <span class="letter-anim" style="animation-delay: 0.38s">i</span>
            <span class="letter-anim" style="animation-delay: 0.43s">d</span>
        </h1>

        <p class="font-display text-white/70 text-sm text-center mt-3 max-w-[220px] leading-relaxed">
            Internal technology support &amp; ticketing for optimusbank.com
        </p>
    </div>

    <!-- RIGHT: full-bleed photo + floating card -->
    <div class="flex-1 relative overflow-hidden bg-brand-dark" x-effect="$el.inert = otpOpen">
        <img src="{{ asset('images/login-bg.svg') }}" alt="" class="absolute inset-0 w-full h-full object-cover" />

        <!-- dark overlay, mobile only — improves contrast since blue panel is hidden -->
        <div class="absolute inset-0 bg-brand-dark/50 md:hidden"></div>

        <!-- mobile-only compact header -->
        <div class="md:hidden absolute top-6 left-0 right-0 flex flex-col items-center px-4">
            <div class="w-14 h-14 rounded-xl bg-white flex items-center justify-center mb-2 shadow-lg">
                <svg viewBox="0 0 48 48" class="w-8 h-8">
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
            <h1 class="font-display font-extrabold text-white text-xl tracking-tight flex" aria-label="Tech Aid">
                <span class="letter-anim" style="animation-delay: 0.05s">T</span>
                <span class="letter-anim" style="animation-delay: 0.10s">e</span>
                <span class="letter-anim" style="animation-delay: 0.15s">c</span>
                <span class="letter-anim" style="animation-delay: 0.20s">h</span>
                <span class="letter-anim" style="animation-delay: 0.28s">&nbsp;</span>
                <span class="letter-anim" style="animation-delay: 0.33s">A</span>
                <span class="letter-anim" style="animation-delay: 0.38s">i</span>
                <span class="letter-anim" style="animation-delay: 0.43s">d</span>
            </h1>
        </div>

        <div
        
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[92%] sm:w-full max-w-[440px] mx-auto sm:mx-4"
        >
            <div class="bg-white rounded-xl shadow-2xl p-6 sm:p-8">
                <h2 class="font-display font-bold text-brand text-lg mb-6">
                    Sign in to Tech Aid
                </h2>
                <div
                    x-show="loginError"
                    x-cloak
                    role="alert"
                    class="mb-5 flex items-start gap-2 px-3 py-2.5 border border-red-200 bg-red-50 rounded-lg"
                >
                  <i data-lucide="circle-alert" class="w-4 h-4 text-red-600 shrink-0"></i>
                  <p x-text="loginError" class="text-xs font-semibold leading-4 text-red-700"></p>
                </div>

                {{-- novalidate: our live messages replace the browser's own validation bubbles. --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate @submit.prevent="login()">
                    @csrf

                    <div>
                        <label for="email" class="block font-display font-medium text-brand text-sm mb-1.5">Email</label>
                        <div class="relative">
                            <i data-lucide="mail" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 transition-colors"
                               :class="emailInvalid ? 'text-red-500' : 'text-gray-400'"></i>
                            <input
                                id="email" name="email" type="email" required autofocus autocomplete="username"
                                placeholder="Email"
                                x-ref="emailInput" x-model="email"
                                @input.debounce.500ms="touched.email = true" @blur="touched.email = true"
                                :aria-invalid="emailInvalid.toString()" aria-describedby="email-error"
                                class="w-full pl-10 pr-3 py-2.5 rounded-lg border text-gray-800 text-sm placeholder:text-gray-400
                                       transition-colors focus:outline-none focus:ring-2"
                                :class="emailInvalid
                                    ? 'border-red-400 bg-red-50/40 focus:ring-red-500/20 focus:border-red-500'
                                    : 'border-gray-200 bg-white focus:ring-brand/20 focus:border-brand'"
                            />
                        </div>
                        <p id="email-error" x-show="emailInvalid" x-cloak x-transition.opacity.duration.150ms
                           class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                            <i data-lucide="circle-alert" class="w-3.5 h-3.5 mt-px shrink-0"></i>
                            <span x-text="emailError"></span>
                        </p>
                    </div>

                    <div>
                        <label for="password" class="block font-display font-medium text-brand text-sm mb-1.5">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 transition-colors"
                               :class="passwordInvalid ? 'text-red-500' : 'text-gray-400'"></i>
                            <input
                                :type="showPw ? 'text' : 'password'"
                                id="password" name="password" required placeholder="Password" autocomplete="current-password"
                                x-ref="passwordInput" x-model="password"
                                @input.debounce.500ms="touched.password = true" @blur="touched.password = true"
                                :aria-invalid="passwordInvalid.toString()" aria-describedby="password-error"
                                class="w-full pl-10 pr-10 py-2.5 rounded-lg border text-gray-800 text-sm placeholder:text-gray-400
                                       transition-colors focus:outline-none focus:ring-2"
                                :class="passwordInvalid
                                    ? 'border-red-400 bg-red-50/40 focus:ring-red-500/20 focus:border-red-500'
                                    : 'border-gray-200 bg-white focus:ring-brand/20 focus:border-brand'"
                            />
                            <button type="button" @click="showPw = !showPw" :aria-label="showPw ? 'Hide password' : 'Show password'"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4" x-show="!showPw"></i>
                                <i data-lucide="eye-off" class="w-4 h-4" x-show="showPw"></i>
                            </button>
                        </div>
                        <p id="password-error" x-show="passwordInvalid" x-cloak x-transition.opacity.duration.150ms
                           class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                            <i data-lucide="circle-alert" class="w-3.5 h-3.5 mt-px shrink-0"></i>
                            <span x-text="passwordError"></span>
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="loggingIn"
                        class="w-full inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark disabled:opacity-80 disabled:cursor-wait text-white text-sm font-semibold py-2.5 rounded-lg transition-colors mt-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40"
                        >
                        <span x-show="!loggingIn">Login</span>
                        <span x-show="loggingIn" x-cloak class="inline-flex items-center gap-2" role="status">
                            @include('partials.spinner') Signing in
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- OTP MODAL (design/screenshots/otpmodal.JPG) -->
    <div x-show="otpOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 overflow-y-auto"
         role="dialog" aria-modal="true" aria-labelledby="otp-title" aria-describedby="otp-intro">
        <div class="relative w-full max-w-[35rem] bg-white rounded-2xl shadow-2xl p-6 sm:p-10 my-auto">
            {{-- Closing cancels the pending login (and its code), back to the sign-in form. --}}
            <button type="button" @click="cancel()" aria-label="Close and sign in again"
                    class="absolute top-4 right-4 sm:top-6 sm:right-6 p-1.5 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40">
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
                    class="mt-6 w-full inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark disabled:hover:bg-brand text-white text-base font-semibold py-3.5 rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40">
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
                    class="mt-3 w-full flex items-center justify-between gap-3 bg-white border text-base font-medium px-5 py-3.5 rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40">
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
</div>

<script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
@endsection
