{{-- Files attached to the ticket, with a quick-view modal for images and PDFs (ticket-show.js → attachmentPreview). --}}
<section x-data="attachmentPreview()" class="bg-white border border-gray-100 rounded-xl shadow-sm">
    <h3 class="px-5 py-4 border-b border-gray-100 text-sm font-semibold text-gray-900">
        Attachments <span class="ml-1 text-gray-400 font-normal">{{ $ticket->attachments->count() }}</span>
    </h3>

    @if ($ticket->attachments->isEmpty())
        <p class="px-5 py-6 text-sm text-gray-500">No files were attached to this ticket.</p>
    @else
        <ul class="divide-y divide-gray-100">
            @foreach ($ticket->attachments as $file)
                @php
                    $isPdf = $file->mime_type === 'application/pdf';
                    $badge = $file->isImage() ? 'bg-teal/15 text-teal-700' : ($isPdf ? 'bg-red-50 text-red-600' : 'bg-brand/10 text-brand');
                    $downloadUrl = route('tickets.attachments.download', [$ticket, $file]);
                @endphp
                <li class="flex items-center gap-3 px-5 py-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $badge }}">
                        <i data-lucide="{{ $file->isImage() ? 'image' : 'file-text' }}" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate" title="{{ $file->original_filename }}">{{ $file->original_filename }}</p>
                        <p class="text-xs text-gray-500">{{ $file->extension() }} · {{ $file->humanSize() }}</p>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        @if ($file->isPreviewable())
                            <button type="button"
                                    @click="show(@js([
                                        'kind' => $file->isImage() ? 'image' : 'pdf',
                                        'name' => $file->original_filename,
                                        'url' => route('tickets.attachments.show', [$ticket, $file]),
                                        'downloadUrl' => $downloadUrl,
                                    ]))"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:border-brand hover:text-brand transition-colors">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i><span class="hidden sm:inline">Preview</span>
                            </button>
                        @endif
                        <a href="{{ $downloadUrl }}" aria-label="Download {{ $file->original_filename }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:border-brand hover:text-brand transition-colors">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i><span class="hidden sm:inline">Download</span>
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Quick view --}}
    <div x-show="open" x-cloak @keydown.escape.window="open && close()"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="preview-title">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-gray-900/70" @click="close()"></div>

        <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95"
             class="relative w-full max-w-5xl h-[88vh] bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden">
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
                <i data-lucide="file-search" class="w-4 h-4 text-brand shrink-0"></i>
                <h3 id="preview-title" class="flex-1 min-w-0 truncate text-sm font-semibold text-gray-900" x-text="file.name"></h3>
                <a :href="file.url" target="_blank" rel="noopener" title="Open in new tab"
                   class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-100 hover:text-brand">
                    <i data-lucide="external-link" class="w-4 h-4"></i><span class="sr-only">Open in new tab</span>
                </a>
                <a :href="file.downloadUrl" title="Download"
                   class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-100 hover:text-brand">
                    <i data-lucide="download" class="w-4 h-4"></i><span class="sr-only">Download</span>
                </a>
                <button type="button" @click="close()" title="Close"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                    <i data-lucide="x" class="w-4 h-4"></i><span class="sr-only">Close preview</span>
                </button>
            </div>

            <div class="relative flex-1 min-h-0 bg-gray-100 flex items-center justify-center">
                <div x-show="loading" class="absolute inset-0 flex items-center justify-center text-gray-400">
                    <i data-lucide="loader-circle" class="w-7 h-7 animate-spin"></i>
                </div>
                <template x-if="open && file.kind === 'image'">
                    {{-- x-on:error, not @error — Blade would read "@error" as its validation directive. --}}
                    <img :src="file.url" :alt="file.name" @load="loading = false" x-on:error="loading = false" class="max-w-full max-h-full object-contain p-4" />
                </template>
                <template x-if="open && file.kind === 'pdf'">
                    <iframe :src="file.url" :title="file.name" @load="loading = false" class="w-full h-full bg-white"></iframe>
                </template>
            </div>
        </div>
    </div>
</section>
