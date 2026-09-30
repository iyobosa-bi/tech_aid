<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo account per role. Safe to re-run: existing accounts (matched by
 * email, including soft-deleted ones) are never recreated and their password
 * is never touched — new accounts get the shared test password.
 */
class DemoUserSeeder extends Seeder
{
    public const PASSWORD = 'Test1234@@@';

    public function run(): void
    {
        $lineManager = $this->user('linemanager@example.com', 'Lara Manager', RoleName::LineManager, 'Operations');

        $requester = $this->user('test@example.com', 'Test User', RoleName::Requester, 'Operations');
        $requester->update(['line_manager_id' => $lineManager->id]);

        $this->user('hosm@example.com', 'Hassan Service-Lead', RoleName::HeadOfServiceManagement, 'Technology');
        $this->user('support@example.com', 'Sade Support', RoleName::ApplicationSupport, 'Technology');
        $this->user('admin@example.com', 'Ade Admin', RoleName::Admin, 'Technology');
    }
    
    
    private function user(string $email, string $name, RoleName $role, string $department): User
    {
        $user = User::withTrashed()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => self::PASSWORD,
                'department' => $department,
            ],
        );

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $user->assignRole($role->value);

        return $user;
    }
}
