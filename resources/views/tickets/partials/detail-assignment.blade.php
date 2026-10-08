{{--
    Head of Service Management's actions on a ticket (Flows 5–6): Assign or Reassign it to an
    Application Support person, or Resolve directly. Each button only shows when TicketPolicy
    allows it; the forms are plain POSTs and ticket-show.js (ticketAssignment) only drives the
    modals. The picker lists everyone in Application Support — on-leave staff (and, when
    reassigning, the current assignee) are shown but can't be picked.
--}}
@use('App\Enums\TicketStatus')
@use('App\Http\Requests\AssignTicketRequest')
@use('App\Http\Requests\ResolveTicketRequest')
@php
    $user = auth()->user();
    $pickMode = $user->can('assign', $ticket) ? 'assign' : ($user->can('reassign', $ticket) ? 'reassign' : null);
    // Resolve directly is Head of Service Management's alternative to assigning (Flow 5).
    $canResolve = $ticket->status === TicketStatus::PendingAssignment->value && $user->can('resolve', $ticket);
    $verb = $pickMode === 'reassign' ? 'Reassign' : 'Assign';
    $btn = 'inline-flex items-center justify-center gap-2 rounded-lg font-display font-semibold text-sm px-4 py-2.5 transition-colors disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40';
    $assignFailed = $errors->assign->any();
    $resolveFailed = $errors->resolve->any();
    $preselect = $assignFailed ? (int) old('assignee_id') : $suggestedAssignee?->id;
    $initials = fn (string $name) => collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
@endphp

<div x-data="ticketAssignment(@js([
        'reopen' => $assignFailed ? 'pick' : ($resolveFailed ? 'resolve' : null),
        'selected' => $preselect ?: null,
        'names' => $supportStaff?->pluck('name', 'id') ?? [],
        'verb' => $verb,
        'note' => $assignFailed ? old('note', '') : '',
        'pickError' => $errors->assign->first(),
        'notes' => $resolveFailed ? old('resolution_notes', '') : '',
        'notesError' => $errors->resolve->first('resolution_notes'),
        'notesMin' => ResolveTicketRequest::NOTES_MIN,
        'notesMax' => ResolveTicketRequest::NOTES_MAX,
     ]))"
     class="flex flex-col-reverse sm:flex-row gap-2">

    @if ($canResolve)
        <button type="button" @click="openResolve()" class="{{ $btn }} border border-gray-200 bg-white text-gray-800 hover:bg-gray-50">
            <i data-lucide="circle-check" class="w-4 h-4 text-green-600"></i> Resolve directly
        </button>
    @endif
    @if ($pickMode === 'assign')
        <button type="button" @click="openPick()" class="{{ $btn }} bg-brand hover:bg-brand-dark text-white">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Assign
        </button>
    @elseif ($pickMode === 'reassign')
        <button type="button" @click="openPick()" class="{{ $btn }} border border-brand bg-white text-brand hover:bg-brand/5">
            <i data-lucide="repeat-2" class="w-4 h-4"></i> Reassign
        </button>
    @endif

    <div x-show="modal" x-cloak @keydown.escape.window="close()"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         role="dialog" aria-modal="true" :aria-labelledby="modal === 'pick' ? 'pick-title' : 'resolve-title'">
        <div x-show="modal" x-transition.opacity class="absolute inset-0 bg-gray-900/50" @click="close()"></div>

        {{-- Assign / Reassign: choose a person; the button names them, so it doubles as the confirmation. --}}
        @if ($pickMode)
            <form x-show="modal === 'pick'" method="POST" action="{{ route($pickMode === 'assign' ? 'tickets.assign' : 'tickets.reassign', $ticket) }}"
                  @submit="submitting = true" novalidate
                  x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95"
                  class="relative w-full max-w-lg max-h-[90vh] flex flex-col bg-white rounded-xl shadow-2xl">
                @csrf
                <div class="p-6 pb-4">
                    <div class="w-11 h-11 rounded-full bg-brand/10 text-brand flex items-center justify-center mb-4">
                        <i data-lucide="{{ $pickMode === 'assign' ? 'user-plus' : 'repeat-2' }}" class="w-5 h-5"></i>
                    </div>
                    <h3 id="pick-title" class="font-display font-bold text-lg text-gray-900">{{ $verb }} {{ $ticket->ticket_number }}</h3>
                    <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">
                        @if ($pickMode === 'assign')
                            Choose who in Application Support should work on it. The least busy available person is selected for you.
                        @else
                            It's with <span class="font-semibold text-gray-900">{{ $ticket->assignedTo?->name }}</span> now. Choose who should take it over.
                        @endif
                    </p>
                </div>

                <fieldset class="px-6 overflow-y-auto min-h-0">
                    <legend class="sr-only">Application Support</legend>
                    <div class="space-y-2">
                        @forelse ($supportStaff ?? [] as $person)
                            @php
                                $isCurrent = $person->id === $ticket->assigned_to_id;
                                $unavailable = $person->on_leave || $isCurrent;
                            @endphp
                            <label class="flex items-center gap-3 rounded-lg border px-3.5 py-3 transition-colors {{ $unavailable ? 'border-gray-100 bg-gray-50/60 opacity-60 cursor-not-allowed' : 'border-gray-200 cursor-pointer hover:border-brand/40' }}"
                                   @unless ($unavailable) :class="selected === {{ $person->id }} && 'border-brand bg-brand/[0.04] ring-1 ring-brand'" @endunless>
                                <input type="radio" name="assignee_id" value="{{ $person->id }}" x-model.number="selected" @disabled($unavailable) class="sr-only">
                                <span class="w-9 h-9 rounded-full bg-teal/15 text-teal-800 text-xs font-semibold flex items-center justify-center shrink-0">{{ $initials($person->name) }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-sm font-semibold text-gray-900">{{ $person->name }}</span>
                                        @if ($isCurrent)
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium bg-gray-100 text-gray-600">Current</span>
                                        @elseif ($person->on_leave)
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium bg-gray-100 text-gray-600">On leave</span>
                                        @elseif ($suggestedAssignee?->id === $person->id)
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium bg-teal/15 text-teal-700">Least busy</span>
                                        @endif
                                    </span>
                                    <span class="block text-xs text-gray-500 mt-0.5">{{ $person->open_tickets_count }} open {{ Str::plural('ticket', $person->open_tickets_count) }}</span>
                                </span>
                                @unless ($unavailable)
                                    <i data-lucide="circle-check" class="w-5 h-5 text-brand shrink-0" x-show="selected === {{ $person->id }}"></i>
                                @endunless
                            </label>
                        @empty
                            <p class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                There's nobody in Application Support yet.
                            </p>
                        @endforelse
                    </div>
                </fieldset>

                <div class="px-6 pt-4">
                    <label for="assign-note" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Note <span class="font-normal text-gray-500">(optional)</span>
                    </label>
                    <textarea id="assign-note" name="note" rows="2" maxlength="{{ AssignTicketRequest::NOTE_MAX }}" x-model="note"
                              placeholder="{{ $pickMode === 'assign' ? 'e.g. Customer statements go out today, so please prioritise.' : 'e.g. Sade is on the payments project this week.' }}"
                              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-900 placeholder:text-gray-400 resize-y focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Shared in the conversation with everyone on this ticket.</p>
                    <p x-show="pickError" x-text="pickError" class="text-xs font-medium text-red-600 mt-2" role="alert"></p>
                </div>

                <div class="p-6 pt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="close()" :disabled="submitting" class="{{ $btn }} border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" :disabled="submitting || !selected" class="{{ $btn }} bg-brand hover:bg-brand-dark text-white">
                        <span x-show="!submitting" x-text="selectedName ? `${verb} to ${selectedName}` : verb">{{ $verb }}</span>
                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') {{ $pickMode === 'assign' ? 'Assigning' : 'Reassigning' }}</span>
                    </button>
                </div>
            </form>
        @endif

        {{-- Resolve directly: resolution notes, then a confirmation that quotes them. --}}
        @if ($canResolve)
            <form x-show="modal === 'resolve'" x-ref="resolveForm" method="POST" action="{{ route('tickets.resolve', $ticket) }}"
                  @submit.prevent="toConfirm()" novalidate
                  x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95"
                  class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl p-6">
                @csrf

                <div x-show="step === 'notes'">
                    <div class="w-11 h-11 rounded-full bg-green-100 text-green-700 flex items-center justify-center mb-4">
                        <i data-lucide="circle-check" class="w-5 h-5"></i>
                    </div>
                    <h3 id="resolve-title" class="font-display font-bold text-lg text-gray-900">Resolve {{ $ticket->ticket_number }} directly</h3>
                    <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">
                        Use this when the issue is already fixed and doesn't need Application Support. Your notes go to {{ $ticket->requester?->name ?? 'the requester' }} and stay on the ticket.
                    </p>

                    <label for="resolution-notes" class="block text-sm font-medium text-gray-700 mt-5 mb-1.5">Resolution notes <span class="text-red-500">*</span></label>
                    <textarea id="resolution-notes" name="resolution_notes" rows="4" maxlength="{{ ResolveTicketRequest::NOTES_MAX }}"
                              x-ref="resolutionNotes" x-model="notes" @input="notesServerError = ''" @blur="notesTouched = true"
                              placeholder="e.g. Reset the mail relay rule for the Operations mailbox; external emails send normally again."
                              :aria-invalid="(!!shownNotesError).toString()" aria-describedby="resolution-notes-error"
                              class="w-full px-3.5 py-2.5 rounded-lg border text-sm text-gray-900 placeholder:text-gray-400 resize-y focus:outline-none focus:ring-2"
                              :class="shownNotesError ? 'border-red-400 bg-red-50/40 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-200 focus:ring-brand/20 focus:border-brand'"></textarea>
                    <div class="flex items-start justify-between gap-3 mt-1.5">
                        <p id="resolution-notes-error" x-show="shownNotesError" x-text="shownNotesError" class="text-xs font-medium text-red-600"></p>
                        <span class="ml-auto text-[11px] text-gray-500 tabular-nums" x-text="`${notes.length}/{{ ResolveTicketRequest::NOTES_MAX }}`"></span>
                    </div>

                    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        <button type="button" @click="close()" class="{{ $btn }} border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="{{ $btn }} bg-green-600 hover:bg-green-700 text-white">Continue</button>
                    </div>
                </div>

                <div x-show="step === 'confirm'" x-cloak>
                    <div class="w-11 h-11 rounded-full bg-green-100 text-green-700 flex items-center justify-center mb-4">
                        <i data-lucide="circle-check" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-display font-bold text-lg text-gray-900">Mark {{ $ticket->ticket_number }} as resolved?</h3>
                    <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">
                        It won't be assigned to Application Support. {{ $ticket->requester?->name ?? 'The requester' }} is told by email and in the app.
                    </p>
                    <blockquote class="mt-4 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-800 whitespace-pre-line break-words max-h-40 overflow-y-auto" x-text="notes"></blockquote>

                    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        <button type="button" @click="step = 'notes'" :disabled="submitting" class="{{ $btn }} border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Back</button>
                        <button type="button" x-ref="resolveConfirm" @click="submit('resolveForm')" :disabled="submitting" class="{{ $btn }} bg-green-600 hover:bg-green-700 text-white">
                            <span x-show="!submitting">Yes, resolve ticket</span>
                            <span x-show="submitting" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') Resolving</span>
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
