<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\CrmCompanyController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\CrmLeadController;
use App\Http\Controllers\Api\V1\LeadRemarkController;
use App\Http\Controllers\Api\V1\FoldersController;
use App\Http\Controllers\Api\V1\CrmPaymentController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TenantRegistrationController;
use App\Http\Controllers\Api\V1\TenantBillingController;
use App\Http\Controllers\Api\V1\PipelineStageController;
use App\Http\Controllers\Api\V1\SalesPipelineController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\SegmentController;
use App\Http\Controllers\Api\V1\LeadAssignmentRuleController;
use App\Http\Controllers\Api\V1\ReferralCodeController;

Route::prefix('v1')->group(function () {

    // Public: lead capture (optional tenant_slug scopes lead to tenant)
    Route::post('/leadsdirectstore', [CrmLeadController::class, 'directstore']);

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // SaaS: self-service tenant registration
    Route::post('/tenants/register', [TenantRegistrationController::class, 'register']);

    Route::middleware(['auth:sanctum', 'tenant.context', 'tenant.active'])->group(function () {

        Route::get('/me', function (Request $request) {
            $user = $request->user();
            $tenant = $user->tenant_id
                ? \App\Models\Tenant::find($user->tenant_id)
                : null;

            return response()->json([
                'status' => true,
                'user' => $user,
                'tenant' => $tenant,
                'is_platform_admin' => $user->isPlatformAdmin(),
            ]);
        });

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::put('/userpasswupdate', [UserController::class, 'userpasswupdate']);

        // Tenant context
        Route::get('/tenant', [TenantController::class, 'me']);
        Route::get('/tenants', [TenantController::class, 'index']);

        // Platform billing (invoice-based, no card subscriptions)
        Route::get('/billing/invoices', [TenantBillingController::class, 'index']);
        Route::post('/billing/invoices', [TenantBillingController::class, 'store']);
        Route::post('/billing/invoices/{id}/send', [TenantBillingController::class, 'send']);
        Route::post('/billing/invoices/{id}/paid', [TenantBillingController::class, 'markPaid']);
        Route::post('/billing/invoices/{id}/void', [TenantBillingController::class, 'void']);
        Route::get('/billing/my-invoices', [TenantBillingController::class, 'myInvoices']);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Sales pipeline (Phase 1)
        Route::get('/pipeline/kanban', [SalesPipelineController::class, 'kanban']);
        Route::get('/pipeline/funnel', [SalesPipelineController::class, 'funnel']);
        Route::get('/pipeline/sla-breaches', [SalesPipelineController::class, 'slaBreaches']);
        Route::get('/pipeline/sources', [SalesPipelineController::class, 'sources']);
        Route::post('/pipeline/leads/{leadId}/move', [SalesPipelineController::class, 'moveStage']);
        Route::get('/pipeline/leads/{leadId}/history', [SalesPipelineController::class, 'history']);
        Route::post('/pipeline/leads/{leadId}/auto-assign', [SalesPipelineController::class, 'autoAssign']);

        Route::get('/pipeline/stages', [PipelineStageController::class, 'index']);
        Route::post('/pipeline/stages', [PipelineStageController::class, 'store']);
        Route::put('/pipeline/stages/{id}', [PipelineStageController::class, 'update']);

        Route::get('/assignment-rules', [LeadAssignmentRuleController::class, 'index']);
        Route::post('/assignment-rules', [LeadAssignmentRuleController::class, 'store']);
        Route::put('/assignment-rules/{id}', [LeadAssignmentRuleController::class, 'update']);
        Route::delete('/assignment-rules/{id}', [LeadAssignmentRuleController::class, 'destroy']);

        Route::get('/contacts', [ContactController::class, 'index']);
        Route::post('/contacts', [ContactController::class, 'store']);
        Route::get('/contacts/{id}', [ContactController::class, 'show']);
        Route::put('/contacts/{id}', [ContactController::class, 'update']);
        Route::get('/contacts/{id}/timeline', [ContactController::class, 'timeline']);

        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::get('/organizations/{id}', [OrganizationController::class, 'show']);
        Route::put('/organizations/{id}', [OrganizationController::class, 'update']);

        Route::get('/tags', [TagController::class, 'index']);
        Route::post('/tags', [TagController::class, 'store']);

        Route::get('/segments', [SegmentController::class, 'index']);
        Route::post('/segments', [SegmentController::class, 'store']);
        Route::get('/segments/{id}/preview', [SegmentController::class, 'preview']);

        Route::get('/referral-codes', [ReferralCodeController::class, 'index']);
        Route::post('/referral-codes', [ReferralCodeController::class, 'store']);

        // Companies (branches) APIs
        Route::get('/companies', [CrmCompanyController::class, 'index']);
        Route::get('/companies/{id}', [CrmCompanyController::class, 'show']);
        Route::post('/companies', [CrmCompanyController::class, 'store']);
        Route::put('/companies/{id}', [CrmCompanyController::class, 'update']);
        Route::delete('/companies/{id}', [CrmCompanyController::class, 'destroy']);
        Route::get('/usersbycompany/{company}', [UserController::class, 'usersbycompany']);

        // Users APIs
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // CRM Leads
        Route::get('/leads', [CrmLeadController::class, 'index']);
        Route::get('/leads/{id}', [CrmLeadController::class, 'show']);
        Route::post('/leads', [CrmLeadController::class, 'store']);
        Route::put('/leads/{id}', [CrmLeadController::class, 'update']);
        Route::delete('/leads/{id}', [CrmLeadController::class, 'destroy']);

        Route::get('/openleads', [CrmLeadController::class, 'openleads']);
        Route::put('/leadsassign/{id}', [CrmLeadController::class, 'leadsassign']);

        Route::put('/movetobookedleads/{id}', [CrmLeadController::class, 'movetobookedleads']);
        Route::get('/bookedleads', [CrmLeadController::class, 'bookedleads']);

        Route::put('/movetonotbookedleads/{id}', [CrmLeadController::class, 'movetonotbookedleads']);
        Route::get('/notbookedleads', [CrmLeadController::class, 'notbookedleads']);

        Route::put('/movetoarchiveleads/{id}', [CrmLeadController::class, 'movetoarchiveleads']);
        Route::get('/archiveleads', [CrmLeadController::class, 'archiveleads']);

        Route::get('/allleads', [CrmLeadController::class, 'allleads']);

        Route::get('/leads/company/{companyId}', [CrmLeadController::class, 'leadsByCompany']);
        Route::get('/leads/agent/{agentId}', [CrmLeadController::class, 'leadsByAgent']);

        // Leads Remarks
        Route::get('/leads/{leadId}/remarks', [LeadRemarkController::class, 'index']);
        Route::get('/leads/remarks/{id}', [LeadRemarkController::class, 'show']);
        Route::post('/leads/remarks', [LeadRemarkController::class, 'store']);
        Route::put('/leads/remarks/{id}', [LeadRemarkController::class, 'update']);
        Route::delete('/leads/remarks/{id}', [LeadRemarkController::class, 'destroy']);

        // CRM Folders (bookings)
        Route::get('/folders', [FoldersController::class, 'index']);
        Route::get('/folders/{id}', [FoldersController::class, 'show']);
        Route::post('/folders', [FoldersController::class, 'store']);
        Route::put('/folders/{id}', [FoldersController::class, 'update']);
        Route::put('/folders/{folderId}/installments', [FoldersController::class, 'updateInstallments']);
        Route::post('/folders/parse-package-pdf', [FoldersController::class, 'parsePackagePdf']);
        Route::delete('/folders/{id}', [FoldersController::class, 'destroy']);

        // Folder Payments
        Route::get('/folders/{folderId}/payments', [CrmPaymentController::class, 'index']);
        Route::post('/folders/{folderId}/payments', [CrmPaymentController::class, 'store']);
        Route::get('/payments/{paymentId}', [CrmPaymentController::class, 'show']);
        Route::put('/payments/{paymentId}', [CrmPaymentController::class, 'update']);
        Route::put('/payments/{paymentId}/process', [CrmPaymentController::class, 'process']);
        Route::delete('/payments/{paymentId}', [CrmPaymentController::class, 'destroy']);

    });
});
