{{--
    The assigned Application Support person's actions (Flow 7): Start work (assigned →
    in_progress, a plain POST — low-stakes, so no modal) and Resolve (the shared resolve-action
    modal, with optional files). Each button only shows when TicketPolicy allows it, and the
    controller checks again.
--}}
@php
    $btn = 'inline-flex items-center justify-center gap-2 rounded-lg font-display font-semibold text-sm px-4 py-2.5 transition-colors disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40';
@endphp

<div class="flex flex-col-reverse sm:flex-row gap-2">
    @can('resolve', $ticket)
        @include('tickets.partials.resolve-action', [
            'label' => 'Resolve',
            'buttonClass' => 'bg-green-600 hover:bg-green-700 text-white',
            'title' => "Resolve {$ticket->ticket_number}",
            'intro' => 'Describe what you did to fix it. Your notes and any files go to '.($ticket->requester?->name ?? 'the requester').' and stay on the ticket.',
            'confirmNote' => ($ticket->requester?->name ?? 'The requester').' is told by email and in the app, and can confirm the fix or reopen the ticket.',
        ])
    @endcan

    @can('startProgress', $ticket)
        <form method="POST" action="{{ route('tickets.start', $ticket) }}" x-data="{ starting: false }" @submit="starting = true" class="flex flex-col">
            @csrf
            <button type="submit" :disabled="starting" class="{{ $btn }} bg-brand hover:bg-brand-dark text-white">
                <span x-show="!starting" class="inline-flex items-center gap-2"><i data-lucide="play" class="w-4 h-4"></i> Start work</span>
                <span x-show="starting" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') Starting</span>
            </button>
        </form>
    @endcan
</div>
