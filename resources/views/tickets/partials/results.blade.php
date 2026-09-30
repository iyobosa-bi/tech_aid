@php
    $hasFilters = $filters['search'] !== '' || $filters['status'];
    $canOpen = Route::has('tickets.show');

    $columns = [
        'id' => ['label' => 'Ticket', 'class' => 'pl-5 pr-3'],
        'requester' => ['label' => 'Requester', 'class' => 'px-3 hidden md:table-cell'],
        'title' => ['label' => 'Title', 'class' => 'px-3', 'sortable' => false],
        'status' => ['label' => 'Status', 'class' => 'px-3'],
        'created_at' => ['label' => 'Opened', 'class' => 'pl-3 pr-5 hidden sm:table-cell'],
    ];

    $sortUrl = function (string $column) use ($filters) {
        $direction = $filters['sort'] === $column
            ? ($filters['direction'] === 'asc' ? 'desc' : 'asc')
            : ($column === 'created_at' ? 'desc' : 'asc');

        return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $direction, 'page' => null]);
    };

    $priorityDot = ['high' => 'bg-red-500', 'medium' => 'bg-amber-400', 'low' => 'bg-gray-300'];
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
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/60">
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
                        $initials = collect(explode(' ', $requesterName))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
                    @endphp
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="pl-5 pr-3 py-3.5 whitespace-nowrap text-xs font-medium text-gray-400 tabular-nums">
                            {{ $ticket->ticket_number }}
                        </td>
                        <td class="px-3 py-3.5 whitespace-nowrap hidden md:table-cell">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-full bg-brand/10 text-brand text-[10px] font-semibold flex items-center justify-center shrink-0">{{ $initials }}</span>
                                <span class="text-gray-700">{{ $requesterName }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-3.5 w-full max-w-0">
                            @if ($canOpen)
                                <a href="{{ route('tickets.show', $ticket) }}" class="block truncate font-medium text-gray-800 hover:text-brand" title="{{ $ticket->title }}">{{ $ticket->title }}</a>
                            @else
                                <p class="truncate font-medium text-gray-800" title="{{ $ticket->title }}">{{ $ticket->title }}</p>
                            @endif
                            <p class="flex items-center gap-2 mt-0.5 text-[11px] text-gray-400">
                                <span>{{ ucfirst($ticket->category ?? 'General') }}</span>
                                <span class="text-gray-300">·</span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $priorityDot[$ticket->priority] ?? 'bg-gray-300' }}"></span>
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                                <span class="md:hidden text-gray-300">·</span>
                                <span class="md:hidden truncate">{{ $requesterName }}</span>
                            </p>
                        </td>
                        <td class="px-3 py-3.5 whitespace-nowrap">
                            @include('partials.status-badge', ['status' => $ticket->status])
                        </td>
                        <td class="pl-3 pr-5 py-3.5 whitespace-nowrap hidden sm:table-cell">
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
