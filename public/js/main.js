function loginPage() {
    return {
        otpOpen: false,
        code: ['', '', '', '', '', ''],
        showPw: false,
        revealed: false,
        verifying: false,
        resending: false,
        otpError: '',
        resendMessage: '',
        loggingIn: false,
        loginError: '',
        routes: {},

        // Live validation. A field's error shows once it's "touched" — after a short pause
        // in typing, on leaving the field, or on submit — then updates on every keystroke.
        email: '',
        password: '',
        touched: { email: false, password: false },
        emailDomain: '',
        minPasswordLength: 8,

        init() {
            this.routes = {
                verify: this.$el.dataset.verifyUrl,
                resend: this.$el.dataset.resendUrl,
                cancel: this.$el.dataset.cancelUrl,
            };
            this.emailDomain = (this.$el.dataset.emailDomain || '').toLowerCase();
            this.email = this.$el.dataset.oldEmail || '';

            setTimeout(() => this.revealed = true, 100);
        },

        get emailError() {
            const value = this.email.trim();

            if (!value) return 'Email is required.';
            if (this.emailDomain && !value.toLowerCase().endsWith('@' + this.emailDomain)) {
                return `Email must end with @${this.emailDomain}.`;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                return `Enter a valid email address, e.g. name@${this.emailDomain || 'example.com'}.`;
            }

            return '';
        },

        get passwordError() {
            if (!this.password) return 'Password is required.';
            if (this.password.length < this.minPasswordLength) {
                return `Password must be at least ${this.minPasswordLength} characters.`;
            }

            return '';
        },

        get emailInvalid() {
            return this.touched.email && this.emailError !== '';
        },

        get passwordInvalid() {
            return this.touched.password && this.passwordError !== '';
        },

        // Runs on submit: shows every error at once and focuses the first bad field.
        validate() {
            this.touched.email = true;
            this.touched.password = true;

            if (this.emailError) this.$refs.emailInput.focus();
            else if (this.passwordError) this.$refs.passwordInput.focus();

            return !this.emailError && !this.passwordError;
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        focusDigit(i) {
            const inputs = this.$refs.otpInputs.querySelectorAll('input');
            if (inputs[i]) {
                inputs[i].focus();
            }
        },

        // Opens the OTP modal and moves the cursor into its first box. The password field
        // loses focus straight away. The page behind is made inert (x-effect in login.blade.php),
        // so it can't be clicked or tabbed back into.
        openOtp() {
            document.activeElement?.blur();
            this.code = ['', '', '', '', '', ''];
            this.otpOpen = true;
            this.whenVisible(this.$refs.otpInputs, () => this.focusDigit(0));
        },

        // x-show reveals an element one animation frame *after* the state changes, and
        // focus() on a still-hidden input is silently ignored, so wait until it's on screen.
        whenVisible(el, callback, framesLeft = 10) {
            requestAnimationFrame(() => {
                if (el.offsetParent !== null || framesLeft === 0) callback();
                else this.whenVisible(el, callback, framesLeft - 1);
            });
        },

        onDigitInput(i, event) {
            const digit = event.target.value.replace(/\D/g, '').slice(-1);
            this.code[i] = digit;
            if (digit && i < this.code.length - 1) {
                this.focusDigit(i + 1);
            }
        },

        onDigitKeydown(i, event) {
            if (event.key === 'Backspace' && !this.code[i] && i > 0) {
                this.focusDigit(i - 1);
            }
        },

        async login() {
            this.loginError = '';
            this.otpError = '';
            this.resendMessage = '';

            // Don't spend a server round trip (or a rate-limited attempt) on input that can't succeed.
            if (!this.validate()) return;

            this.loggingIn = true;

            try {
                const form = this.$root.querySelector('form');
                const formData = new FormData(form);

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {
                    this.loginError =
                        data.message ||
                        data.errors?.email?.[0] ||
                        'Invalid email or password.';

                    // The form never reloads (fetch), so clear the rejected password ourselves.
                    // The email stays filled in so the user only retypes the password.
                    this.clearPassword();

                    return;
                }

                this.logDebugCode(data);
                this.loginError = '';
                this.openOtp();
            } catch (e) {
                this.loginError = 'Something went wrong. Please try again.';
            } finally {
                this.loggingIn = false;
            }
        },
    

        // Untouches the field too, so "Password is required" doesn't pile on top of the server's message.
        clearPassword() {
            this.showPw = false;
            this.password = '';
            this.touched.password = false;
            this.$refs.passwordInput?.focus();
        },

        // The server only includes debug_code when APP_ENV=local and APP_DEBUG=true.
        logDebugCode(data) {
            if (data.debug_code) {
                console.info(`%c[Tech Aid dev] OTP code: ${data.debug_code}`, 'color:#152a9e;font-weight:bold;font-size:14px');
            }
        },

        async verify() {
            this.otpError = '';
            this.resendMessage = '';
            this.verifying = true;

            try {
                const response = await fetch(this.routes.verify, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ code: this.code.join('') }),
                });
                const data = await response.json();

                if (!response.ok) {
                    this.otpError = data.message || 'Invalid or expired code.';
                    this.code = ['', '', '', '', '', ''];
                    this.focusDigit(0);
                    return;
                }

                window.location.href = data.redirect;
            } catch (e) {
                this.otpError = 'Something went wrong. Please try again.';
            } finally {
                this.verifying = false;
            }
        },

        async resend() {
            this.otpError = '';
            this.resendMessage = '';
            this.resending = true;

            try {
                const response = await fetch(this.routes.resend, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });
                const data = await response.json();

                if (!response.ok) {
                    this.otpError = data.message || 'Could not resend the code.';
                    return;
                }

                this.logDebugCode(data);
                this.resendMessage = data.message || 'A new code has been sent.';
            } catch (e) {
                this.otpError = 'Something went wrong. Please try again.';
            } finally {
                this.resending = false;
            }
        },

        async cancel() {
            try {
                await fetch(this.routes.cancel, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                });
            } finally {
                this.otpOpen = false;
                this.code = ['', '', '', '', '', ''];
                this.otpError = '';
                this.resendMessage = '';
                this.password = '';
                this.touched.password = false;
                // Back on the form, ready for the other account's password.
                this.whenVisible(this.$refs.passwordInput, () => this.$refs.passwordInput.focus());
            }
        },
    };
}
