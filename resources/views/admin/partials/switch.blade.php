{{--
    An on/off switch that saves as soon as it's clicked (design/style-notes.md, "Admin — System
    settings"). It posts the *opposite* of the current value, then the page reloads with a flash.
    Params: action, field, on (bool), label (for screen readers), onText, offText.
--}}
<form method="POST" action="{{ $action }}" x-data="{ busy: false }" @submit="busy = true" class="inline-flex items-center gap-2.5">
    @csrf
    @method('PUT')
    <input type="hidden" name="{{ $field }}" value="{{ $on ? 0 : 1 }}">
    <button type="submit" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $label }}" :disabled="busy"
            class="relative w-11 h-6 shrink-0 rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-brand/40 disabled:cursor-wait {{ $on ? 'bg-brand' : 'bg-gray-300' }}">
        <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform {{ $on ? 'translate-x-5' : '' }}"></span>
    </button>
    <span x-show="!busy" class="text-sm font-medium {{ $on ? 'text-gray-900' : 'text-gray-500' }}">{{ $on ? $onText : $offText }}</span>
    <span x-show="busy" x-cloak class="inline-flex items-center gap-1.5 text-sm text-gray-500" role="status">
        @include('partials.spinner', ['tone' => 'brand', 'size' => 'sm']) Saving
    </span>
</form>
