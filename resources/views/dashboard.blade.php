@extends('layouts.app', ['pageTitle' => 'Dashboard'])

@section('content')

<!-- Role-specific stat cards (built by App\Services\DashboardService) -->
@php
    // The three icon-badge tints from design/style-notes.md (KPI cards).
    $tones = [
        'brand' => 'bg-brand/10 text-brand',
        'teal' => 'bg-teal/15 text-teal-600',
        'amber' => 'bg-amber-100 text-amber-600',
    ];
@endphp
{{-- items-start: Admin's status-breakdown card is taller; the others keep their natural height. --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 items-start gap-4 mb-8">
    @foreach ($stats as $card)
        <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-lg {{ $tones[$card['tone']] }} flex items-center justify-center shrink-0">
                    <i data-lucide="{{ $card['icon'] }}" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-600">{{ $card['label'] }}</p>
                    <p class="font-display font-bold text-2xl text-gray-900 tabular-nums leading-tight mt-0.5">{{ $card['value'] }}</p>
                    <p class="text-[11px] leading-snug text-gray-500 mt-0.5">{{ $card['hint'] }}</p>
                </div>
            </div>

            @isset($card['breakdown'])
                <ul class="mt-4 pt-3 border-t border-gray-100 space-y-2">
                    @forelse ($card['breakdown'] as $row)
                        <li class="flex items-center justify-between gap-3">
                            @include('partials.status-badge', ['status' => $row['status']])
                            <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ number_format($row['count']) }}</span>
                        </li>
                    @empty
                        <li class="text-xs text-gray-500">No tickets yet.</li>
                    @endforelse
                </ul>
            @endisset
        </div>
    @endforeach
</div>

<!-- Ticket list -->
<div class="bg-white border border-gray-100 rounded-xl shadow-sm">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <span class="text-sm font-semibold text-gray-700">Recent Tickets</span>
        <a href="{{ Route::has('tickets.index') ? route('tickets.index') : '#' }}" class="text-xs font-medium text-brand hover:underline">View all →</a>
    </div>

    <div class="divide-y divide-gray-50">
        @forelse (($recentTickets ?? []) as $ticket)
            <a href="{{ Route::has('tickets.show') ? route('tickets.show', $ticket) : '#' }}" class="flex items-center justify-between px-5 py-4 hover:bg-gray-50/60">
                <div class="flex items-center gap-4 min-w-0">
                    <span class="text-xs text-gray-400 font-medium shrink-0">{{ $ticket->ticket_number }}</span>
                    <p class="text-sm text-gray-800 truncate">{{ $ticket->title }}</p>
                </div>
                @include('partials.status-badge', ['status' => $ticket->status])
            </a>
        @empty
            <div class="px-5 py-10 text-center">
                <i data-lucide="inbox" class="w-8 h-8 text-gray-300 mx-auto mb-2"></i>
                <p class="text-sm text-gray-400">No tickets yet.</p>
            </div>
        @endforelse
    </div>
</div>

@endsection
