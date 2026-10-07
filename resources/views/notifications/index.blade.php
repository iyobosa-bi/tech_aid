@extends('layouts.app', ['pageTitle' => 'Notifications', 'title' => 'Notifications — Tech Aid'])

{{--
    "All Notifications" (design/screenshots/notifications_2.png, Flow 10). Plain GET forms and
    links throughout — filters, page size and page number all live in the query string.
--}}
@php
    $filterQuery = array_filter(\Illuminate\Support\Arr::only($filters, ['status', 'type', 'from', 'to']));
    $field = 'w-full h-10 px-3 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand';
    $pageLink = 'w-9 h-9 rounded-lg flex items-center justify-center transition-colors';
    $enabled = $pageLink.' text-gray-800 hover:bg-gray-100 hover:text-brand';
    $disabled = $pageLink.' text-gray-300 cursor-not-allowed';
@endphp

@section('content')
<div class="rounded-xl bg-brand/[0.06] border border-gray-100 shadow-sm">
    <div class="px-5 sm:px-6 py-5">
        <h2 class="font-display font-bold text-2xl text-gray-900">Notifications</h2>
        <p class="text-sm text-gray-700 mt-1">View and manage all notifications.</p>
    </div>

    <div class="border-t border-brand/10 px-5 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-display font-bold text-lg text-gray-900">All Notifications</h3>

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" @disabled(! $unread)
                        class="h-10 px-4 rounded-lg text-sm font-medium text-brand hover:bg-white transition-colors disabled:text-gray-400 disabled:hover:bg-transparent disabled:cursor-not-allowed">
                    Mark all as Read
                </button>
            </form>

            <div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                        class="relative h-10 px-5 rounded-lg border border-brand bg-white text-sm font-medium text-brand hover:bg-brand/5 transition-colors">
                    Filters
                    @if ($activeFilters)
                        <span class="absolute -top-2 -right-2 min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[11px] font-bold flex items-center justify-center tabular-nums">
                            {{ $activeFilters }}<span class="sr-only"> active</span>
                        </span>
                    @endif
                </button>

                <form x-show="open" x-cloak @click.outside="open = false" method="GET" action="{{ route('notifications.index') }}"
                      x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1"
                      class="absolute right-0 mt-2 w-[min(18rem,calc(100vw-2.5rem))] z-20 bg-white rounded-xl border border-gray-100 shadow-xl p-4 space-y-3">
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">

                    <div>
                        <label for="filter-status" class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                        <select id="filter-status" name="status" class="{{ $field }}">
                            <option value="">All</option>
                            <option value="unread" @selected($filters['status'] === 'unread')>Unread</option>
                            <option value="read" @selected($filters['status'] === 'read')>Read</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-type" class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                        <select id="filter-type" name="type" class="{{ $field }}">
                            <option value="">All types</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter-from" class="block text-xs font-medium text-gray-700 mb-1">From</label>
                        <input id="filter-from" type="date" name="from" value="{{ $filters['from'] }}" class="{{ $field }}">
                    </div>
                    <div>
                        <label for="filter-to" class="block text-xs font-medium text-gray-700 mb-1">To</label>
                        <input id="filter-to" type="date" name="to" value="{{ $filters['to'] }}" class="{{ $field }}">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <a href="{{ route('notifications.index', ['per_page' => $filters['per_page']]) }}"
                           class="h-9 px-3 inline-flex items-center rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">Clear</a>
                        <button type="submit" class="h-9 px-4 rounded-lg bg-brand hover:bg-brand-dark text-white text-sm font-semibold transition-colors">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-b-xl overflow-hidden">
        @if ($notifications->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="w-12 h-12 rounded-xl bg-brand/[0.06] flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="{{ $activeFilters ? 'search-x' : 'bell-off' }}" class="w-5 h-5 text-brand"></i>
                </div>
                @if ($activeFilters)
                    <p class="text-sm font-semibold text-gray-900">No notifications match these filters</p>
                    <a href="{{ route('notifications.index', ['per_page' => $filters['per_page']]) }}"
                       class="inline-flex items-center gap-1.5 mt-4 text-sm font-semibold text-brand hover:text-brand-dark">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Clear filters
                    </a>
                @else
                    <p class="text-sm font-semibold text-gray-900">No notifications yet</p>
                    <p class="text-sm text-gray-500 mt-1">Updates on your tickets will show up here.</p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[36rem] text-sm">
                    <thead class="bg-gray-100 text-left text-[13px] text-gray-800">
                        <tr>
                            <th scope="col" class="px-5 sm:px-6 py-3.5 font-medium">Title</th>
                            <th scope="col" class="px-4 py-3.5 font-medium w-40">Date</th>
                            <th scope="col" class="px-4 sm:px-6 py-3.5 font-medium w-32">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($notifications as $notification)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 sm:px-6 py-4">
                                    <div class="flex items-start gap-2.5">
                                        @unless ($notification['read'])
                                            <span class="mt-1.5 w-2 h-2 rounded-full bg-red-500 shrink-0"><span class="sr-only">Unread</span></span>
                                        @endunless
                                        <span class="leading-relaxed break-words {{ $notification['read'] ? 'text-gray-700' : 'font-medium text-gray-900' }}">{{ $notification['message'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-gray-800 tabular-nums whitespace-nowrap">
                                    <time datetime="{{ $notification['datetime'] }}" title="{{ $notification['full_date'] }}">{{ $notification['date'] }}</time>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <a href="{{ $notification['url'] }}"
                                       class="inline-flex items-center justify-center h-9 px-4 rounded-md bg-brand hover:bg-brand-dark text-white text-sm font-semibold transition-colors">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Footer: page size on the left, page navigation on the right. --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 sm:px-6 py-3.5 border-t border-gray-100">
            <form method="GET" action="{{ route('notifications.index') }}" class="flex items-center gap-3">
                @foreach ($filterQuery as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <label for="per-page" class="text-sm text-gray-500">Results per page</label>
                <select id="per-page" name="per_page" @change="$el.form.requestSubmit()" x-data
                        class="h-9 pl-3 pr-8 rounded-md border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($filters['per_page'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>

            <nav class="flex items-center gap-1" aria-label="Pagination">
                @if ($notifications->onFirstPage())
                    <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevrons-left" class="w-4 h-4"></i></span>
                    <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevron-left" class="w-4 h-4"></i></span>
                @else
                    <a href="{{ $notifications->url(1) }}" aria-label="First page" class="{{ $enabled }}"><i data-lucide="chevrons-left" class="w-4 h-4"></i></a>
                    <a href="{{ $notifications->previousPageUrl() }}" rel="prev" aria-label="Previous page" class="{{ $enabled }}"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
                @endif

                <form method="GET" action="{{ route('notifications.index') }}" class="flex items-center gap-2 px-2">
                    @foreach ([...$filterQuery, 'per_page' => $filters['per_page']] as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <label for="page-number" class="text-sm text-gray-500">Page</label>
                    <select id="page-number" name="page" @change="$el.form.requestSubmit()" x-data
                            class="h-9 pl-3 pr-8 rounded-md border border-gray-300 bg-white text-sm text-gray-900 tabular-nums focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand">
                        @foreach (range(1, max(1, $notifications->lastPage())) as $page)
                            <option value="{{ $page }}" @selected($notifications->currentPage() === $page)>{{ $page }}</option>
                        @endforeach
                    </select>
                    <span class="text-sm text-gray-500 whitespace-nowrap">of {{ max(1, $notifications->lastPage()) }}</span>
                </form>

                @if ($notifications->hasMorePages())
                    <a href="{{ $notifications->nextPageUrl() }}" rel="next" aria-label="Next page" class="{{ $enabled }}"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
                    <a href="{{ $notifications->url($notifications->lastPage()) }}" aria-label="Last page" class="{{ $enabled }}"><i data-lucide="chevrons-right" class="w-4 h-4"></i></a>
                @else
                    <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevron-right" class="w-4 h-4"></i></span>
                    <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevrons-right" class="w-4 h-4"></i></span>
                @endif
            </nav>
        </div>
    </div>
</div>
@endsection
