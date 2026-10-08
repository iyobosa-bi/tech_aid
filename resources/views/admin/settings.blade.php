@extends('layouts.app', ['pageTitle' => 'System settings', 'title' => 'System settings — Tech Aid'])

{{-- Admin → System settings (design/style-notes.md, "Admin — System settings"). SettingsController. --}}
@section('content')
<div class="max-w-5xl space-y-6">

    {{-- Ticket assignment (Flow 5) --}}
    <section class="bg-white border border-gray-100 rounded-xl shadow-sm">
        <div class="px-5 sm:px-6 py-5 flex flex-col md:flex-row md:items-start md:justify-between gap-5">
            <div class="flex gap-4 min-w-0">
                <span class="w-10 h-10 rounded-lg bg-brand/10 text-brand flex items-center justify-center shrink-0">
                    <i data-lucide="shuffle" class="w-5 h-5"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="font-display font-semibold text-gray-900">Auto-assign approved tickets</h2>
                    <p class="text-sm text-gray-600 mt-1 leading-relaxed max-w-2xl">
                        When this is on, a ticket goes to Application Support the moment its line manager approves it, to
                        whoever in the bucket below has the <span class="font-medium text-gray-900">fewest open tickets</span>
                        (if two are level, whoever was given a ticket longest ago). When it's off, Head of Service Management
                        assigns each ticket by hand. Either way, Head of Service Management can reassign.
                    </p>
                    @if ($autoAssign && $availableCount === 0)
                        <p class="mt-3 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900">
                            <i data-lucide="triangle-alert" class="w-4 h-4 mt-0.5 shrink-0"></i>
                            Nobody in Application Support is available, so approved tickets will wait for Head of Service Management.
                        </p>
                    @elseif ($autoAssign && $nextUp)
                        <p class="mt-3 text-sm text-gray-600">
                            Next ticket goes to <span class="font-semibold text-gray-900">{{ $nextUp->name }}</span>
                            ({{ $nextUp->open_tickets_count }} open {{ Str::plural('ticket', $nextUp->open_tickets_count) }}).
                        </p>
                    @endif
                </div>
            </div>
            <div class="shrink-0">
                @include('admin.partials.switch', [
                    'action' => route('admin.settings.auto-assign'),
                    'field' => 'enabled',
                    'on' => $autoAssign,
                    'label' => 'Auto-assign approved tickets',
                    'onText' => 'On',
                    'offText' => 'Off',
                ])
            </div>
        </div>
    </section>

    {{-- The bucket: active Application Support staff; on-leave staff are skipped by auto-assign. --}}
    <section class="bg-white border border-gray-100 rounded-xl shadow-sm">
        <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-display font-semibold text-gray-900">Application Support bucket</h2>
                <p class="text-xs text-gray-500 mt-0.5">Mark someone on leave to stop auto-assign from giving them tickets. Tickets they already have stay with them.</p>
            </div>
            <p class="text-sm text-gray-700">
                <span class="font-semibold text-gray-900 tabular-nums">{{ $availableCount }} of {{ $staff->count() }}</span> available for auto-assign
            </p>
        </div>

        @if ($staff->isEmpty())
            <p class="px-6 py-10 text-center text-sm text-gray-500">There's nobody in Application Support yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-sm">
                    <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wide text-gray-800">
                        <tr>
                            <th scope="col" class="px-5 sm:px-6 py-3 font-semibold">Name</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Email</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Open tickets</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                            <th scope="col" class="px-5 sm:px-6 py-3 font-semibold">On leave</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($staff as $person)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 sm:px-6 py-3.5 font-medium text-gray-900 whitespace-nowrap">{{ $person->name }}</td>
                                <td class="px-4 py-3.5 text-gray-700">{{ $person->email }}</td>
                                <td class="px-4 py-3.5 text-gray-900 tabular-nums">{{ $person->open_tickets_count }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $person->on_leave ? 'bg-gray-100 text-gray-600' : 'bg-green-100 text-green-700' }}">
                                        {{ $person->on_leave ? 'On leave' : 'Available' }}
                                    </span>
                                </td>
                                <td class="px-5 sm:px-6 py-3.5">
                                    @include('admin.partials.switch', [
                                        'action' => route('admin.support.availability', $person->id),
                                        'field' => 'on_leave',
                                        'on' => $person->on_leave,
                                        'label' => "{$person->name} is on leave",
                                        'onText' => 'Yes',
                                        'offText' => 'No',
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Coming later: bulk staff import from Excel. --}}
    <section class="bg-white border border-gray-100 rounded-xl shadow-sm opacity-70" aria-disabled="true">
        <div class="px-5 sm:px-6 py-5 flex gap-4">
            <span class="w-10 h-10 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center shrink-0">
                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
            </span>
            <div>
                <h2 class="font-display font-semibold text-gray-900 flex items-center gap-2">
                    Bulk import staff
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium bg-gray-100 text-gray-600">Coming soon</span>
                </h2>
                <p class="text-sm text-gray-600 mt-1">Upload an Excel sheet of staff with their roles and line managers to create their accounts in one go.</p>
            </div>
        </div>
    </section>
</div>
@endsection
