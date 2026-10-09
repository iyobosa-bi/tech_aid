@extends('layouts.app', ['pageTitle' => 'Users', 'title' => 'Users — Tech Aid'])

{{-- Admin → Users (design/style-notes.md, "Admin — Users"). Admin\UserController; UserPolicy decides the buttons. --}}
@section('content')
@php
    $initials = fn (string $name) => collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
    $hasFilters = $filters['search'] !== '' || $filters['role'] || $filters['status'];
    $btn = 'inline-flex items-center justify-center gap-1.5 rounded-lg font-display font-semibold text-xs px-3 py-1.5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40';
    $modalBtn = 'inline-flex items-center justify-center gap-2 rounded-lg font-display font-semibold text-sm px-4 py-2.5 transition-colors disabled:opacity-60 disabled:cursor-wait focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40';
@endphp

<div x-data="userAdmin()" class="relative rounded-xl bg-brand/[0.06] p-3">
    <form method="GET" action="{{ route('admin.users') }}" role="search" class="flex flex-col lg:flex-row lg:items-center gap-3 px-1 pt-1 pb-4">
        <div class="lg:mr-4">
            <h2 class="font-display font-semibold text-sm text-gray-900 whitespace-nowrap">All users</h2>
            <p class="text-xs text-gray-600 mt-0.5 tabular-nums">
                {{ $counts['total'] }} {{ Str::plural('user', $counts['total']) }} · {{ $counts['deactivated'] }} deactivated
            </p>
        </div>

        <div class="flex gap-2 flex-1 lg:max-w-md">
            <div class="relative flex-1 min-w-0">
                <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-brand pointer-events-none"></i>
                <input type="search" name="search" value="{{ $filters['search'] }}" maxlength="100" placeholder="Search by name or email" aria-label="Search users"
                       class="w-full h-11 pl-10 pr-3 rounded-lg border border-white bg-white text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand" />
            </div>
            <button type="submit" class="h-11 px-5 rounded-lg bg-brand hover:bg-brand-dark text-white font-display font-semibold text-sm shadow-sm transition-colors">Search</button>
        </div>

        <div class="flex gap-2 w-full lg:w-auto lg:ml-auto">
            <select name="role" onchange="this.form.submit()" aria-label="Filter by role"
                    class="h-11 min-w-0 flex-1 lg:flex-none lg:w-52 rounded-lg border border-brand bg-white pl-3 pr-8 text-sm {{ $filters['role'] ? 'text-brand font-medium' : 'text-gray-500' }} focus:outline-none focus:ring-2 focus:ring-brand/20">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" class="text-gray-900" @selected($filters['role'] === $role->value)>{{ $role->value }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" aria-label="Filter by status"
                    class="h-11 min-w-0 flex-1 lg:flex-none lg:w-40 rounded-lg border border-brand bg-white pl-3 pr-8 text-sm {{ $filters['status'] ? 'text-brand font-medium' : 'text-gray-500' }} focus:outline-none focus:ring-2 focus:ring-brand/20">
                <option value="">All statuses</option>
                <option value="active" class="text-gray-900" @selected($filters['status'] === 'active')>Active</option>
                <option value="deactivated" class="text-gray-900" @selected($filters['status'] === 'deactivated')>Deactivated</option>
            </select>
        </div>
    </form>

    <div class="bg-white rounded-lg border border-gray-100 shadow-sm overflow-hidden">
        @if ($users->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="w-12 h-12 rounded-xl bg-brand/[0.06] flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="search-x" class="w-5 h-5 text-brand"></i>
                </div>
                <p class="text-sm font-semibold text-gray-900">No matching users</p>
                @if ($hasFilters)
                    <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-1.5 mt-4 text-sm font-semibold text-brand hover:text-brand-dark">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Clear filters
                    </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[60rem] text-[13px] text-gray-900">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200/70 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-800">
                            <th scope="col" class="pl-5 pr-3 h-12">User</th>
                            <th scope="col" class="px-3 h-12">Role</th>
                            <th scope="col" class="px-3 h-12">Department</th>
                            <th scope="col" class="px-3 h-12">Line manager</th>
                            <th scope="col" class="px-3 h-12">Status</th>
                            <th scope="col" class="px-3 h-12">Joined</th>
                            <th scope="col" class="pl-3 pr-5 h-12 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $person)
                            @php
                                $isMe = $person->is(auth()->user());
                                $role = $person->roles->first()?->name ?? '—';
                                // Shown in the confirmation: what deactivating or deleting this person affects.
                                $warnings = array_values(array_filter([
                                    $person->direct_reports_count ? "Line manager for {$person->direct_reports_count} ".Str::plural('person', $person->direct_reports_count).' — their new tickets can\'t be approved until they get a new line manager.' : null,
                                    $person->open_tickets_count ? "{$person->open_tickets_count} open ".Str::plural('ticket', $person->open_tickets_count).' assigned to them — Head of Service Management should reassign '.($person->open_tickets_count === 1 ? 'it' : 'them').'.' : null,
                                ]));
                                $target = ['name' => $person->name, 'warnings' => $warnings,
                                    'statusUrl' => route('admin.users.status', $person), 'deleteUrl' => route('admin.users.destroy', $person)];
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors {{ $person->isActive() ? '' : 'bg-gray-50/60' }}">
                                <td class="pl-5 pr-3 py-3.5">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 text-xs font-semibold {{ $person->isActive() ? 'bg-teal/15 text-teal-800' : 'bg-gray-100 text-gray-500' }}">{{ $initials($person->name) }}</span>
                                        <div class="min-w-0">
                                            <p class="flex items-center gap-1.5 font-semibold {{ $person->isActive() ? 'text-gray-900' : 'text-gray-500' }}">
                                                <span class="truncate">{{ $person->name }}</span>
                                                @if ($isMe) <span class="rounded-full px-2 py-0.5 text-[10px] font-medium bg-brand/10 text-brand shrink-0">You</span> @endif
                                            </p>
                                            <p class="text-xs text-gray-500 truncate">{{ $person->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3.5 whitespace-nowrap">{{ $role }}</td>
                                <td class="px-3 py-3.5 whitespace-nowrap">{{ $person->department ?? '—' }}</td>
                                <td class="px-3 py-3.5 whitespace-nowrap">{{ $person->lineManager?->name ?? '—' }}</td>
                                <td class="px-3 py-3.5 whitespace-nowrap">
                                    @if (! $person->isActive())
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold bg-gray-100 text-gray-600" title="Deactivated {{ $person->deactivated_at->format('M j, Y') }}">Deactivated</span>
                                    @elseif ($person->on_leave)
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold bg-amber-100 text-amber-700">On leave</span>
                                    @else
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold bg-green-100 text-green-700">Active</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3.5 whitespace-nowrap tabular-nums text-gray-700">{{ $person->created_at->format('M j, Y') }}</td>
                                <td class="pl-3 pr-5 py-3 whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('deactivate', $person)
                                            <button type="button" @click="open('deactivate', @js($target))" class="{{ $btn }} border border-gray-200 bg-white text-gray-700 hover:border-amber-400 hover:text-amber-700">
                                                <i data-lucide="user-x" class="w-3.5 h-3.5"></i> Deactivate
                                            </button>
                                        @endcan
                                        @can('activate', $person)
                                            <button type="button" @click="open('activate', @js($target))" class="{{ $btn }} border border-brand bg-white text-brand hover:bg-brand/5">
                                                <i data-lucide="user-check" class="w-3.5 h-3.5"></i> Activate
                                            </button>
                                        @endcan
                                        @can('delete', $person)
                                            <button type="button" @click="open('delete', @js($target))" aria-label="Delete {{ $person->name }}" title="Delete account"
                                                    class="w-8 h-8 rounded-lg border border-gray-200 bg-white flex items-center justify-center text-gray-500 hover:border-red-300 hover:text-red-600 hover:bg-red-50 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400/40">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        @endcan
                                        @if ($isMe)
                                            <span class="text-xs text-gray-400">Your account</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between gap-3 px-5 py-3.5 border-t border-gray-100">
                <p class="text-sm text-gray-700">
                    Showing <span class="font-semibold text-gray-900 tabular-nums">{{ $users->firstItem() }}–{{ $users->lastItem() }}</span>
                    of <span class="font-semibold text-gray-900 tabular-nums">{{ $users->total() }}</span>
                </p>
                @if ($users->hasPages())
                    @php
                        $pageLink = 'w-9 h-9 rounded-lg border flex items-center justify-center transition-colors';
                        $enabled = $pageLink.' border-gray-200 text-gray-800 hover:border-brand hover:text-brand';
                        $disabled = $pageLink.' border-gray-100 text-gray-300 cursor-not-allowed';
                    @endphp
                    <nav class="flex items-center gap-1.5" aria-label="Pagination">
                        @if ($users->onFirstPage())
                            <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevron-left" class="w-4 h-4"></i></span>
                        @else
                            <a href="{{ $users->previousPageUrl() }}" rel="prev" aria-label="Previous page" class="{{ $enabled }}"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
                        @endif
                        <span class="px-2 text-sm text-gray-800 tabular-nums">{{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
                        @if ($users->hasMorePages())
                            <a href="{{ $users->nextPageUrl() }}" rel="next" aria-label="Next page" class="{{ $enabled }}"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
                        @else
                            <span class="{{ $disabled }}" aria-disabled="true"><i data-lucide="chevron-right" class="w-4 h-4"></i></span>
                        @endif
                    </nav>
                @endif
            </div>
        @endif
    </div>

    {{-- One confirmation modal for all three actions; it posts a plain form. --}}
    <div x-show="action" x-cloak @keydown.escape.window="close()"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="user-action-title">
        <div class="absolute inset-0 bg-gray-900/50" @click="close()"></div>

        <form x-ref="form" method="POST" :action="action === 'delete' ? target.deleteUrl : target.statusUrl" @submit="submitting = true"
              class="relative w-full max-w-md bg-white rounded-xl shadow-2xl p-6">
            @csrf
            <template x-if="action === 'delete'"><input type="hidden" name="_method" value="DELETE"></template>
            <template x-if="action !== 'delete'"><input type="hidden" name="_method" value="PUT"></template>
            <input type="hidden" name="active" :value="action === 'activate' ? 1 : 0">

            <div class="w-11 h-11 rounded-full flex items-center justify-center mb-4"
                 :class="{ 'bg-amber-100 text-amber-700': action === 'deactivate', 'bg-red-100 text-red-600': action === 'delete', 'bg-brand/10 text-brand': action === 'activate' }">
                <span x-show="action === 'deactivate'"><i data-lucide="user-x" class="w-5 h-5"></i></span>
                <span x-show="action === 'delete'"><i data-lucide="trash-2" class="w-5 h-5"></i></span>
                <span x-show="action === 'activate'"><i data-lucide="user-check" class="w-5 h-5"></i></span>
            </div>

            <h3 id="user-action-title" class="font-display font-bold text-lg text-gray-900" x-text="title"></h3>
            <p class="text-sm text-gray-600 mt-1.5 leading-relaxed" x-text="description"></p>

            <template x-if="action !== 'activate' && target.warnings.length">
                <ul class="mt-4 space-y-2">
                    <template x-for="warning in target.warnings" :key="warning">
                        <li class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            <span x-text="warning"></span>
                        </li>
                    </template>
                </ul>
            </template>

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" @click="close()" :disabled="submitting" class="{{ $modalBtn }} border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" :disabled="submitting" class="{{ $modalBtn }} text-white"
                        :class="{ 'bg-amber-600 hover:bg-amber-700': action === 'deactivate', 'bg-red-600 hover:bg-red-700': action === 'delete', 'bg-brand hover:bg-brand-dark': action === 'activate' }">
                    <span x-show="!submitting" x-text="confirmLabel"></span>
                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-2" role="status">@include('partials.spinner') <span x-text="busyLabel"></span></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Admin → Users: one confirmation modal for Deactivate / Activate / Delete.
    function userAdmin() {
        const texts = {
            deactivate: {
                title: (name) => `Deactivate ${name}?`,
                description: () => 'They\'ll be signed out straight away and can\'t sign in until you activate the account again. Their tickets and history stay as they are.',
                confirm: 'Deactivate', busy: 'Deactivating',
            },
            activate: {
                title: (name) => `Activate ${name}?`,
                description: () => 'They\'ll be able to sign in again with their existing password (and a login code, as usual).',
                confirm: 'Activate', busy: 'Activating',
            },
            delete: {
                title: (name) => `Delete ${name}'s account?`,
                description: () => 'They won\'t be able to sign in again, and their email can be used for a new account. Their tickets and history are kept for the audit trail. This can\'t be undone here.',
                confirm: 'Delete account', busy: 'Deleting',
            },
        };

        return {
            action: null,
            target: { name: '', warnings: [], statusUrl: '', deleteUrl: '' },
            submitting: false,

            get title() { return this.action ? texts[this.action].title(this.target.name) : ''; },
            get description() { return this.action ? texts[this.action].description() : ''; },
            get confirmLabel() { return this.action ? texts[this.action].confirm : ''; },
            get busyLabel() { return this.action ? texts[this.action].busy : ''; },

            open(action, target) {
                this.target = target;
                this.action = action;
                this.$nextTick(() => lucide.createIcons());
            },

            close() {
                if (!this.submitting) this.action = null;
            },
        };
    }
</script>
@endpush
