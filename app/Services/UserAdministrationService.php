<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

/**
 * Admin → Users: switching accounts off and on, and deleting them. UserPolicy decides who may.
 *
 * - Deactivate (reversible): the person can't sign in, is signed out on their next request
 *   (EnsureAccountIsActive), and is never offered for assignment. Their tickets stay as they are.
 * - Delete: a soft delete — the account is gone for good from the app, but the row stays so
 *   tickets and the audit trail keep their name, and the email can be reused for a new account.
 *   Their unused one-time codes are cancelled.
 *
 * Every change goes to the activity log with who did it.
 */
class UserAdministrationService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly OtpService $otps,
    ) {}

    public function setActive(User $target, bool $active, User $admin): void
    {
        $this->users->setActive($target, $active);

        Log::channel('activity')->info($active ? 'User reactivated' : 'User deactivated', $this->context($target, $admin));
    }

    public function delete(User $target, User $admin): void
    {
        $this->otps->invalidate($target);
        $this->users->delete($target);

        Log::channel('activity')->info('User deleted', $this->context($target, $admin));
    }

    /**
     * @return array{target_user_id: int, target_username: string, actor_id: int, actor_name: string}
     */
    private function context(User $target, User $admin): array
    {
        return [
            'target_user_id' => $target->id,
            'target_username' => $target->username(),
            'actor_id' => $admin->id,
            'actor_name' => $admin->username(),
        ];
    }
}
