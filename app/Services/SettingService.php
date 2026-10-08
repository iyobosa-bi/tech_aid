<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\SettingRepository;
use Illuminate\Support\Facades\Log;

/**
 * System settings managed by Admin (Admin → System settings).
 */
class SettingService
{
    // Flow 5: ON → a ticket is assigned to the least busy support person the moment it's approved.
    public const AUTO_ASSIGN = 'auto_assign_enabled';

    public function __construct(private readonly SettingRepository $settings) {}

    // OFF until an Admin turns it on.
    public function autoAssignEnabled(): bool
    {
        return $this->settings->get(self::AUTO_ASSIGN) === '1';
    }

    public function setAutoAssign(bool $enabled, User $admin): void
    {
        $this->settings->set(self::AUTO_ASSIGN, $enabled ? '1' : '0');

        Log::channel('activity')->info($enabled ? 'Auto-assign turned on' : 'Auto-assign turned off', [
            'actor_id' => $admin->id,
            'actor_name' => $admin->username(),
        ]);
    }
}
