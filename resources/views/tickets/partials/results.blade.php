{{-- Always loaded (even with no results) so a table swapped in later by live search has its script. --}}
@include('tickets.partials.table-assets')
@php
    $hasFilters = $filters['search'] !== '' || $filters['status'];
    $listUrl ??= route('tickets.index'); // Admin → All tickets passes its own
@endphp

@if ($tickets->isEmpty())
    <div class="px-6 py-16 text-center">
        <div class="w-12 h-12 rounded-xl bg-brand/[0.06] flex items-center justify-center mx-auto mb-4">
            <i data-lucide="{{ $hasFilters ? 'search-x' : 'inbox' }}" class="w-5 h-5 text-brand"></i>
        </div>
        @if ($hasFilters)
            <p class="text-sm font-semibold text-gray-900">No matching tickets</p>
            <p class="text-sm text-gray-500 mt-1">
                @if ($filters['search'] !== '')
                    Nothing matches “{{ $filters['search'] }}”{{ $filters['status'] ? ' with that status' : '' }}.
                @else
                    No tickets have that status right now.
                @endif
            </p>
            <a href="{{ $listUrl }}" @click.prevent="clearFilters()"
               class="inline-flex items-center gap-1.5 mt-4 text-sm font-semibold text-brand hover:text-brand-dark">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Clear filters
            </a>
        @else
            <p class="text-sm font-semibold text-gray-900">No tickets yet</p>
            <p class="text-sm text-gray-500 mt-1">Tickets you raise, approve or work on will show up here.</p>
            @can('create', App\Models\Ticket::class)
                <a href="{{ route('tickets.create') }}"
                   class="inline-flex items-center gap-2 mt-5 bg-brand hover:bg-brand-dark text-white font-display font-semibold text-sm px-4 py-2.5 rounded-lg transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i> Raise a ticket
                </a>
            @endcan
        @endif
    </div>
@else
    @include('tickets.partials.table', ['sortable' => true])

    <div class="flex items-center justify-between gap-3 px-5 py-3.5 border-t border-gray-100">
        <p class="text-sm text-gray-700">
            Showing <span class="font-semibold text-gray-900 tabular-nums">{{ $tickets->firstItem() }}–{{ $tickets->lastItem() }}</span>
            of <span class="font-semibold text-gray-900 tabular-nums">{{ $tickets->total() }}</span>
        </p>
        @if ($tickets->hasPages())
            <nav class="flex items-center gap-1.5" aria-label="Pagination">
                @php
                    $pageLink = 'w-9 h-9 rounded-lg border flex items-center justify-center transition-colors';
                    $enabled = $pageLink.' border-gray-200 text-gray-800 hover:border-brand hover:text-brand';
                    $disabled = $pageLink.' border-gray-100 text-gray-300 cursor-not-allowed';
                @endphp
                @if ($tickets->onFirstPage())
                    <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevron-left" class="w-4 h-4"></i></span>
                @else
                    <a href="{{ $tickets->previousPageUrl() }}" data-ajax rel="prev" aria-label="Previous page" class="{{ $enabled }}"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
                @endif
                <span class="px-2 text-sm text-gray-800 tabular-nums">{{ $tickets->currentPage() }} / {{ $tickets->lastPage() }}</span>
                @if ($tickets->hasMorePages())
                    <a href="{{ $tickets->nextPageUrl() }}" data-ajax rel="next" aria-label="Next page" class="{{ $enabled }}"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
                @else
                    <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevron-right" class="w-4 h-4"></i></span>
                @endif
            </nav>
        @endif
    </div>
@endif
