@php
// One colour family per status, two looks: 'soft' (pastel fill, the default) and
// 'outline' (coloured border on white, used by the ticket table per design/screenshots/ticketTable.png).
$styles = [
    'pending_line_manager_approval' => ['soft' => 'bg-amber-100 text-amber-700', 'outline' => 'border-amber-400 text-amber-700'],
    'returned' => ['soft' => 'bg-orange-100 text-orange-700', 'outline' => 'border-orange-400 text-orange-700'],
    'pending_assignment' => ['soft' => 'bg-blue-100 text-blue-700', 'outline' => 'border-blue-400 text-blue-700'],
    'assigned' => ['soft' => 'bg-blue-100 text-blue-700', 'outline' => 'border-blue-400 text-blue-700'],
    'in_progress' => ['soft' => 'bg-sky-100 text-sky-700', 'outline' => 'border-sky-400 text-sky-700'],
    'resolved' => ['soft' => 'bg-green-100 text-green-700', 'outline' => 'border-green-500 text-green-700'],
    'closed' => ['soft' => 'bg-gray-100 text-gray-600', 'outline' => 'border-gray-300 text-gray-600'],
    'reopened' => ['soft' => 'bg-red-100 text-red-700', 'outline' => 'border-red-400 text-red-700'],
];
$variant = ($variant ?? 'soft') === 'outline' ? 'outline' : 'soft';
$class = $styles[$status][$variant] ?? $styles['closed'][$variant];
$shape = $variant === 'outline'
    ? 'border bg-white text-xs font-medium px-2.5 py-1'
    : 'text-[11px] font-semibold px-2.5 py-1';
$label = \App\Enums\TicketStatus::tryFrom($status)?->label() ?? Str::headline($status);
@endphp
<span class="{{ $class }} {{ $shape }} inline-flex items-center rounded-full shrink-0 whitespace-nowrap">
    {{ $label }}
</span>
