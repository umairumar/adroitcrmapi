<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignRolesFromUtype extends Command
{
    protected $signature = 'saas:assign-roles-from-utype {--dry-run}';

    protected $description = 'Map legacy user.utype values to SaaS roles in user_role';

    public function handle(): int
    {
        if (! Schema::hasTable('user_role')) {
            $this->error('Run migrations and SaasFoundationSeeder first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $map = config('saas.utype_role_map', []);
        $assigned = 0;

        User::withoutGlobalScopes()->chunkById(100, function ($users) use ($map, $dryRun, &$assigned) {
            foreach ($users as $user) {
                $slug = $map[$user->utype] ?? null;
                if (! $slug) {
                    continue;
                }

                $role = Role::where('slug', $slug)->whereNull('tenant_id')->first();
                if (! $role) {
                    continue;
                }

                $exists = DB::table('user_role')
                    ->where('user_id', $user->id)
                    ->where('role_id', $role->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("Would assign {$slug} to user #{$user->id}");
                } else {
                    DB::table('user_role')->insert([
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                        'tenant_id' => $user->tenant_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $assigned++;
            }
        });

        $this->info(($dryRun ? 'Would assign' : 'Assigned') . " {$assigned} role(s).");

        return self::SUCCESS;
    }
}
