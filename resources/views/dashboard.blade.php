@extends('layouts.app', ['pageTitle' => 'Dashboard'])

@section('content')

<!-- KPI cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-brand/10 flex items-center justify-center shrink-0">
            <i data-lucide="ticket" class="w-5 h-5 text-brand"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-1">Open Tickets</p>
            <p class="font-display font-bold text-2xl text-gray-800">{{ $openTicketsCount ?? 0 }}</p>
        </div>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-teal/15 flex items-center justify-center shrink-0">
            <i data-lucide="clock" class="w-5 h-5 text-teal-600"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-1">Avg. Resolution</p>
            <p class="font-display font-bold text-2xl text-gray-800">{{ $avgResolutionTime ?? '—' }}</p>
        </div>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
            <i data-lucide="star" class="w-5 h-5 text-amber-500"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-1">My Rating</p>
            <p class="font-display font-bold text-2xl text-gray-800">{{ $myRating ?? '—' }}</p>
        </div>
    </div>
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
