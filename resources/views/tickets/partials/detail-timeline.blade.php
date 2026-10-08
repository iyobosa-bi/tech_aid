{{-- Status history: every status change from the audit trail, oldest first (plain messages are in the conversation). --}}
@use('App\Enums\TicketAction')
<section class="bg-white border border-gray-100 rounded-xl shadow-sm">
    <h3 class="px-5 py-4 border-b border-gray-100 text-sm font-semibold text-gray-900">Status history</h3>

    <ol class="px-5 py-5">
        @forelse ($timeline as $entry)
            @php $action = TicketAction::tryFrom($entry->action); @endphp
            <li class="relative flex gap-3 {{ $loop->last ? '' : 'pb-6' }}">
                @unless ($loop->last)
                    <span class="absolute left-4 top-9 bottom-0 w-px bg-gray-200" aria-hidden="true"></span>
                @endunless
                <span class="relative w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $action?->tone() ?? 'bg-gray-100 text-gray-600' }}">
                    <i data-lucide="{{ $action?->icon() ?? 'circle' }}" class="w-4 h-4"></i>
                </span>
                <div class="min-w-0 pt-0.5">
                    <p class="text-sm font-semibold text-gray-900">{{ $action?->label() ?? Str::headline($entry->action) }}</p>
                    <p class="text-xs text-gray-600">
                        @if ($entry->isSystem())
                            Tech Aid · Automatic
                        @else
                            {{ $entry->actor?->name ?? 'Former staff member' }}@if ($entry->actor_role) · {{ $entry->actor_role }}@endif
                        @endif
                    </p>
                    {{-- Assign / reassign: who it went to (and from). --}}
                    @if ($to = $people[$entry->meta['to_assignee_id'] ?? 0] ?? null)
                        <p class="text-xs text-gray-800 mt-0.5">
                            @if ($from = $people[$entry->meta['from_assignee_id'] ?? 0] ?? null)
                                From {{ $from }} to <span class="font-semibold">{{ $to }}</span>
                            @else
                                To <span class="font-semibold">{{ $to }}</span>
                            @endif
                        </p>
                    @endif
                    <time class="block text-[11px] text-gray-500 mt-0.5" datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('M j, Y · H:i') }}</time>
                    @if ($entry->to_status && $entry->to_status !== $entry->from_status)
                        <div class="mt-1.5">@include('partials.status-badge', ['status' => $entry->to_status])</div>
                    @endif
                    @if ($entry->comment)
                        <p class="mt-1.5 text-xs text-gray-700 italic line-clamp-3 break-words">“{{ $entry->comment }}”</p>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-sm text-gray-500">No status changes recorded yet.</li>
        @endforelse
    </ol>
</section>
