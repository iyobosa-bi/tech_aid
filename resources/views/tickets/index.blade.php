@extends('layouts.app', ['pageTitle' => 'Tickets', 'title' => 'Tickets — Tech Aid'])

@push('head')
    <style>
        @keyframes ticketListProgress { from { transform: translateX(-100%); } to { transform: translateX(400%); } }
        .ticket-list-progress { animation: ticketListProgress 0.9s ease-in-out infinite; }
        input[type="search"]::-webkit-search-cancel-button { display: none; }
    </style>
@endpush

@section('content')
{{-- Layout follows design/screenshots/ticketTable.png: tinted panel, toolbar row, white table card inside. --}}
<div
    x-data="ticketList(@js([
        'url' => route('tickets.index'),
        'search' => $filters['search'],
        'status' => $filters['status'] ?? '',
        'sort' => $filters['sort'],
        'direction' => $filters['direction'],
    ]))"
    class="relative rounded-xl bg-brand/[0.06] p-3"
>
    <div x-show="loading" x-cloak class="absolute inset-x-0 top-0 h-0.5 overflow-hidden rounded-t-xl" aria-hidden="true">
        <div class="h-full w-1/4 bg-brand ticket-list-progress"></div>
    </div>

    <form method="GET" action="{{ route('tickets.index') }}" @submit.prevent="refresh()" role="search"
          class="flex flex-col lg:flex-row lg:items-center gap-3 px-1 pt-1 pb-4">
        <h2 class="font-display font-semibold text-sm text-gray-900 whitespace-nowrap lg:mr-6">{{ $heading }}</h2>

        <div class="flex gap-2 flex-1 lg:max-w-xl">
            <div class="relative flex-1 min-w-0">
                <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-brand pointer-events-none"></i>
                <input
                    type="search" name="search" x-ref="search" x-model="search" value="{{ $filters['search'] }}"
                    @input.debounce.300ms="refresh()" @keydown.escape="clearSearch()"
                    placeholder="{{ $showRequester ? 'Search tickets by ID, title or requester' : 'Search tickets by ID or title' }}" autocomplete="off" maxlength="100"
                    aria-label="Search tickets"
                    class="w-full h-11 pl-10 pr-9 rounded-lg border border-white bg-white text-sm text-gray-900 placeholder:text-gray-400 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
                />
                <button type="button" x-show="search" x-cloak @click="clearSearch()" aria-label="Clear search"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-6 h-6 rounded-md flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <button type="submit"
                    class="h-11 px-5 sm:px-7 rounded-lg bg-brand hover:bg-brand-dark active:scale-[0.98] text-white font-display font-semibold text-sm shadow-sm transition">
                Search
            </button>
        </div>

        {{-- The empty option doubles as the placeholder: it reads "Filter" until a status is picked,
             then "All statuses" so it can be chosen to clear the filter. --}}
        <div class="relative lg:ml-auto lg:w-60">
            <select name="status" x-model="status" @change="refresh()" aria-label="Filter by status"
                    class="w-full h-11 appearance-none pl-4 pr-10 rounded-lg border border-brand bg-white text-sm cursor-pointer transition focus:outline-none focus:ring-2 focus:ring-brand/20"
                    :class="status ? 'text-brand font-medium' : 'text-gray-400'">
                <option value="" class="text-gray-900" x-text="status ? 'All statuses' : 'Filter'">{{ $filters['status'] ? 'All statuses' : 'Filter' }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" class="text-gray-900" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <i data-lucide="list-filter" class="w-4 h-4 absolute right-3.5 top-1/2 -translate-y-1/2 text-brand pointer-events-none"></i>
        </div>

        {{-- Keeps the current sort when the form is submitted without JavaScript. --}}
        <input type="hidden" name="sort" value="{{ $filters['sort'] }}" :value="sort" />
        <input type="hidden" name="direction" value="{{ $filters['direction'] }}" :value="direction" />
    </form>

    <div x-ref="results" @click="navigate($event)" aria-live="polite" :aria-busy="loading.toString()"
         class="bg-white rounded-lg border border-gray-100 shadow-sm overflow-hidden transition-opacity duration-150" :class="loading && 'opacity-60'">
        @include('tickets.partials.results')
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ticket-list.js') }}?v={{ filemtime(public_path('js/ticket-list.js')) }}"></script>
@endpush
