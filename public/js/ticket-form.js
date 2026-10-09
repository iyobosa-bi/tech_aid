// The raise / edit & resubmit form (resources/views/tickets/form.blade.php).
// The file dropzone itself is set up by ticket-uploads.js (shared with the resolve modal).

function ticketForm(config) {
    return {
        title: config.old.title ?? '',
        description: config.old.description ?? '',
        priority: config.old.priority ?? '',
        pendingUploads: 0,
        failedUploads: 0,
        submitting: false,
        submitBlockedReason: '',
        pond: null,

        init() {
            // No file input when an edited ticket already has the maximum number of files.
            if (!this.$refs.attachments) return;

            this.pond = createTicketPond(this.$refs.attachments, {
                maxFiles: config.maxFiles,
                maxFileSize: config.maxFileSize,
                files: config.existingUploads,
                urls: config.urls,
                onChange: () => this.syncUploadState(),
                onWarning: (warning) => {
                    if (warning?.body === 'Max files') {
                        this.submitBlockedReason = `You can attach at most ${config.maxFiles} files.`;
                    }
                },
            });
        },

        syncUploadState() {
            if (!this.pond) return;

            const state = ticketPondState(this.pond);
            this.pendingUploads = state.pending;
            this.failedUploads = state.failed;

            if (!this.pendingUploads && !this.failedUploads) this.submitBlockedReason = '';
        },

        submit(event) {
            this.syncUploadState();

            if (this.pendingUploads) {
                event.preventDefault();
                this.submitBlockedReason = 'Please wait for your attachments to finish uploading.';
                return;
            }

            if (this.failedUploads) {
                event.preventDefault();
                this.submitBlockedReason = 'Remove the attachments that failed before submitting.';
                return;
            }

            this.submitting = true;
        },
    };
}
