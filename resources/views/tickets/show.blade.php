@extends('layouts.app', ['pageTitle' => $ticket->ticket_number, 'title' => $ticket->ticket_number.' — Tech Aid'])

@use('App\Enums\TicketCategory')
@use('App\Enums\TicketPriority')
@use('App\Enums\TicketStatus')

@section('content')
@php
    $category = TicketCategory::tryFrom((string) $ticket->category)?->label() ?? '—';
    $priority = TicketPriority::tryFrom($ticket->priority)?->label() ?? ucfirst($ticket->priority);
    $requesterName = $ticket->requester?->name ?? 'Unknown';
    $assigneeName = $ticket->assignedTo?->name ?? 'Application Support';

    // A one-line "what happens next" for the waiting statuses.
    $nextStep = match ($ticket->status) {
        TicketStatus::PendingLineManagerApproval->value => 'Waiting for '.($ticket->lineManager?->name ?? 'the line manager').' to approve.',
        TicketStatus::Returned->value => "Waiting for {$requesterName} to edit and resubmit.",
        TicketStatus::PendingAssignment->value => 'Waiting for Head of Service Management to assign it.',
        TicketStatus::Assigned->value => $isAssignee ? 'Waiting for you to start work.' : "Waiting for {$assigneeName} to start work.",
        TicketStatus::InProgress->value => $isAssignee ? "You're working on it." : "{$assigneeName} is working on it.",
        TicketStatus::Resolved->value => "Waiting for {$requesterName} to confirm the fix.",
        default => null,
    };
@endphp

<a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-brand mb-4">
    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to tickets
</a>

{{-- Header --}}
<div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 sm:p-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="text-xs font-semibold text-gray-500 tabular-nums">{{ $ticket->ticket_number }}</span>
                @include('partials.status-badge', ['status' => $ticket->status, 'variant' => 'outline'])
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $priority }} priority</span>
            </div>
            <h2 class="font-display font-bold text-xl text-gray-900 break-words">{{ $ticket->title }}</h2>
            <p class="text-sm text-gray-600 mt-1.5">
                Raised by <span class="font-semibold text-gray-900">{{ $requesterName }}</span>
                · {{ $category }}
                · <time datetime="{{ $ticket->created_at->toIso8601String() }}">{{ $ticket->created_at->format('M j, Y · H:i') }}</time>
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            @can('approve', $ticket)
                @include('tickets.partials.detail-decision')
            @endcan
            @if ($supportStaff)
                @include('tickets.partials.detail-assignment')
            @elseif ($isAssignee)
                @include('tickets.partials.detail-work')
            @endif
            @can('resubmit', $ticket)
                <a href="{{ route('tickets.edit', $ticket) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand hover:bg-brand-dark text-white font-display font-semibold text-sm px-4 py-2.5 transition-colors">
                    <i data-lucide="file-pen-line" class="w-4 h-4"></i> Edit & resubmit
                </a>
            @endcan
        </div>
    </div>
</div>

{{-- Returned to the requester (Flow 3): show why, wherever the ticket is viewed from. --}}
@if ($returnedBy)
    <div class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
        <i data-lucide="undo-2" class="w-5 h-5 text-amber-700 shrink-0 mt-0.5"></i>
        <div class="min-w-0 text-sm text-amber-900">
            <p class="font-semibold">
                {{ $returnedBy->actor?->name ?? 'The line manager' }} returned this ticket
                <span class="font-normal text-amber-800">· {{ $returnedBy->created_at->format('M j, Y · H:i') }}</span>
            </p>
            <p class="mt-1 whitespace-pre-line break-words">“{{ $returnedBy->comment }}”</p>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="lg:col-span-2 space-y-6 min-w-0">
        <section class="bg-white border border-gray-100 rounded-xl shadow-sm">
            <h3 class="px-5 py-4 border-b border-gray-100 text-sm font-semibold text-gray-900">Description</h3>
            <p class="px-5 py-4 text-sm text-gray-800 leading-relaxed whitespace-pre-line break-words">{{ $ticket->description }}</p>
        </section>

        @include('tickets.partials.detail-attachments')
        @include('tickets.partials.detail-conversation')
    </div>

    <div class="space-y-6 min-w-0">
        <section class="bg-white border border-gray-100 rounded-xl shadow-sm">
            <h3 class="px-5 py-4 border-b border-gray-100 text-sm font-semibold text-gray-900">Details</h3>
            <dl class="divide-y divide-gray-100 text-sm">
                @php
                    $details = [
                        'Requester' => $requesterName.($ticket->requester?->department ? ' · '.$ticket->requester->department : ''),
                        'Line manager' => $ticket->lineManager?->name ?? '—',
                        'Assigned to' => $ticket->assignedTo?->name ?? 'Not assigned',
                        'Category' => $category,
                        'Priority' => $priority,
                        'Last updated' => $ticket->updated_at->format('M j, Y · H:i'),
                    ];
                @endphp
                <div class="flex items-center justify-between gap-4 px-5 py-3">
                    <dt class="text-gray-500">Status</dt>
                    <dd>@include('partials.status-badge', ['status' => $ticket->status])</dd>
                </div>
                @foreach ($details as $label => $value)
                    <div class="flex items-start justify-between gap-4 px-5 py-3">
                        <dt class="text-gray-500 shrink-0">{{ $label }}</dt>
                        <dd class="text-gray-900 font-medium text-right break-words min-w-0">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            @if ($nextStep)
                <p class="flex items-start gap-2 px-5 py-3 border-t border-gray-100 text-xs text-gray-600">
                    <i data-lucide="hourglass" class="w-3.5 h-3.5 mt-px shrink-0 text-gray-400"></i> {{ $nextStep }}
                </p>
            @endif
        </section>

        @include('tickets.partials.detail-timeline')
    </div>
</div>
@endsection

{{-- The resolve modal's file dropzone — only for someone who may resolve this ticket. --}}
@can('resolve', $ticket)
    @push('head')
        <link href="https://cdn.jsdelivr.net/npm/filepond@4.32.7/dist/filepond.min.css" rel="stylesheet" />
        <link href="{{ asset('css/filepond-theme.css') }}" rel="stylesheet" />
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-type@1.2.9/dist/filepond-plugin-file-validate-type.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-size@2.2.8/dist/filepond-plugin-file-validate-size.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/filepond@4.32.7/dist/filepond.min.js"></script>
        <script src="{{ asset('js/ticket-uploads.js') }}?v={{ filemtime(public_path('js/ticket-uploads.js')) }}"></script>
    @endpush
@endcan

@push('scripts')
    <script src="{{ asset('js/ticket-show.js') }}?v={{ filemtime(public_path('js/ticket-show.js')) }}"></script>
@endpush
