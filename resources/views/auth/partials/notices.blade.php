{{-- Success ("status") and problem ("error") messages on the signed-out cards, e.g. "Too many requests". --}}
@if (session('status'))
    <div role="status" class="mt-6 flex items-start gap-2 px-3 py-2.5 border border-green-200 bg-green-50 rounded-lg">
        <i data-lucide="circle-check" class="w-4 h-4 mt-0.5 text-green-600 shrink-0"></i>
        <p class="text-sm font-medium leading-5 text-green-800">{{ session('status') }}</p>
    </div>
@endif
@if (session('error'))
    <div role="alert" class="mt-6 flex items-start gap-2 px-3 py-2.5 border border-red-200 bg-red-50 rounded-lg">
        <i data-lucide="circle-alert" class="w-4 h-4 mt-0.5 text-red-600 shrink-0"></i>
        <p class="text-sm font-semibold leading-5 text-red-700">{{ session('error') }}</p>
    </div>
@endif
