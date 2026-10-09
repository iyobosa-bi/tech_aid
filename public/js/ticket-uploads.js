// FilePond set-up shared by the ticket form (ticket-form.js) and the resolve modal (ticket-show.js).
// The rules match TicketUploadController: JPG, PNG, PDF, DOC, DOCX, at most 10MB each.
// Load after the FilePond scripts and before the page's own script.

const TICKET_MIME_BY_EXTENSION = {
    jpg: 'image/jpeg',
    jpeg: 'image/jpeg',
    png: 'image/png',
    pdf: 'application/pdf',
    doc: 'application/msword',
    docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
};

FilePond.registerPlugin(FilePondPluginFileValidateType, FilePondPluginFileValidateSize);

/**
 * @param {HTMLInputElement} input
 * @param {{maxFiles: number, maxFileSize?: string, files?: Array, urls: {process: string, revert: string},
 *          onChange: Function, onWarning?: Function}} options
 *   onChange runs whenever a file is added, uploads, fails or is removed.
 */
function createTicketPond(input, options) {
    const headers = {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
    };

    const pond = FilePond.create(input, {
        credits: false,
        // FilePond does NOT inherit the underlying input's name="attachments[]" —
        // it defaults to the fixed field name "filepond" for both the async
        // upload request and the hidden inputs it adds on confirmed upload.
        // This must be set explicitly (brackets included) to match what
        // TicketUploadController and the form requests validate.
        name: 'attachments[]',
        allowMultiple: true,
        maxFiles: options.maxFiles,
        maxFileSize: options.maxFileSize ?? '10MB',
        acceptedFileTypes: Object.values(TICKET_MIME_BY_EXTENSION),
        // Some browsers/OSes report an empty MIME type for Word files; fall back to the extension.
        fileValidateTypeDetectType: (file, type) => Promise.resolve(
            type || TICKET_MIME_BY_EXTENSION[file.name.split('.').pop().toLowerCase()] || ''
        ),
        files: options.files ?? [],
        server: {
            process: {
                url: options.urls.process,
                headers,
                onerror: (response) => ticketUploadErrorMessage(response),
            },
            revert: { url: options.urls.revert, headers },
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
        .forEach((event) => pond.on(event, () => options.onChange(pond)));

    if (options.onWarning) pond.on('warning', options.onWarning);

    return pond;
}

// How many files are still uploading, and how many failed — the form can't be sent until both are 0.
function ticketPondState(pond) {
    const S = FilePond.FileStatus;
    const files = pond.getFiles();

    return {
        pending: files.filter((f) => [S.INIT, S.IDLE, S.LOADING, S.PROCESSING_QUEUED, S.PROCESSING].includes(f.status)).length,
        failed: files.filter((f) => [S.LOAD_ERROR, S.PROCESSING_ERROR].includes(f.status)).length,
        names: files.filter((f) => f.status === S.PROCESSING_COMPLETE).map((f) => f.filename),
    };
}

function ticketUploadErrorMessage(response) {
    try {
        return JSON.parse(response).message || 'Upload failed';
    } catch (e) {
        return 'Upload failed';
    }
}
