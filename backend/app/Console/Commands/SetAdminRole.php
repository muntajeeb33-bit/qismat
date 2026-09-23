<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetAdminRole extends Command
{
    protected $signature = 'qismat:admin
        {email : Existing Qismat account email}
        {--remove : Restore the account to the member role}
        {--force : Skip the production confirmation}';

    protected $description = 'Grant or remove the Qismat administrator role';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $this->error('No Qismat account exists for this email. Sign in once before assigning the role.');

            return self::FAILURE;
        }

        $role = $this->option('remove') ? 'member' : 'admin';
        if (app()->isProduction() && ! $this->option('force')
            && ! $this->confirm("Set {$user->email} to the {$role} role?")) {
            $this->warn('No changes were made.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => $role])->save();
        $user->tokens()->delete();
        $this->info("{$user->email} now has the {$role} role. Existing API sessions were revoked.");

        return self::SUCCESS;
    }
}
