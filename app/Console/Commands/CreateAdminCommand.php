<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Creates or promotes the first administrator. Kept as a console command so
 * no password ever travels through a browser form or a seeder file.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'gtl:admin {email} {--name=Administrateur} {--role=super_admin}';

    protected $description = 'Create or promote a GoodTripLove administrator';

    public function handle(): int
    {
        $email = $this->argument('email');
        $role = $this->option('role');

        if (! in_array($role, ['admin', 'super_admin', 'moderator'], true)) {
            $this->error('Role must be moderator, admin or super_admin.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update(['role' => $role, 'is_active' => true]);
            $this->info("{$email} is now {$role}.");

            return self::SUCCESS;
        }

        $password = $this->secret('Password (min 10 characters, letters and numbers)');

        if (strlen((string) $password) < 10) {
            $this->error('Password too short.');

            return self::FAILURE;
        }

        User::create([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'locale' => config('goodtriplove.default_locale'),
            'is_active' => true,
        ])->forceFill(['email_verified_at' => now()])->save();

        $this->info("Administrator {$email} created with role {$role}.");

        return self::SUCCESS;
    }
}
