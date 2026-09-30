@php
    $flashStyles = [
        'success' => ['icon' => 'check-circle', 'box' => 'bg-green-50 border-green-200', 'text' => 'text-green-700', 'accent' => 'text-green-600'],
        'error' => ['icon' => 'alert-circle', 'box' => 'bg-red-50 border-red-200', 'text' => 'text-red-700', 'accent' => 'text-red-600'],
    ];
@endphp
@foreach ($flashStyles as $key => $style)
    @if (session($key))
        <div x-data="{ show: true }" x-show="show" role="{{ $key === 'error' ? 'alert' : 'status' }}"
             class="mb-6 flex items-start gap-3 px-4 py-3 border rounded-lg {{ $style['box'] }}">
            <i data-lucide="{{ $style['icon'] }}" class="w-4 h-4 mt-0.5 shrink-0 {{ $style['accent'] }}"></i>
            <p class="flex-1 text-sm {{ $style['text'] }}">{{ session($key) }}</p>
            <button type="button" @click="show = false" aria-label="Dismiss" class="{{ $style['accent'] }} opacity-60 hover:opacity-100">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif
@endforeach
