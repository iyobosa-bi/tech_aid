@extends('layouts.guest', ['title' => 'Login — Tech Aid'])

@section('content')

<div x-data="loginPage()"
     data-verify-url="{{ route('login.otp.verify') }}"
     data-resend-url="{{ route('login.otp.resend') }}"
     data-cancel-url="{{ route('login.otp.cancel') }}"
     class="h-full flex"
>
    <div
        x-show="revealed"
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
    <div class="flex-1 relative overflow-hidden bg-brand-dark">
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
            x-show="revealed"
            x-transition:enter="transition ease-out duration-500 delay-150"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[92%] sm:w-full max-w-[440px] mx-auto sm:mx-4"
        >
            <div class="bg-white rounded-xl shadow-2xl p-6 sm:p-8">
                <h2 class="font-display font-bold text-brand text-lg mb-6">
                    Sign in to Tech Aid
                </h2>

                <div
                    x-show="loginError"
                    x-cloak
                    class="mb-5 px-3 py-2.5 border border-red-200 bg-red-50 rounded-lg"
                >
                  <p x-text="loginError" class="text-xs text-red-600"></p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-5" @submit.prevent="login()">
                    @csrf

                    <div>
                        <label for="email" class="block font-display font-medium text-brand text-sm mb-1.5">Email</label>
                        <div class="relative">
                            <i data-lucide="mail" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input
                                id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                                placeholder="Email"
                                class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-gray-200 bg-white
                                       text-gray-800 text-sm placeholder:text-gray-400
                                       focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block font-display font-medium text-brand text-sm mb-1.5">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input
                                :type="showPw ? 'text' : 'password'"
                                x-ref="passwordInput"
                                id="password" name="password" required placeholder="Password"
                                class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-200 bg-white
                                       text-gray-800 text-sm placeholder:text-gray-400
                                       focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
                            />
                            <button type="button" @click="showPw = !showPw"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4" x-show="!showPw"></i>
                                <i data-lucide="eye-off" class="w-4 h-4" x-show="showPw"></i>
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="loggingIn"
                        class="w-full bg-gray-400 hover:bg-gray-500 disabled:opacity-60 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors mt-2"
                        >
                        <span x-show="!loggingIn">Login</span>
                        <span x-show="loggingIn" x-cloak>Signing in…</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- OTP MODAL -->
    <div x-show="otpOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-sm bg-white rounded-xl shadow-2xl p-6">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-display font-bold text-lg text-brand">Verify your identity</h3>
            </div>
            <p class="text-sm text-gray-500 mb-6">Enter the 6-digit code sent to your email.</p>

            <div class="flex gap-2 justify-between mb-4" x-ref="otpInputs">
                <template x-for="(digit, i) in code" :key="i">
                    <input
                        type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1"
                        x-model="code[i]"
                        @input="onDigitInput(i, $event)"
                        @keydown="onDigitKeydown(i, $event)"
                        class="w-11 h-12 text-center text-lg font-semibold rounded-lg border border-gray-200
                               text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
                    />
                </template>
            </div>

            <p x-show="otpError" x-cloak x-text="otpError" class="text-xs text-red-600 mb-4 text-center"></p>
            <p x-show="resendMessage" x-cloak x-text="resendMessage" class="text-xs text-teal-600 mb-4 text-center"></p>

            <button type="button" @click="verify()" :disabled="verifying"
                    class="w-full bg-gray-400 hover:bg-gray-500 disabled:opacity-60 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors mb-3">
                <span x-show="!verifying">Verify</span>
                <span x-show="verifying" x-cloak>Verifying…</span>
            </button>

            <p class="text-xs text-center text-gray-400">
                Didn't get a code?
                <button type="button" @click="resend()" :disabled="resending" class="text-blue-600 font-medium disabled:opacity-60">
                    <span x-show="!resending">Resend</span>
                    <span x-show="resending" x-cloak>Sending…</span>
                </button>
            </p>

            <p class="text-xs text-center text-gray-300 mt-3">
                <button type="button" @click="cancel()" class="underline hover:text-gray-500">Use a different account</button>
            </p>
        </div>
    </div>
</div>

<script src="{{ asset('js/main.js') }}"></script>
@endsection
