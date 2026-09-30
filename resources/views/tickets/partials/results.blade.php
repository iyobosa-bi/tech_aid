@use('App\Enums\TicketCategory')
@use('App\Enums\TicketPriority')
@php
    $hasFilters = $filters['search'] !== '' || $filters['status'];
    $canOpen = Route::has('tickets.show');

    // Every column stays visible; narrow screens scroll the table sideways instead.
    // The ticket ID column is pinned so rows stay identifiable while scrolling.
    $sticky = 'sticky left-0 z-10 shadow-[1px_0_0_0_#f3f4f6]';

    $columns = array_filter([
        'id' => ['label' => 'Ticket', 'class' => "pl-5 pr-3.5 {$sticky} bg-gray-50"],
        'requester' => $showRequester ? ['label' => 'Requester', 'class' => 'px-3.5'] : null,
        'title' => ['label' => 'Title', 'class' => 'px-3.5', 'sortable' => false],
        'category' => ['label' => 'Category', 'class' => 'px-3.5'],
        'priority' => ['label' => 'Priority', 'class' => 'px-3.5'],
        'assignee' => ['label' => 'Assigned to', 'class' => 'px-3.5', 'sortable' => false],
        'status' => ['label' => 'Status', 'class' => 'px-3.5'],
        'created_at' => ['label' => 'Opened', 'class' => 'pl-3.5 pr-5'],
    ]);

    $sortUrl = function (string $column) use ($filters) {
        $direction = $filters['sort'] === $column
            ? ($filters['direction'] === 'asc' ? 'desc' : 'asc')
            : (in_array($column, ['created_at', 'priority'], true) ? 'desc' : 'asc');

        return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $direction, 'page' => null]);
    };

    $initials = fn (string $name) => collect(explode(' ', $name))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');

    $priorityStyles = [
        'high' => ['dot' => 'bg-red-500', 'text' => 'text-red-600'],
        'medium' => ['dot' => 'bg-amber-400', 'text' => 'text-amber-700'],
        'low' => ['dot' => 'bg-gray-300', 'text' => 'text-gray-500'],
    ];
@endphp

@if ($tickets->isEmpty())
    <div class="px-5 py-16 text-center">
        <div class="w-12 h-12 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="{{ $hasFilters ? 'search-x' : 'inbox' }}" class="w-5 h-5 text-gray-400"></i>
        </div>
        @if ($hasFilters)
            <p class="text-sm font-semibold text-gray-700">No matching tickets</p>
            <p class="text-xs text-gray-400 mt-1">
                @if ($filters['search'] !== '')
                    Nothing matches “{{ $filters['search'] }}”{{ $filters['status'] ? ' with that status' : '' }}.
                @else
                    No tickets have that status right now.
                @endif
            </p>
            <a href="{{ route('tickets.index') }}" @click.prevent="clearFilters()"
               class="inline-flex items-center gap-1.5 mt-4 text-xs font-semibold text-brand hover:text-brand-dark">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Clear filters
            </a>
        @else
            <p class="text-sm font-semibold text-gray-700">No tickets yet</p>
            <p class="text-xs text-gray-400 mt-1">Tickets you raise, approve or work on will show up here.</p>
            @can('create', App\Models\Ticket::class)
                <a href="{{ route('tickets.create') }}"
                   class="inline-flex items-center gap-2 mt-5 bg-brand hover:bg-brand-dark text-white font-display font-semibold text-xs px-4 py-2.5 rounded-lg transition-colors">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Raise a ticket
                </a>
            @endcan
        @endif
    </div>
@else
    {{-- Auto table layout: spare width is shared out; when space runs short the title wraps/shrinks first
         (every other column is nowrap), and only once it hits its min width does the wrapper scroll. --}}
    <div class="overflow-x-auto overscroll-x-contain [scrollbar-width:thin]">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    @foreach ($columns as $key => $column)
                        @php $active = $filters['sort'] === $key; @endphp
                        <th scope="col" class="{{ $column['class'] }} py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 whitespace-nowrap"
                            @if ($active) aria-sort="{{ $filters['direction'] === 'asc' ? 'ascending' : 'descending' }}" @endif>
                            @if ($column['sortable'] ?? true)
                                <a href="{{ $sortUrl($key) }}" data-ajax
                                   class="group inline-flex items-center gap-1 {{ $active ? 'text-brand' : 'hover:text-gray-600' }}">
                                    {{ $column['label'] }}
                                    @if ($active)
                                        <i data-lucide="{{ $filters['direction'] === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3"></i>
                                    @else
                                        <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40 group-hover:opacity-100"></i>
                                    @endif
                                </a>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach ($tickets as $ticket)
                    @php
                        $requesterName = $ticket->requester?->name ?? 'Unknown';
                        $assigneeName = $ticket->assignedTo?->name;
                        $priority = $priorityStyles[$ticket->priority] ?? $priorityStyles['low'];
                        $priorityLabel = TicketPriority::tryFrom($ticket->priority)?->label() ?? ucfirst($ticket->priority);
                        $categoryLabel = TicketCategory::tryFrom((string) $ticket->category)?->shortLabel() ?? '—';
                    @endphp
                    <tr class="group hover:bg-gray-50 transition-colors">
                        <td class="pl-5 pr-3.5 py-3.5 whitespace-nowrap text-xs font-medium text-gray-400 tabular-nums {{ $sticky }} bg-white group-hover:bg-gray-50 transition-colors">
                            {{ $ticket->ticket_number }}
                        </td>

                        @if ($showRequester)
                            <td class="px-3.5 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-brand/10 text-brand text-[10px] font-semibold flex items-center justify-center shrink-0">{{ $initials($requesterName) }}</span>
                                    <span class="text-gray-700">{{ $requesterName }}</span>
                                </div>
                            </td>
                        @endif

                        {{-- Bounded, wrapping title: shrinks to min-w before the table scrolls, never grows past max-w,
                             so spare width is shared by every column. Two lines max, full text on hover. --}}
                        <td class="px-3.5 py-3.5">
                            <div class="min-w-36 max-w-xs">
                                @if ($canOpen)
                                    <a href="{{ route('tickets.show', $ticket) }}" class="line-clamp-2 font-medium leading-snug text-gray-800 hover:text-brand" title="{{ $ticket->title }}">{{ $ticket->title }}</a>
                                @else
                                    <p class="line-clamp-2 font-medium leading-snug text-gray-800" title="{{ $ticket->title }}">{{ $ticket->title }}</p>
                                @endif
                            </div>
                        </td>

                        <td class="px-3.5 py-3.5 whitespace-nowrap text-gray-600">
                            {{ $categoryLabel }}
                        </td>

                        <td class="px-3.5 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $priority['text'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $priority['dot'] }}"></span>
                                {{ $priorityLabel }}
                            </span>
                        </td>

                        <td class="px-3.5 py-3.5 whitespace-nowrap">
                            @if ($assigneeName)
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-teal/15 text-teal-700 text-[10px] font-semibold flex items-center justify-center shrink-0">{{ $initials($assigneeName) }}</span>
                                    <span class="text-gray-700">{{ $assigneeName }}</span>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-gray-400">
                                    <span class="w-6 h-6 rounded-full border border-dashed border-gray-300 shrink-0"></span>
                                    <span class="text-xs">Not assigned</span>
                                </div>
                            @endif
                        </td>

                        <td class="px-3.5 py-3.5 whitespace-nowrap">
                            @include('partials.status-badge', ['status' => $ticket->status])
                        </td>

                        <td class="pl-3.5 pr-5 py-3.5 whitespace-nowrap">
                            <time datetime="{{ $ticket->created_at->toIso8601String() }}" class="block text-gray-700 tabular-nums">{{ $ticket->created_at->format('M j, Y') }}</time>
                            <span class="block text-[11px] text-gray-400 tabular-nums">{{ $ticket->created_at->format('H:i') }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between gap-3 px-5 py-3 border-t border-gray-100">
        <p class="text-xs text-gray-400">
            Showing <span class="font-medium text-gray-600 tabular-nums">{{ $tickets->firstItem() }}–{{ $tickets->lastItem() }}</span>
            of <span class="font-medium text-gray-600 tabular-nums">{{ $tickets->total() }}</span>
        </p>
        @if ($tickets->hasPages())
            <nav class="flex items-center gap-1" aria-label="Pagination">
                @php
                    $pageLink = 'w-8 h-8 rounded-lg border flex items-center justify-center transition-colors';
                    $enabled = $pageLink.' border-gray-200 text-gray-600 hover:border-brand hover:text-brand';
                    $disabled = $pageLink.' border-gray-100 text-gray-300 cursor-not-allowed';
                @endphp
                @if ($tickets->onFirstPage())
                    <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevron-left" class="w-4 h-4"></i></span>
                @else
                    <a href="{{ $tickets->previousPageUrl() }}" data-ajax rel="prev" aria-label="Previous page" class="{{ $enabled }}"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
                @endif
                <span class="px-2 text-xs text-gray-400 tabular-nums">{{ $tickets->currentPage() }} / {{ $tickets->lastPage() }}</span>
                @if ($tickets->hasMorePages())
                    <a href="{{ $tickets->nextPageUrl() }}" data-ajax rel="next" aria-label="Next page" class="{{ $enabled }}"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
                @else
                    <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevron-right" class="w-4 h-4"></i></span>
                @endif
            </nav>
        @endif
    </div>
@endif
