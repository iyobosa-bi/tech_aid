{{-- Raise a ticket (create) or edit & resubmit a returned one (Flow 4) — $ticket is null when creating. --}}
@php
    $editing = (bool) $ticket;
    $pageTitle = $editing ? "Edit {$ticket->ticket_number}" : 'New Ticket';
@endphp
@extends('layouts.app', ['pageTitle' => $pageTitle, 'title' => $pageTitle.' — Tech Aid'])

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/filepond@4.32.7/dist/filepond.min.css" rel="stylesheet" />
    <link href="{{ asset('css/filepond-theme.css') }}" rel="stylesheet" />
@endpush

@section('content')
@php
    $inputBase = 'w-full px-3.5 py-2.5 rounded-lg border bg-white text-gray-800 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand';
    $inputClass = fn (string $field) => $inputBase.' '.($errors->has($field) ? 'border-red-300' : 'border-gray-200');
    $backUrl = $editing ? route('tickets.show', $ticket) : route('dashboard');
@endphp

<div
    x-data="ticketForm(@js([
        'old' => [
            'title' => old('title', $ticket?->title),
            'description' => old('description', $ticket?->description),
            'priority' => old('priority', $ticket?->priority),
        ],
        'maxFiles' => $maxAttachments,
        'maxFileSize' => '10MB',
        'existingUploads' => $existingUploads,
        'urls' => [
            'process' => route('tickets.uploads.store'),
            'revert' => route('tickets.uploads.destroy'),
        ],
    ]))"
    class="max-w-4xl mx-auto"
>
    <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-brand mb-4">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> {{ $editing ? 'Back to ticket' : 'Back to dashboard' }}
    </a>

    <form method="POST" action="{{ $editing ? route('tickets.update', $ticket) : route('tickets.store') }}" @submit="submit($event)" novalidate
          class="bg-white border border-gray-100 rounded-xl shadow-sm">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="px-6 sm:px-8 pt-6 pb-5 border-b border-gray-100 flex items-start gap-4">
            <div class="w-11 h-11 rounded-lg bg-brand/10 flex items-center justify-center shrink-0">
                <i data-lucide="{{ $editing ? 'file-pen-line' : 'ticket-plus' }}" class="w-5 h-5 text-brand"></i>
            </div>
            <div>
                <h2 class="font-display font-bold text-lg text-gray-800">{{ $editing ? "Edit & resubmit {$ticket->ticket_number}" : 'Create New Ticket' }}</h2>
                <p class="text-sm text-gray-400 mt-0.5">
                    {{ $editing ? 'Update the details your line manager asked about, then send it back for approval.' : 'Tell us about your problem so we can get you the right help and support.' }}
                </p>
            </div>
        </div>

        <div class="px-6 sm:px-8 py-6 space-y-6">

            @if ($editing && $returnedBy)
                <div class="flex items-start gap-3 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200">
                    <i data-lucide="undo-2" class="w-4 h-4 text-amber-700 shrink-0 mt-0.5"></i>
                    <div class="text-xs text-amber-900">
                        <p class="font-semibold">{{ $returnedBy->actor?->name ?? 'Your line manager' }} returned this ticket:</p>
                        <p class="mt-1 whitespace-pre-line">“{{ $returnedBy->comment }}”</p>
                    </div>
                </div>
            @endif

            @if ($lineManager)
                <div class="flex items-center gap-3 px-4 py-3 rounded-lg bg-teal/10 border border-teal/30">
                    <i data-lucide="user-check" class="w-4 h-4 text-teal-700 shrink-0"></i>
                    <p class="text-xs text-teal-800">
                        Once {{ $editing ? 'resubmitted' : 'submitted' }}, this ticket goes {{ $editing ? 'back ' : '' }}to <span class="font-semibold">{{ $lineManager->name }}</span> (your line manager) for approval.
                    </p>
                </div>
            @else
                <div class="flex items-start gap-3 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                    <p class="text-xs text-amber-800">
                        You don't have a line manager assigned yet, so tickets can't be routed for approval. Please contact an administrator.
                    </p>
                </div>
            @endif

            @if ($errors->any())
                <div class="flex items-start gap-3 px-4 py-3 rounded-lg bg-red-50 border border-red-200">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0 mt-0.5"></i>
                    <div class="text-xs text-red-700">
                        <p class="font-semibold mb-1">Please fix the following before submitting:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Title --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="title" class="text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <span class="text-[11px]" :class="title.length > 150 ? 'text-red-500' : 'text-gray-400'" x-text="`${title.length}/150`"></span>
                </div>
                <input id="title" name="title" type="text" x-model="title" maxlength="150" required
                       placeholder="e.g. Can't send or receive emails"
                       class="{{ $inputClass('title') }}" />
                @error('title') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="description" class="text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                    <span class="text-[11px]" :class="description.length > 5000 ? 'text-red-500' : 'text-gray-400'" x-text="`${description.length}/5000`"></span>
                </div>
                <textarea id="description" name="description" rows="6" x-model="description" maxlength="5000" required
                          placeholder="What happened? When did it start? What have you already tried? Include any error messages you see."
                          class="{{ $inputClass('description') }} resize-y"></textarea>
                @error('description') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
            </div>

            {{-- Category --}}
            @php $selectedCategory = old('category', $ticket?->category); @endphp
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1.5">Category <span class="text-red-500">*</span></label>
                <select id="category" name="category" required class="{{ $inputClass('category') }}">
                    <option value="" disabled @selected(! $selectedCategory)>Select a category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->value }}" @selected($selectedCategory === $category->value)>{{ $category->label() }}</option>
                    @endforeach
                </select>
                @error('category') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
            </div>

            {{-- Priority --}}
            <fieldset>
                <legend class="text-sm font-medium text-gray-700 mb-1.5">Priority <span class="text-red-500">*</span></legend>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach ($priorities as $priority)
                        <label class="relative flex items-start gap-3 px-4 py-3 rounded-lg border cursor-pointer transition-colors"
                               :class="priority === '{{ $priority->value }}' ? 'border-brand bg-brand/5 ring-2 ring-brand/15' : '{{ $errors->has('priority') ? 'border-red-300' : 'border-gray-200' }} hover:border-gray-300'">
                            <input type="radio" name="priority" value="{{ $priority->value }}" x-model="priority" class="mt-1 accent-[#152a9e]" />
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">{{ $priority->label() }}</span>
                                <span class="block text-[11px] text-gray-400 leading-snug">{{ $priority->hint() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('priority') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
            </fieldset>

            {{-- Attachments --}}
            <div>
                @if ($attachments->isNotEmpty())
                    <p class="text-sm font-medium text-gray-700 mb-1.5">Already attached</p>
                    <ul class="mb-4 divide-y divide-gray-100 rounded-lg border border-gray-100">
                        @foreach ($attachments as $file)
                            <li class="flex items-center gap-3 px-3.5 py-2.5">
                                <i data-lucide="{{ $file->isImage() ? 'image' : 'file-text' }}" class="w-4 h-4 text-gray-400 shrink-0"></i>
                                <span class="flex-1 min-w-0 truncate text-sm text-gray-800">{{ $file->original_filename }}</span>
                                <span class="text-[11px] text-gray-400 shrink-0">{{ $file->humanSize() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($maxAttachments > 0)
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="attachments" class="text-sm font-medium text-gray-700">
                            {{ $attachments->isNotEmpty() ? 'Add more files' : 'Attachments' }} <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <span class="text-[11px] text-gray-400">JPG, PNG, PDF, DOC · max 10MB each · up to {{ $maxAttachments }} {{ $attachments->isNotEmpty() ? 'more' : '' }} {{ Str::plural('file', $maxAttachments) }}</span>
                    </div>
                    <input id="attachments" type="file" name="attachments[]" multiple x-ref="attachments" />
                    @error('attachments') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
                    @error('attachments.*') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
                    <p x-show="submitBlockedReason" x-cloak x-text="submitBlockedReason" class="text-xs text-red-600 mt-1.5"></p>
                @else
                    <p class="text-xs text-gray-500">This ticket already has the maximum of {{ $attachments->count() }} files.</p>
                @endif
            </div>

            @if ($editing)
                {{-- Saved with the resubmission; shows in the ticket's conversation. --}}
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-1.5">Note for {{ $lineManager?->name ?? 'your line manager' }} <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea id="note" name="note" rows="3" maxlength="1000"
                              placeholder="e.g. I've added the screenshot and the account number you asked for."
                              class="{{ $inputClass('note') }} resize-y">{{ old('note') }}</textarea>
                    @error('note') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>

        <div class="px-6 sm:px-8 py-4 border-t border-gray-100 bg-gray-50/60 rounded-b-xl flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
            <a href="{{ $backUrl }}" class="text-center text-sm font-medium text-gray-500 hover:text-gray-700 px-4 py-2.5">Cancel</a>
            <button type="submit"
                    @disabled(! $lineManager)
                    :disabled="submitting || pendingUploads > 0 || {{ $lineManager ? 'false' : 'true' }}"
                    class="inline-flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark disabled:opacity-50 disabled:cursor-not-allowed text-white font-display font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
                <span x-show="!submitting && pendingUploads === 0" class="inline-flex items-center gap-2"><i data-lucide="send" class="w-4 h-4"></i> {{ $editing ? 'Resubmit for approval' : 'Submit Ticket' }}</span>
                <span x-show="pendingUploads > 0 && !submitting" x-cloak class="inline-flex items-center gap-2" role="status">
                    @include('partials.spinner') Uploading attachments
                </span>
                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2" role="status">
                    @include('partials.spinner') {{ $editing ? 'Resubmitting' : 'Submitting' }}
                </span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-type@1.2.9/dist/filepond-plugin-file-validate-type.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-size@2.2.8/dist/filepond-plugin-file-validate-size.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond@4.32.7/dist/filepond.min.js"></script>
    <script src="{{ asset('js/ticket-uploads.js') }}?v={{ filemtime(public_path('js/ticket-uploads.js')) }}"></script>
    <script src="{{ asset('js/ticket-form.js') }}?v={{ filemtime(public_path('js/ticket-form.js')) }}"></script>
@endpush
