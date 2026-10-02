{{-- Styles + script for tickets.partials.table, added once to whichever page includes it. --}}
@once
    @push('head')
        <style>
            /* Chrome skips box-shadow on cells of a border-collapse table, so the pinned
               column's edge is a pseudo-element. Classes are toggled by ticket-table.js. */
            .ticket-sticky { transition: background-color .15s ease; }
            .ticket-sticky::after {
                content: ''; position: absolute; top: 0; bottom: 0; right: -12px; width: 12px; pointer-events: none;
                border-left: 1px solid #e5e7eb; background: linear-gradient(to right, rgb(15 23 42 / .07), transparent);
                opacity: 0; transition: opacity .2s ease;
            }
            .is-scrolled .ticket-sticky::after { opacity: 1; }
            .ticket-scroll-fade { opacity: 0; transition: opacity .2s ease; }
            .has-more .ticket-scroll-fade { opacity: 1; }
        </style>
    @endpush
    @push('scripts')
        <script src="{{ asset('js/ticket-table.js') }}?v={{ filemtime(public_path('js/ticket-table.js')) }}"></script>
    @endpush
@endonce
