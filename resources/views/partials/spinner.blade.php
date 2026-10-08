{{--
    The busy indicator for every button's loading state — always this ring next to the label,
    never "…" text (design/style-notes.md, Buttons). A plain CSS ring, so it also works on guest
    pages, which don't load Lucide.
    @include('partials.spinner')                      white ring, for brand/red buttons
    @include('partials.spinner', ['tone' => 'brand']) brand ring, for white/outlined buttons
    @include('partials.spinner', ['size' => 'sm'])    smaller, for text links
--}}
@php
    $ring = ($tone ?? 'white') === 'brand' ? 'border-brand/30 border-t-brand' : 'border-white/30 border-t-white';
    $box = ($size ?? 'md') === 'sm' ? 'w-3 h-3' : 'w-4 h-4';
@endphp
<span class="{{ $box }} shrink-0 rounded-full border-2 {{ $ring }} animate-spin" aria-hidden="true"></span>
