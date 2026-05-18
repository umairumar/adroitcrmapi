<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CrmCompany;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\BranchAccess;
use App\Services\Sales\PipelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TenantRegistrationController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly BranchAccess $branchAccess,
        private readonly PipelineService $pipeline,
    ) {}

    /**
     * Self-service signup for a new travel company (tenant).
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'organization_name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|alpha_dash|unique:tenants,slug',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:user,email',
            'admin_password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
            'branch_name' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $slug = $request->slug ?: Str::slug($request->organization_name);
        $baseSlug = $slug;
        $counter = 1;
        while (Tenant::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $trialDays = (int) (config('saas.plans.trial.trial_days') ?? 14);

        $result = DB::transaction(function () use ($request, $slug, $trialDays) {
            $tenant = Tenant::create([
                'name' => $request->organization_name,
                'slug' => $slug,
                'email' => $request->admin_email,
                'phone' => $request->phone,
                'status' => 'active',
                'plan' => 'trial',
                'trial_ends_at' => now()->addDays($trialDays),
                'billing_status' => 'trial',
                'billing_email' => $request->admin_email,
                'payment_terms_days' => (int) config('saas.billing.default_payment_terms_days', 30),
                'settings' => [
                    'timezone' => $request->input('timezone', 'UTC'),
                    'currency' => $request->input('currency', 'GBP'),
                ],
            ]);

            $branch = CrmCompany::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'title' => $request->branch_name ?: $tenant->name . ' — Main Office',
                'email' => $request->admin_email,
                'phone' => $request->phone ?? '',
                'status' => 1,
            ]);

            $user = User::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'is_platform_admin' => false,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'utype' => 'cadmin',
                'name' => $request->admin_name,
                'company' => '-' . $branch->id . '-',
                'agent_directline' => $request->phone ?? '',
                'status' => 1,
                'cby' => 0,
                'cdate' => now(),
                'mby' => 0,
                'mdate' => now(),
            ]);

            $role = Role::where('slug', 'tenant_admin')
                ->whereNull('tenant_id')
                ->where('is_system', true)
                ->first();

            if ($role) {
                DB::table('user_role')->insert([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'tenant_id' => $tenant->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->branchAccess->syncBranchesForUser($user, [$branch->id]);

            $this->pipeline->seedDefaultStagesForTenant($tenant->id);

            $token = $user->createToken('crm-token')->plainTextToken;

            return compact('tenant', 'branch', 'user', 'token');
        });

        $this->auditLogger->log(
            'tenant.registered',
            $result['tenant'],
            null,
            ['slug' => $result['tenant']->slug, 'plan' => 'trial'],
            $request,
            $result['user']->id,
        );

        return response()->json([
            'status' => true,
            'message' => 'Organization registered successfully.',
            'token' => $result['token'],
            'tenant' => $result['tenant'],
            'branch' => $result['branch'],
            'user' => $result['user'],
        ], 201);
    }
}
