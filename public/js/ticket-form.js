const TICKET_MIME_BY_EXTENSION = {
    jpg: 'image/jpeg',
    jpeg: 'image/jpeg',
    png: 'image/png',
    pdf: 'application/pdf',
    doc: 'application/msword',
    docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
};

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
            FilePond.registerPlugin(FilePondPluginFileValidateType, FilePondPluginFileValidateSize);

            const headers = {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            };

            this.pond = FilePond.create(this.$refs.attachments, {
                credits: false,
                // FilePond does NOT inherit the underlying input's name="attachments[]" —
                // it defaults to the fixed field name "filepond" for both the async
                // upload request and the hidden inputs it adds on confirmed upload.
                // This must be set explicitly (brackets included) to match what
                // TicketUploadController and StoreTicketRequest both validate.
                name: 'attachments[]',
                allowMultiple: true,
                maxFiles: config.maxFiles,
                maxFileSize: config.maxFileSize,
                acceptedFileTypes: Object.values(TICKET_MIME_BY_EXTENSION),
                // Some browsers/OSes report an empty MIME type for Word files; fall back to the extension.
                fileValidateTypeDetectType: (file, type) => Promise.resolve(
                    type || TICKET_MIME_BY_EXTENSION[file.name.split('.').pop().toLowerCase()] || ''
                ),
                files: config.existingUploads,
                server: {
                    process: {
                        url: config.urls.process,
                        headers,
                        onerror: (response) => this.errorMessage(response),
                    },
                    revert: { url: config.urls.revert, headers },
                },
                labelIdle: '<span class="filepond--label-action">Click to upload</span> or drag and drop files here',
                labelFileProcessing: 'Uploading',
                labelFileProcessingComplete: 'Upload complete',
                labelFileProcessingError: (error) => error?.body || 'Upload failed',
                labelTapToRetry: 'tap to retry',
                labelTapToCancel: 'tap to cancel',
                labelTapToUndo: 'tap to remove',
                labelMaxFileSizeExceeded: 'File is too large',
                labelMaxFileSize: 'Maximum file size is {filesize}',
                labelFileTypeNotAllowed: 'This file type is not allowed',
                fileValidateTypeLabelExpectedTypes: 'Use JPG, PNG, PDF, DOC or DOCX',
                labelMaxTotalFileSizeExceeded: 'Too many files',
            });

            ['addfile', 'processfilestart', 'processfile', 'processfileabort', 'processfilerevert', 'removefile', 'updatefiles', 'warning']
                .forEach((event) => this.pond.on(event, () => this.syncUploadState()));

            this.pond.on('warning', (warning) => {
                if (warning?.body === 'Max files') {
                    this.submitBlockedReason = `You can attach at most ${config.maxFiles} files.`;
                }
            });
        },

        syncUploadState() {
            const S = FilePond.FileStatus;
            const files = this.pond.getFiles();

            this.pendingUploads = files.filter((f) => [S.INIT, S.IDLE, S.LOADING, S.PROCESSING_QUEUED, S.PROCESSING].includes(f.status)).length;
            this.failedUploads = files.filter((f) => [S.LOAD_ERROR, S.PROCESSING_ERROR].includes(f.status)).length;

            if (!this.pendingUploads && !this.failedUploads) this.submitBlockedReason = '';
        },

        errorMessage(response) {
            try {
                return JSON.parse(response).message || 'Upload failed';
            } catch (e) {
                return 'Upload failed';
            }
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
