<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create (or update) the super-admin user.
     *
     * Credentials are read from environment variables so they are never
     * hard-coded in version control:
     *
     *   SUPER_ADMIN_NAME     — display name (default: "Super Admin")
     *   SUPER_ADMIN_EMAIL    — login email (required)
     *   SUPER_ADMIN_PASSWORD — login password (required)
     *
     * The seeder is idempotent: running it twice updates the existing account
     * rather than creating a duplicate.
     *
     * Usage:
     *   php artisan db:seed --class=SuperAdminSeeder
     */
    public function run(): void
    {
        $email    = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');
        $name     = env('SUPER_ADMIN_NAME', 'Super Admin');

        if (! $email || ! $password) {
            $this->command->error(
                'SUPER_ADMIN_EMAIL and SUPER_ADMIN_PASSWORD must be set in your .env file.'
            );
            return;
        }

        // Super-admins need an organization record because the users table
        // has a required organization_id FK. We use a dedicated internal org
        // that is separate from any real tenant.
        $org = Organization::firstOrCreate(
            ['slug' => 'demandlead-internal'],
            [
                'name'     => 'DemandLead (Internal)',
                'industry' => 'Technology',
                'country'  => 'KE',
                'timezone' => 'Africa/Nairobi',
                'status'   => 'active',
            ]
        );

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'organization_id' => $org->id,
                'name'            => $name,
                'password'        => Hash::make($password),
                'role'            => 'owner',
                'status'          => 'active',
                'is_super_admin'  => true,
            ]
        );

        $action = $user->wasRecentlyCreated ? 'created' : 'updated';

        $this->command->info("Super-admin {$action}: {$user->email}");
        $this->command->line("  Name:          {$user->name}");
        $this->command->line("  Organization:  {$org->name}");
        $this->command->line("  is_super_admin: true");
        $this->command->newLine();
        $this->command->warn(
            "Remember to set a strong password in production via SUPER_ADMIN_PASSWORD."
        );
    }
}
