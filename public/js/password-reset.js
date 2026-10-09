// Forgot password pages (resources/views/auth/reset-code.blade.php and reset-password.blade.php).
// Both are plain form POSTs; these only drive the code boxes, the countdown and the rules checklist.

// Step 2: six code boxes (type, backspace, or paste the whole code) and the resend countdown.
function resetCode(config) {
    return {
        code: ['', '', '', '', '', ''],
        secondsLeft: Math.max(0, config.secondsLeft ?? 0),
        endsAt: 0,
        timer: null,
        verifying: false,
        resending: false,

        init() {
            // Local debug mode only (APP_ENV=local, APP_DEBUG=true): the server hands the code over for testing.
            if (config.debugCode) {
                console.info(`%c[Tech Aid dev] Password reset code: ${config.debugCode}`, 'color:#152a9e;font-weight:bold;font-size:14px');
            }

            // Counted from a fixed end time, so a slowed-down background tab still shows the truth.
            this.endsAt = Date.now() + this.secondsLeft * 1000;
            if (this.secondsLeft > 0) this.timer = setInterval(() => this.tick(), 1000);

            this.$nextTick(() => this.focusBox(0));
        },

        tick() {
            this.secondsLeft = Math.max(0, Math.ceil((this.endsAt - Date.now()) / 1000));
            if (this.secondsLeft === 0) clearInterval(this.timer);
        },

        get countdown() {
            const minutes = Math.floor(this.secondsLeft / 60);
            const seconds = this.secondsLeft % 60;
            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },

        focusBox(i) {
            this.$refs.boxes.querySelectorAll('input')[i]?.focus();
        },

        onInput(i, event) {
            const digit = event.target.value.replace(/\D/g, '').slice(-1);
            this.code[i] = digit;
            if (digit && i < this.code.length - 1) this.focusBox(i + 1);
        },

        onKeydown(i, event) {
            if (event.key === 'Backspace' && !this.code[i] && i > 0) this.focusBox(i - 1);
        },

        onPaste(event) {
            const digits = (event.clipboardData?.getData('text') ?? '').replace(/\D/g, '').slice(0, 6).split('');
            if (!digits.length) return;

            this.code = [...digits, '', '', '', '', '', ''].slice(0, 6);
            this.focusBox(Math.min(digits.length, 5));
        },
    };
}

// Step 3: the new password, with a checklist matching Password::defaults() (AppServiceProvider).
function newPassword() {
    return {
        password: '',
        confirmation: '',
        show: false,
        saving: false,

        get rules() {
            const p = this.password;

            return [
                { label: 'At least 8 characters', met: p.length >= 8 },
                { label: 'Upper and lower case letters', met: /[a-z]/.test(p) && /[A-Z]/.test(p) },
                { label: 'A number', met: /\d/.test(p) },
                { label: 'A symbol, e.g. @ # !', met: /[^A-Za-z0-9]/.test(p) },
                { label: 'Both passwords match', met: p !== '' && p === this.confirmation },
            ];
        },
    };
}
