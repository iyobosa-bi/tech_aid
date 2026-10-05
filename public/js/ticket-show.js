// Interactive parts of the ticket page (resources/views/tickets/show.blade.php).
// Every action is still a normal form POST; these only manage the modals and inputs.

// Line manager's decision. Approve = one confirmation step. Decline = a required
// comment first, then a confirmation that shows the comment before anything is sent.
function ticketDecision(config) {
    return {
        modal: null, // 'approve' | 'decline' | null
        step: 'comment', // decline: 'comment' → 'confirm'
        comment: config.comment ?? '',
        touched: false,
        serverError: config.error ?? '',
        submitting: false,

        init() {
            // The server rejected the comment (e.g. the page's checks were bypassed): reopen with its message.
            if (config.reopenDecline) this.openDecline();
        },

        get commentError() {
            const length = this.comment.trim().length;

            if (!length) return 'Please explain why you are declining this ticket.';
            if (length < config.minLength) return `Please give a little more detail (at least ${config.minLength} characters).`;
            if (this.comment.length > config.maxLength) return `Please keep it under ${config.maxLength} characters.`;

            return '';
        },

        get shownError() {
            return this.serverError || (this.touched ? this.commentError : '');
        },

        openApprove() {
            this.modal = 'approve';
            this.$nextTick(() => this.$refs.approveConfirm.focus());
        },

        openDecline() {
            this.modal = 'decline';
            this.step = 'comment';
            this.$nextTick(() => this.$refs.declineComment.focus());
        },

        toConfirm() {
            this.touched = true;

            if (this.commentError) {
                this.$refs.declineComment.focus();
                return;
            }

            this.step = 'confirm';
            this.$nextTick(() => this.$refs.declineConfirm.focus());
        },

        close() {
            if (!this.submitting) this.modal = null;
        },

        // form.submit() skips the @submit handler, so this is the only path that actually sends.
        submit(formRef) {
            this.submitting = true;
            this.$refs[formRef].submit();
        },
    };
}

// Quick view for images and PDFs. The <img>/<iframe> is created only while open, so a
// closed preview isn't still loading the file in the background.
function attachmentPreview() {
    const empty = { kind: '', name: '', url: '', downloadUrl: '' };

    return {
        open: false,
        loading: false,
        file: { ...empty },

        show(file) {
            this.file = file;
            this.loading = true;
            this.open = true;
        },

        close() {
            this.open = false;
            setTimeout(() => (this.file = { ...empty }), 200); // after the fade-out
        },
    };
}

// The conversation's reply box.
function ticketReply(config) {
    return {
        body: config.body ?? '',
        sending: false,

        get remaining() {
            return config.max - this.body.length;
        },

        send(event) {
            if (!this.body.trim()) {
                event.preventDefault();
                return;
            }

            this.sending = true;
        },

        // Ctrl/Cmd + Enter sends, like most chat tools; plain Enter adds a new line.
        keydown(event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                this.$root.requestSubmit(); // the component's root is the reply <form>
            }
        },
    };
}
