@php
$colors = [
    'pending_line_manager_approval' => 'bg-amber-100 text-amber-700',
    'returned' => 'bg-orange-100 text-orange-700',
    'pending_assignment' => 'bg-blue-100 text-blue-700',
    'assigned' => 'bg-blue-100 text-blue-700',
    'in_progress' => 'bg-sky-100 text-sky-700',
    'resolved' => 'bg-green-100 text-green-700',
    'closed' => 'bg-gray-100 text-gray-600',
    'reopened' => 'bg-red-100 text-red-700',
];
$labels = [
    'pending_line_manager_approval' => 'Pending',
    'returned' => 'Returned',
    'pending_assignment' => 'Pending Assignment',
    'assigned' => 'Assigned',
    'in_progress' => 'In Progress',
    'resolved' => 'Resolved',
    'closed' => 'Closed',
    'reopened' => 'Reopened',
];
$class = $colors[$status] ?? 'bg-gray-100 text-gray-600';
$label = $labels[$status] ?? Str::headline($status);
@endphp
<span class="{{ $class }} text-[11px] font-semibold px-2.5 py-1 rounded-full shrink-0">
    {{ $label }}
</span>
