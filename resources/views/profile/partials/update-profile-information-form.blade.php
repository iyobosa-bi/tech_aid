{{-- Read-only: staff can't change their own name or email; an Admin manages them (Admin → Users). --}}
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Your name and email are managed by your administrator. Contact them if something needs changing.
        </p>
    </header>

    <div class="mt-6 space-y-6">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" type="text" class="mt-1 block w-full bg-gray-50 text-gray-500 cursor-not-allowed" :value="$user->name" disabled />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" class="mt-1 block w-full bg-gray-50 text-gray-500 cursor-not-allowed" :value="$user->email" disabled />
        </div>
    </div>
</section>
