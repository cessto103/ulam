<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetAdminTwoFactor extends Command
{
    protected $signature = 'admin:reset-2fa {email : Email of the locked-out admin}';

    protected $description = 'Emergency 2FA reset for an admin locked out of their authenticator (server access = ownership proof)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('No user with that email.');
            return self::FAILURE;
        }

        if (!$user->isAdmin()) {
            $this->error('That user is not an admin — this command only resets admin accounts.');
            return self::FAILURE;
        }

        if (!$user->twofa_enabled_at) {
            $this->info('2FA is not enabled on that account — nothing to reset.');
            return self::SUCCESS;
        }

        $user->update(['twofa_secret' => null, 'twofa_enabled_at' => null, 'twofa_last_ts' => null]);
        // Kill existing dashboard sessions too — if the phone was stolen rather
        // than lost, a live session shouldn't outlive the reset.
        $user->tokens()->where('name', 'admin-panel')->delete();

        $this->info("2FA reset for {$user->email}. They can log in with password only — re-enroll immediately in Settings → Security.");

        return self::SUCCESS;
    }
}
