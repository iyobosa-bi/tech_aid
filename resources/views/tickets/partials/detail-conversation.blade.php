{{--
    The ticket conversation: messages ('commented' history entries) plus decisions that carried
    a comment, e.g. a decline reason. Everyone on the ticket sees the whole thread.
--}}
@use('App\Enums\TicketAction')
@use('App\Http\Requests\StoreTicketCommentRequest')
@php
    $initials = fn (?string $name) => collect(explode(' ', $name ?? '?'))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
@endphp

<section id="conversation" class="bg-white border border-gray-100 rounded-xl shadow-sm scroll-mt-6">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-900">
            Conversation <span class="ml-1 text-gray-400 font-normal">{{ $conversation->count() }}</span>
        </h3>
        <p class="text-xs text-gray-500 mt-0.5">Visible to the requester, line manager, Head of Service Management and assigned support.</p>
    </div>

    @if ($conversation->isEmpty())
        <div class="px-5 py-10 text-center">
            <div class="w-11 h-11 rounded-xl bg-brand/[0.06] flex items-center justify-center mx-auto mb-3">
                <i data-lucide="messages-square" class="w-5 h-5 text-brand"></i>
            </div>
            <p class="text-sm font-semibold text-gray-900">No messages yet</p>
            <p class="text-sm text-gray-500 mt-1">Ask a question or share an update — everyone on this ticket will see it.</p>
        </div>
    @else
        {{-- Newest at the bottom, like a chat; opens scrolled to the latest message. --}}
        <ol class="px-5 py-5 space-y-5 max-h-[34rem] overflow-y-auto" x-data x-init="$el.scrollTop = $el.scrollHeight">
            @foreach ($conversation as $entry)
                @php
                    $mine = $entry->actor_id === auth()->id();
                    $action = TicketAction::tryFrom($entry->action);
                @endphp
                <li class="flex gap-3 {{ $mine ? 'flex-row-reverse' : '' }}">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-[11px] font-semibold {{ $mine ? 'bg-brand text-white' : 'bg-teal/15 text-teal-800' }}">
                        {{ $initials($entry->actor?->name) }}
                    </span>
                    <div class="min-w-0 max-w-[85%] sm:max-w-[75%] flex flex-col {{ $mine ? 'items-end' : 'items-start' }}">
                        <div class="flex flex-wrap items-baseline gap-x-2 text-xs {{ $mine ? 'justify-end' : '' }}">
                            <span class="font-semibold text-gray-900">{{ $mine ? 'You' : ($entry->actor?->name ?? 'Former staff member') }}</span>
                            @if ($entry->actor_role) <span class="text-gray-500">{{ $entry->actor_role }}</span> @endif
                            <time class="text-gray-400" datetime="{{ $entry->created_at->toIso8601String() }}" title="{{ $entry->created_at->diffForHumans() }}">{{ $entry->created_at->format('M j · H:i') }}</time>
                        </div>
                        @if ($action && ! $entry->isComment())
                            <span class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $action->tone() }}">
                                <i data-lucide="{{ $action->icon() }}" class="w-3 h-3"></i> {{ $action->label() }} the ticket
                            </span>
                        @endif
                        <div class="mt-1 px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed whitespace-pre-line break-words text-left {{ $mine ? 'bg-brand text-white rounded-tr-md' : 'bg-gray-100 text-gray-900 rounded-tl-md' }}">{{ $entry->comment }}</div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    @can('comment', $ticket)
        <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}"
              x-data="ticketReply(@js(['body' => old('body', ''), 'max' => StoreTicketCommentRequest::MAX_LENGTH]))" @submit="send($event)"
              class="border-t border-gray-100 px-5 py-4">
            @csrf
            <label for="message-body" class="sr-only">Message</label>
            <textarea id="message-body" name="body" x-model="body" @keydown="keydown($event)" rows="3"
                      maxlength="{{ StoreTicketCommentRequest::MAX_LENGTH }}" placeholder="Write a message…"
                      class="w-full px-3.5 py-2.5 rounded-lg border bg-white text-sm text-gray-900 placeholder:text-gray-400 resize-none [field-sizing:content] min-h-[5.5rem] max-h-60 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand {{ $errors->has('body') ? 'border-red-400' : 'border-gray-200' }}"></textarea>
            @error('body') <p class="text-xs font-medium text-red-600 mt-1.5">{{ $message }}</p> @enderror
            <div class="flex items-center justify-between gap-3 mt-2.5">
                <p class="text-[11px] text-gray-500">
                    <span class="hidden sm:inline">Ctrl + Enter to send · </span><span class="tabular-nums" x-text="remaining"></span> characters left
                </p>
                <button type="submit" :disabled="sending || !body.trim()"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand hover:bg-brand-dark disabled:opacity-50 disabled:cursor-not-allowed text-white font-display font-semibold text-sm px-4 py-2 transition-colors">
                    <span x-show="!sending" class="inline-flex items-center gap-2"><i data-lucide="send" class="w-4 h-4"></i> Send</span>
                    <span x-show="sending" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') Sending</span>
                </button>
            </div>
        </form>
    @else
        <p class="flex items-center gap-2 border-t border-gray-100 px-5 py-4 text-xs text-gray-500">
            <i data-lucide="lock" class="w-3.5 h-3.5"></i> This ticket is closed, so the conversation is read-only.
        </p>
    @endcan
</section>
