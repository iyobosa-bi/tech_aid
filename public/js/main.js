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

        init() {
            this.routes = {
                verify: this.$el.dataset.verifyUrl,
                resend: this.$el.dataset.resendUrl,
                cancel: this.$el.dataset.cancelUrl,
            };

            setTimeout(() => this.revealed = true, 100);
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

                    return;
                }

                this.otpOpen = true;
                this.code = ['', '', '', '', '', ''];
                this.loginError = '';

                this.$nextTick(() => {
                    this.focusDigit(0);
                });

            } catch (e) {
                this.loginError = 'Something went wrong. Please try again.';
            } finally {
                this.loggingIn = false;
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
                if (this.$refs.passwordInput) {
                    this.$refs.passwordInput.value = '';
                }
            }
        },
    };
}
