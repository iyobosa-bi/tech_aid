{{--
    The Resolve button and its modal, shared by Head of Service Management's "Resolve directly"
    (Flow 5, from detail-assignment) and the assigned support person's "Resolve" (Flow 7, from
    detail-work). Step 1: required resolution notes plus optional files (FilePond, uploaded
    ahead through tickets.resolution-uploads.*). Step 2: a confirmation quoting the notes and
    listing the files. The form is a plain POST; ticket-show.js (ticketResolution) drives the modal.

    @param string $label        button text, e.g. "Resolve" or "Resolve directly"
    @param string $buttonClass  button colours
    @param string $title        modal heading
    @param string $intro        what resolving means here
    @param string $confirmNote  the line under the confirmation heading
--}}
@use('App\Http\Requests\ResolveTicketRequest')
@php
    $btn = 'inline-flex items-center justify-center gap-2 rounded-lg font-display font-semibold text-sm px-4 py-2.5 transition-colors disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40';
    $failed = $errors->resolve->any();
@endphp

<div class="flex flex-col"
     x-data="ticketResolution(@js([
        'reopen' => $failed,
        'notes' => $failed ? old('resolution_notes', '') : '',
        'notesError' => $errors->resolve->first('resolution_notes'),
        'filesError' => $errors->resolve->first('attachments') ?: $errors->resolve->first('attachments.*'),
        'notesMin' => ResolveTicketRequest::NOTES_MIN,
        'notesMax' => ResolveTicketRequest::NOTES_MAX,
        'maxFiles' => ResolveTicketRequest::MAX_FILES,
        'existingUploads' => $resolutionUploads,
        'urls' => [
            'process' => route('tickets.resolution-uploads.store', $ticket),
            'revert' => route('tickets.resolution-uploads.destroy', $ticket),
        ],
     ]))">

    <button type="button" @click="openModal()" class="{{ $btn }} {{ $buttonClass }}">
        <i data-lucide="circle-check" class="w-4 h-4"></i> {{ $label }}
    </button>

    <div x-show="open" x-cloak @keydown.escape.window="open && close()"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="resolve-title">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-gray-900/50" @click="close()"></div>

        <form x-show="open" x-ref="form" method="POST" action="{{ route('tickets.resolve', $ticket) }}"
              @submit.prevent="toConfirm()" novalidate
              x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95"
              class="relative w-full max-w-xl max-h-[90vh] overflow-y-auto bg-white rounded-xl shadow-2xl p-6">
            @csrf

            <div x-show="step === 'notes'" x-ref="notesStep">
                <div class="w-11 h-11 rounded-full bg-green-100 text-green-700 flex items-center justify-center mb-4">
                    <i data-lucide="circle-check" class="w-5 h-5"></i>
                </div>
                <h3 id="resolve-title" class="font-display font-bold text-lg text-gray-900">{{ $title }}</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">{{ $intro }}</p>

                <label for="resolution-notes" class="block text-sm font-medium text-gray-700 mt-5 mb-1.5">Resolution notes <span class="text-red-500">*</span></label>
                <textarea id="resolution-notes" name="resolution_notes" rows="4" maxlength="{{ ResolveTicketRequest::NOTES_MAX }}"
                          x-ref="notes" x-model="notes" @input="notesServerError = ''" @blur="if (notes) notesTouched = true"
                          placeholder="e.g. Reset the mail relay rule for the Operations mailbox; external emails send normally again."
                          :aria-invalid="(!!shownNotesError).toString()" aria-describedby="resolution-notes-error"
                          class="w-full px-3.5 py-2.5 rounded-lg border text-sm text-gray-900 placeholder:text-gray-400 resize-y focus:outline-none focus:ring-2"
                          :class="shownNotesError ? 'border-red-400 bg-red-50/40 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-200 focus:ring-brand/20 focus:border-brand'"></textarea>
                <div class="flex items-start justify-between gap-3 mt-1.5">
                    <p id="resolution-notes-error" x-show="shownNotesError" x-text="shownNotesError" class="text-xs font-medium text-red-600"></p>
                    <span class="ml-auto text-[11px] text-gray-500 tabular-nums" x-text="`${notes.length}/{{ ResolveTicketRequest::NOTES_MAX }}`"></span>
                </div>

                <div class="mt-5">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 mb-1.5">
                        <label for="resolution-files" class="text-sm font-medium text-gray-700">Files <span class="font-normal text-gray-500">(optional)</span></label>
                        <span class="text-[11px] text-gray-500">JPG, PNG, PDF, DOC · max 10MB each · up to {{ ResolveTicketRequest::MAX_FILES }} files</span>
                    </div>
                    <input id="resolution-files" type="file" multiple x-ref="files" />
                    <p class="text-xs text-gray-500 mt-1">E.g. a screenshot of the fix or a report. Everyone on the ticket can open them.</p>
                    <p x-show="filesError" x-text="filesError" class="text-xs font-medium text-red-600 mt-1.5" role="alert"></p>
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="close()" class="{{ $btn }} border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" :disabled="pendingUploads > 0" class="{{ $btn }} bg-green-600 hover:bg-green-700 text-white">
                        <span x-show="pendingUploads === 0">Continue</span>
                        <span x-show="pendingUploads > 0" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') Uploading files</span>
                    </button>
                </div>
            </div>

            <div x-show="step === 'confirm'" x-cloak>
                <div class="w-11 h-11 rounded-full bg-green-100 text-green-700 flex items-center justify-center mb-4">
                    <i data-lucide="circle-check" class="w-5 h-5"></i>
                </div>
                <h3 class="font-display font-bold text-lg text-gray-900">Mark {{ $ticket->ticket_number }} as resolved?</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">{{ $confirmNote }}</p>
                <blockquote class="mt-4 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-800 whitespace-pre-line break-words max-h-40 overflow-y-auto" x-text="notes"></blockquote>

                <div x-show="fileNames.length" class="mt-3">
                    <p class="text-xs font-medium text-gray-500 mb-1.5" x-text="`${fileNames.length} ${fileNames.length === 1 ? 'file' : 'files'} attached`"></p>
                    <ul class="space-y-1">
                        <template x-for="name in fileNames" :key="name">
                            <li class="flex items-center gap-2 text-sm text-gray-800 min-w-0">
                                <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                <span class="truncate" x-text="name"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="step = 'notes'" :disabled="submitting" class="{{ $btn }} border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Back</button>
                    <button type="button" x-ref="confirm" @click="submit()" :disabled="submitting" class="{{ $btn }} bg-green-600 hover:bg-green-700 text-white">
                        <span x-show="!submitting">Yes, resolve ticket</span>
                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') Resolving</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
