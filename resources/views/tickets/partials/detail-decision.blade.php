{{--
    Line manager's Approve / Decline (Flow 3) — only included when TicketPolicy::approve allows.
    Both go through a confirmation step in a modal. Approve takes an optional comment (blank
    = TicketDecisionService::DEFAULT_APPROVAL_COMMENT); a decline needs a comment first.
    Either comment is posted to the conversation.
    The forms are plain POSTs; ticket-show.js (ticketDecision) only drives the modal.
--}}
@use('App\Http\Requests\ApproveTicketRequest')
@use('App\Services\TicketDecisionService')
@php
    $requesterFirstName = explode(' ', $ticket->requester?->name ?? 'the requester')[0];
    $btn = 'inline-flex items-center justify-center gap-2 rounded-lg font-display font-semibold text-sm px-4 py-2.5 transition-colors disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40';
    // Approve errors live in their own bag (ApproveTicketRequest), so old('comment') goes back to the right box.
    $approveFailed = $errors->approve->has('comment');
    $declineFailed = $errors->has('comment');
@endphp

<div x-data="ticketDecision(@js([
        'reopenDecline' => $declineFailed,
        'comment' => $declineFailed ? old('comment', '') : '',
        'error' => $errors->first('comment'),
        'minLength' => 5,
        'maxLength' => 1000,
        'reopenApprove' => $approveFailed,
        'approveComment' => $approveFailed ? old('comment', '') : '',
        'approveError' => $errors->approve->first('comment'),
     ]))"
     class="flex flex-col-reverse sm:flex-row gap-2">

    <button type="button" @click="openDecline()" class="{{ $btn }} border border-red-200 bg-white text-red-700 hover:bg-red-50">
        <i data-lucide="undo-2" class="w-4 h-4"></i> Decline
    </button>
    <button type="button" @click="openApprove()" class="{{ $btn }} bg-brand hover:bg-brand-dark text-white">
        <i data-lucide="check" class="w-4 h-4"></i> Approve
    </button>

    <div x-show="modal" x-cloak @keydown.escape.window="close()"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         role="dialog" aria-modal="true" :aria-labelledby="modal === 'approve' ? 'approve-title' : 'decline-title'">
        <div x-show="modal" x-transition.opacity class="absolute inset-0 bg-gray-900/50" @click="close()"></div>

        {{-- Approve: optional comment + confirmation --}}
        <div x-show="modal === 'approve'"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95"
             class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl p-6">
            <div class="w-11 h-11 rounded-full bg-teal/15 text-teal-700 flex items-center justify-center mb-4">
                <i data-lucide="check" class="w-5 h-5"></i>
            </div>
            <h3 id="approve-title" class="font-display font-bold text-lg text-gray-900">Approve {{ $ticket->ticket_number }}?</h3>
            <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">
                It moves to Head of Service Management to be assigned to a support engineer, and {{ $requesterFirstName }} will see the update. This can't be undone.
            </p>

            <form x-ref="approveForm" method="POST" action="{{ route('tickets.approve', $ticket) }}" @submit.prevent novalidate>
                @csrf
                <label for="approve-comment" class="block text-sm font-medium text-gray-700 mt-5 mb-1.5">
                    Comment <span class="font-normal text-gray-500">(optional)</span>
                </label>
                <textarea id="approve-comment" name="comment" rows="3" maxlength="{{ ApproveTicketRequest::MAX_COMMENT_LENGTH }}"
                          x-ref="approveComment" x-model="approveComment" @input="approveServerError = ''"
                          placeholder="Leave blank to send “{{ TicketDecisionService::DEFAULT_APPROVAL_COMMENT }}”"
                          :aria-invalid="(!!approveServerError).toString()" aria-describedby="approve-comment-help"
                          class="w-full px-3.5 py-2.5 rounded-lg border text-sm text-gray-900 placeholder:text-gray-400 resize-y focus:outline-none focus:ring-2"
                          :class="approveServerError ? 'border-red-400 bg-red-50/40 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-200 focus:ring-brand/20 focus:border-brand'"></textarea>
                <div class="flex items-start justify-between gap-3 mt-1.5">
                    <p id="approve-comment-help" class="text-xs" :class="approveServerError ? 'font-medium text-red-600' : 'text-gray-500'"
                       x-text="approveServerError || 'Shared in the conversation with everyone on this ticket.'">Shared in the conversation with everyone on this ticket.</p>
                    <span class="ml-auto text-[11px] text-gray-500 tabular-nums" x-text="`${approveComment.length}/{{ ApproveTicketRequest::MAX_COMMENT_LENGTH }}`"></span>
                </div>
            </form>

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" @click="close()" :disabled="submitting" class="{{ $btn }} border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" @click="submit('approveForm')" :disabled="submitting" class="{{ $btn }} bg-brand hover:bg-brand-dark text-white">
                    <span x-show="!submitting">Yes, approve</span>
                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') Approving</span>
                </button>
            </div>
        </div>

        {{-- Decline: required comment, then confirmation --}}
        <form x-show="modal === 'decline'" x-ref="declineForm" method="POST" action="{{ route('tickets.decline', $ticket) }}"
              @submit.prevent="toConfirm()" novalidate
              x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95"
              class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl p-6">
            @csrf

            <div x-show="step === 'comment'">
                <div class="w-11 h-11 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mb-4">
                    <i data-lucide="message-square-warning" class="w-5 h-5"></i>
                </div>
                <h3 id="decline-title" class="font-display font-bold text-lg text-gray-900">Decline {{ $ticket->ticket_number }}</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">
                    Tell {{ $requesterFirstName }} what needs to change. Your comment is sent to them and saved on the ticket.
                </p>

                <label for="decline-comment" class="block text-sm font-medium text-gray-700 mt-5 mb-1.5">Comment <span class="text-red-500">*</span></label>
                <textarea id="decline-comment" name="comment" rows="4" maxlength="1000"
                          x-ref="declineComment" x-model="comment" @input="serverError = ''" @blur="touched = true"
                          placeholder="e.g. Please attach a screenshot of the error and the customer's account number."
                          :aria-invalid="(!!shownError).toString()" aria-describedby="decline-comment-error"
                          class="w-full px-3.5 py-2.5 rounded-lg border text-sm text-gray-900 placeholder:text-gray-400 resize-y focus:outline-none focus:ring-2"
                          :class="shownError ? 'border-red-400 bg-red-50/40 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-200 focus:ring-brand/20 focus:border-brand'"></textarea>
                <div class="flex items-start justify-between gap-3 mt-1.5">
                    <p id="decline-comment-error" x-show="shownError" x-text="shownError" class="text-xs font-medium text-red-600"></p>
                    <span class="ml-auto text-[11px] text-gray-500 tabular-nums" x-text="`${comment.length}/1000`"></span>
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="close()" class="{{ $btn }} border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="{{ $btn }} bg-red-600 hover:bg-red-700 text-white">Continue</button>
                </div>
            </div>

            <div x-show="step === 'confirm'" x-cloak>
                <div class="w-11 h-11 rounded-full bg-red-100 text-red-700 flex items-center justify-center mb-4">
                    <i data-lucide="undo-2" class="w-5 h-5"></i>
                </div>
                <h3 class="font-display font-bold text-lg text-gray-900">Return this ticket to {{ $ticket->requester?->name ?? 'the requester' }}?</h3>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">
                    It goes back to them as <span class="font-semibold text-gray-900">Returned</span> — not closed — so they can edit and resubmit it.
                </p>
                <blockquote class="mt-4 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-800 whitespace-pre-line break-words max-h-40 overflow-y-auto" x-text="comment"></blockquote>

                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="step = 'comment'" :disabled="submitting" class="{{ $btn }} border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Back</button>
                    <button type="button" x-ref="declineConfirm" @click="submit('declineForm')" :disabled="submitting" class="{{ $btn }} bg-red-600 hover:bg-red-700 text-white">
                        <span x-show="!submitting">Yes, decline ticket</span>
                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') Declining</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
