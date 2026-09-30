@use('App\Enums\TicketCategory')
@use('App\Enums\TicketPriority')
@php
    $hasFilters = $filters['search'] !== '' || $filters['status'];
    $canOpen = Route::has('tickets.show');

    // Every column stays visible; narrow screens scroll the table sideways instead.
    // The ticket ID column is pinned so rows stay identifiable while scrolling.
    $sticky = 'ticket-sticky sticky left-0 z-10';

    $columns = array_filter([
        'id' => ['label' => 'ID', 'class' => "pl-5 pr-4 {$sticky} bg-gray-50"],
        'requester' => $showRequester ? ['label' => 'Name', 'class' => 'px-3'] : null,
        'title' => ['label' => 'Title', 'class' => 'px-3', 'sortable' => false],
        'category' => ['label' => 'Category', 'class' => 'px-3'],
        'priority' => ['label' => 'Priority', 'class' => 'px-3'],
        'assignee' => ['label' => 'Assigned to', 'class' => 'px-3', 'sortable' => false],
        'status' => ['label' => 'Status', 'class' => 'px-3'],
        'created_at' => ['label' => 'Date opened', 'class' => 'pl-3 pr-5'],
    ]);

    $sortUrl = function (string $column) use ($filters) {
        $direction = $filters['sort'] === $column
            ? ($filters['direction'] === 'asc' ? 'desc' : 'asc')
            : (in_array($column, ['created_at', 'priority'], true) ? 'desc' : 'asc');

        return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $direction, 'page' => null]);
    };
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
            <a href="{{ route('tickets.index') }}" @click.prevent="clearFilters()"
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
    {{-- data-scroll-frame gets data-scrolled / data-more-right from ticket-list.js, which fade in
         the pinned column's shadow and the right-edge hint only when there is something to scroll to. --}}
    <div class="relative" data-scroll-frame>
        <div class="overflow-x-auto overscroll-x-contain [scrollbar-width:thin]" data-scroll-x>
            <table class="w-full text-[13px] text-gray-900">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200/70">
                        @foreach ($columns as $key => $column)
                            @php $active = $filters['sort'] === $key; @endphp
                            <th scope="col" class="{{ $column['class'] }} h-12 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-800 whitespace-nowrap"
                                @if ($active) aria-sort="{{ $filters['direction'] === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                @if ($column['sortable'] ?? true)
                                    <a href="{{ $sortUrl($key) }}" data-ajax class="group inline-flex items-center gap-1.5 hover:text-brand transition-colors">
                                        {{ $column['label'] }}
                                        <span class="inline-flex flex-col" aria-hidden="true">
                                            <i data-lucide="chevron-up" class="w-3 h-3 stroke-[3] transition-colors {{ $active ? ($filters['direction'] === 'asc' ? 'text-brand' : 'text-gray-300') : 'text-gray-400 group-hover:text-brand' }}"></i>
                                            <i data-lucide="chevron-down" class="w-3 h-3 -mt-[7px] stroke-[3] transition-colors {{ $active ? ($filters['direction'] === 'desc' ? 'text-brand' : 'text-gray-300') : 'text-gray-400 group-hover:text-brand' }}"></i>
                                        </span>
                                    </a>
                                @else
                                    {{ $column['label'] }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($tickets as $ticket)
                        @php
                            $priorityLabel = TicketPriority::tryFrom($ticket->priority)?->label() ?? ucfirst($ticket->priority);
                            $categoryLabel = TicketCategory::tryFrom((string) $ticket->category)?->shortLabel() ?? '—';
                        @endphp
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="pl-5 pr-4 py-4 whitespace-nowrap tabular-nums {{ $sticky }} bg-white group-hover:bg-gray-50">
                                {{ $ticket->ticket_number }}
                            </td>

                            @if ($showRequester)
                                <td class="px-3 py-4 whitespace-nowrap">{{ $ticket->requester?->name ?? 'Unknown' }}</td>
                            @endif

                            {{-- One line like the design. line-clamp (not truncate) keeps the column able to shrink
                                 to its min width before the table scrolls; the full title shows on hover. --}}
                            <td class="px-3 py-4">
                                <div class="min-w-32 max-w-xs">
                                    @if ($canOpen)
                                        <a href="{{ route('tickets.show', $ticket) }}" class="line-clamp-1 break-all text-brand hover:text-brand-dark hover:underline underline-offset-2" title="{{ $ticket->title }}">{{ $ticket->title }}</a>
                                    @else
                                        <p class="line-clamp-1 break-all text-brand" title="{{ $ticket->title }}">{{ $ticket->title }}</p>
                                    @endif
                                </div>
                            </td>

                            <td class="px-3 py-4 whitespace-nowrap">{{ $categoryLabel }}</td>

                            <td class="px-3 py-4 whitespace-nowrap">{{ $priorityLabel }}</td>

                            <td class="px-3 py-4 whitespace-nowrap">{{ $ticket->assignedTo?->name ?? 'Not assigned' }}</td>

                            <td class="px-3 py-4 whitespace-nowrap">
                                @include('partials.status-badge', ['status' => $ticket->status, 'variant' => 'outline'])
                            </td>

                            <td class="pl-3 pr-5 py-4 whitespace-nowrap tabular-nums">
                                <time datetime="{{ $ticket->created_at->toIso8601String() }}">{{ $ticket->created_at->format('Y-m-d H:i') }}</time>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="ticket-scroll-fade pointer-events-none absolute inset-y-0 right-0 w-10 bg-gradient-to-l from-white" aria-hidden="true"></div>
    </div>

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
