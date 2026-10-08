<?php

namespace App\Repositories;

use App\Models\Setting;

/**
 * The key/value `settings` table (docs/06-data-model.md): standing, admin-configurable state.
 */
class SettingRepository
{
    public function get(string $key, ?string $default = null): ?string
    {
        return Setting::query()->where('key', $key)->value('value') ?? $default;
    }

    public function set(string $key, string $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
