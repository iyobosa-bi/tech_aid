@extends('layouts.app', ['pageTitle' => 'Tickets', 'title' => 'Tickets — Tech Aid'])

@push('head')
    <style>
        @keyframes ticketListProgress { from { transform: translateX(-100%); } to { transform: translateX(400%); } }
        .ticket-list-progress { animation: ticketListProgress 0.9s ease-in-out infinite; }
        input[type="search"]::-webkit-search-cancel-button { display: none; }
    </style>
@endpush

@section('content')
<div
    x-data="ticketList(@js([
        'url' => route('tickets.index'),
        'search' => $filters['search'],
        'status' => $filters['status'] ?? '',
        'sort' => $filters['sort'],
        'direction' => $filters['direction'],
    ]))"
    class="relative bg-white border border-gray-100 rounded-xl shadow-sm"
>
    <div x-show="loading" x-cloak class="absolute inset-x-0 top-0 h-0.5 overflow-hidden rounded-t-xl" aria-hidden="true">
        <div class="h-full w-1/4 bg-brand ticket-list-progress"></div>
    </div>

    <form method="GET" action="{{ route('tickets.index') }}" @submit.prevent="refresh()" role="search"
          class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 border-b border-gray-100">
        <div class="relative flex-1 sm:max-w-md">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
            <input
                type="search" name="search" x-ref="search" x-model="search" value="{{ $filters['search'] }}"
                @input.debounce.300ms="refresh()" @keydown.escape="clearSearch()"
                placeholder="Search by ticket ID, title or requester" autocomplete="off" maxlength="100"
                aria-label="Search tickets"
                class="w-full pl-9 pr-9 py-2.5 rounded-lg border border-gray-200 bg-gray-50/60 text-sm text-gray-800 placeholder:text-gray-400 transition-colors focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
            />
            <kbd x-show="!search" class="hidden sm:flex absolute right-2.5 top-1/2 -translate-y-1/2 items-center justify-center w-5 h-5 rounded border border-gray-200 bg-white text-[10px] font-medium text-gray-400 pointer-events-none" aria-hidden="true">/</kbd>
            <button type="button" x-show="search" x-cloak @click="clearSearch()" aria-label="Clear search"
                    class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-md flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        <div class="relative sm:ml-auto">
            <i data-lucide="list-filter" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
               :class="status ? 'text-brand' : 'text-gray-400'"></i>
            <select name="status" x-model="status" @change="refresh()" aria-label="Filter by status"
                    class="w-full sm:w-auto appearance-none pl-9 pr-9 py-2.5 rounded-lg border bg-white text-sm cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
                    :class="status ? 'border-brand/40 text-brand font-medium' : 'border-gray-200 text-gray-600'">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
        </div>

        {{-- Keeps the current sort when the form is submitted without JavaScript. --}}
        <input type="hidden" name="sort" value="{{ $filters['sort'] }}" :value="sort" />
        <input type="hidden" name="direction" value="{{ $filters['direction'] }}" :value="direction" />
    </form>

    <div x-ref="results" @click="navigate($event)" aria-live="polite" :aria-busy="loading.toString()"
         class="transition-opacity duration-150" :class="loading && 'opacity-60'">
        @include('tickets.partials.results')
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ticket-list.js') }}?v={{ filemtime(public_path('js/ticket-list.js')) }}"></script>
@endpush
