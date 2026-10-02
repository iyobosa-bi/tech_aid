{{--
    Shared ticket table: the Tickets page (sortable, inside live search) and the dashboard's
    Recent Tickets (static). Expects $tickets and $showRequester; pass 'sortable' => true
    together with $filters to get sort links in the headers.
--}}
@use('App\Enums\TicketCategory')
@use('App\Enums\TicketPriority')
@include('tickets.partials.table-assets')
@php
    $sortable ??= false;
    $filters ??= null; // only read when sortable
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

<div class="relative" x-data="ticketTableScroll()" :class="{ 'is-scrolled': scrolled, 'has-more': moreRight }" @resize.window="sync()">
    <div class="overflow-x-auto overscroll-x-contain [scrollbar-width:thin]" x-ref="scroller" @scroll.passive="sync()">
        <table class="w-full text-[13px] text-gray-900">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200/70">
                    @foreach ($columns as $key => $column)
                        @php $active = $sortable && $filters['sort'] === $key; @endphp
                        <th scope="col" class="{{ $column['class'] }} h-12 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-800 whitespace-nowrap"
                            @if ($active) aria-sort="{{ $filters['direction'] === 'asc' ? 'ascending' : 'descending' }}" @endif>
                            @if ($sortable && ($column['sortable'] ?? true))
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
