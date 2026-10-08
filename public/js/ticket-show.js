// Interactive parts of the ticket page (resources/views/tickets/show.blade.php).
// Every action is still a normal form POST; these only manage the modals and inputs.

// Line manager's decision. Approve = an optional comment and one confirmation step.
// Decline = a required comment first, then a confirmation that shows the comment
// before anything is sent.
function ticketDecision(config) {
    return {
        modal: null, // 'approve' | 'decline' | null
        step: 'comment', // decline: 'comment' → 'confirm'
        comment: config.comment ?? '',
        touched: false,
        serverError: config.error ?? '',
        approveComment: config.approveComment ?? '',
        approveServerError: config.approveError ?? '',
        submitting: false,

        init() {
            // The server rejected a comment (e.g. the page's checks were bypassed): reopen with its message.
            if (config.reopenDecline) this.openDecline();
            if (config.reopenApprove) this.openApprove();
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
            this.$nextTick(() => this.$refs.approveComment.focus());
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

// Head of Service Management's actions (Flows 5–6). "pick" = Assign / Reassign: choosing a
// person and pressing "Assign to {name}" is the confirmation. "resolve" = Resolve directly:
// required notes first, then a confirmation that quotes them before anything is sent.
function ticketAssignment(config) {
    return {
        modal: null, // 'pick' | 'resolve' | null
        step: 'notes', // resolve: 'notes' → 'confirm'
        selected: config.selected ?? null,
        verb: config.verb,
        note: config.note ?? '',
        pickError: config.pickError ?? '',
        notes: config.notes ?? '',
        notesTouched: false,
        notesServerError: config.notesError ?? '',
        submitting: false,

        init() {
            // The server rejected the input (e.g. the person went on leave meanwhile): reopen with its message.
            if (config.reopen === 'pick') this.openPick();
            if (config.reopen === 'resolve') this.openResolve();
        },

        get selectedName() {
            return this.selected ? (config.names[this.selected] ?? '') : '';
        },

        get notesError() {
            const length = this.notes.trim().length;

            if (!length) return 'Please describe how the issue was resolved.';
            if (length < config.notesMin) return `Please give a little more detail (at least ${config.notesMin} characters).`;
            if (this.notes.length > config.notesMax) return `Please keep it under ${config.notesMax} characters.`;

            return '';
        },

        get shownNotesError() {
            return this.notesServerError || (this.notesTouched ? this.notesError : '');
        },

        openPick() {
            this.modal = 'pick';
            this.$nextTick(() => lucide.createIcons());
        },

        openResolve() {
            this.modal = 'resolve';
            this.step = 'notes';
            this.$nextTick(() => this.$refs.resolutionNotes?.focus());
        },

        toConfirm() {
            this.notesTouched = true;

            if (this.notesError) {
                this.$refs.resolutionNotes.focus();
                return;
            }

            this.step = 'confirm';
            this.$nextTick(() => this.$refs.resolveConfirm.focus());
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
