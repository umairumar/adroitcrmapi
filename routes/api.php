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
use App\Http\Controllers\Api\V1\BookingOperationsController;
use App\Http\Controllers\Api\V1\BookingDepositController;
use App\Http\Controllers\Api\V1\BookingDocumentController;
use App\Http\Controllers\Api\V1\CommissionController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\ExpenseReceiptController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\FinanceReportController;
use App\Http\Controllers\Api\V1\CustomerInvoiceController;
use App\Http\Controllers\Api\V1\SupplierBillController;
use App\Http\Controllers\Api\V1\ChartOfAccountController;
use App\Http\Controllers\Api\V1\JournalEntryController;
use App\Http\Controllers\Api\V1\TaxRateController;
use App\Http\Controllers\Api\V1\ExchangeRateController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\BankAccountController;

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

        // Phase 2: Operations, commissions, deposits, attendance
        Route::get('/operations/booking-statuses', [BookingOperationsController::class, 'bookingStatuses']);
        Route::get('/folders/{folderId}/operations', [BookingOperationsController::class, 'summary']);
        Route::put('/folders/{folderId}/booking-status', [BookingOperationsController::class, 'updateStatus']);
        Route::post('/folders/{folderId}/link-lead', [BookingOperationsController::class, 'linkLead']);
        Route::post('/folders/{folderId}/calculate-commissions', [BookingOperationsController::class, 'calculateCommissions']);
        Route::post('/folders/{folderId}/sync-deposits', [BookingOperationsController::class, 'syncDeposits']);

        Route::get('/folders/{folderId}/deposits', [BookingDepositController::class, 'index']);
        Route::post('/folders/{folderId}/deposits', [BookingDepositController::class, 'store']);

        Route::get('/folders/{folderId}/documents', [BookingDocumentController::class, 'index']);
        Route::post('/folders/{folderId}/documents', [BookingDocumentController::class, 'store']);
        Route::delete('/documents/{id}', [BookingDocumentController::class, 'destroy']);

        Route::get('/commissions/entries', [CommissionController::class, 'entries']);
        Route::post('/commissions/entries/{id}/approve', [CommissionController::class, 'approveEntry']);
        Route::get('/commissions/report', [CommissionController::class, 'report']);
        Route::get('/commissions/staff-rules', [CommissionController::class, 'staffRules']);
        Route::post('/commissions/staff-rules', [CommissionController::class, 'storeStaffRule']);
        Route::get('/commissions/supplier-rules', [CommissionController::class, 'supplierRules']);
        Route::post('/commissions/supplier-rules', [CommissionController::class, 'storeSupplierRule']);
        Route::get('/commissions/payouts', [CommissionController::class, 'payouts']);
        Route::post('/commissions/payouts', [CommissionController::class, 'createPayout']);
        Route::post('/commissions/payouts/{id}/paid', [CommissionController::class, 'markPayoutPaid']);

        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::put('/suppliers/{id}', [SupplierController::class, 'update']);

        Route::get('/receipts', [ExpenseReceiptController::class, 'index']);
        Route::post('/receipts', [ExpenseReceiptController::class, 'store']);
        Route::post('/receipts/{id}/review', [ExpenseReceiptController::class, 'approve']);

        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('/attendance/me', [AttendanceController::class, 'myRecords']);
        Route::get('/attendance/team', [AttendanceController::class, 'teamRecords']);
        Route::get('/attendance/summary/{userId}', [AttendanceController::class, 'summary']);

        Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
        Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
        Route::post('/leave-requests/{id}/review', [LeaveRequestController::class, 'review']);

        // Phase 3: Finance (GL, AR, AP, tax, FX, budgets, bank)
        Route::get('/finance/reports/trial-balance', [FinanceReportController::class, 'trialBalance']);
        Route::get('/finance/reports/ar-aging', [FinanceReportController::class, 'arAging']);
        Route::get('/finance/reports/ap-aging', [FinanceReportController::class, 'apAging']);
        Route::get('/finance/reports/budget-variance', [FinanceReportController::class, 'budgetVariance']);

        Route::get('/finance/chart-of-accounts', [ChartOfAccountController::class, 'index']);
        Route::post('/finance/chart-of-accounts', [ChartOfAccountController::class, 'store']);
        Route::post('/finance/chart-of-accounts/seed', [ChartOfAccountController::class, 'seed']);

        Route::get('/finance/journal-entries', [JournalEntryController::class, 'index']);
        Route::get('/finance/journal-entries/{id}', [JournalEntryController::class, 'show']);
        Route::post('/finance/journal-entries', [JournalEntryController::class, 'store']);

        Route::get('/finance/invoices', [CustomerInvoiceController::class, 'index']);
        Route::get('/finance/invoices/{id}', [CustomerInvoiceController::class, 'show']);
        Route::post('/finance/folders/{folderId}/invoice', [CustomerInvoiceController::class, 'createFromFolder']);
        Route::post('/finance/invoices/{id}/allocate', [CustomerInvoiceController::class, 'allocate']);

        Route::get('/finance/bills', [SupplierBillController::class, 'index']);
        Route::post('/finance/bills', [SupplierBillController::class, 'store']);
        Route::post('/finance/bills/{id}/pay', [SupplierBillController::class, 'pay']);

        Route::get('/finance/tax-rates', [TaxRateController::class, 'index']);
        Route::post('/finance/tax-rates', [TaxRateController::class, 'store']);

        Route::get('/finance/exchange-rates', [ExchangeRateController::class, 'index']);
        Route::post('/finance/exchange-rates', [ExchangeRateController::class, 'store']);
        Route::post('/finance/convert', [ExchangeRateController::class, 'convert']);

        Route::get('/finance/budgets', [BudgetController::class, 'index']);
        Route::post('/finance/budgets', [BudgetController::class, 'store']);

        Route::get('/finance/bank-accounts', [BankAccountController::class, 'index']);
        Route::post('/finance/bank-accounts', [BankAccountController::class, 'store']);
        Route::get('/finance/bank-accounts/{id}/transactions', [BankAccountController::class, 'transactions']);
        Route::post('/finance/bank-accounts/{id}/import', [BankAccountController::class, 'importCsv']);
        Route::get('/finance/bank-accounts/{id}/reconcile-suggestions', [BankAccountController::class, 'reconcileSuggestions']);
        Route::post('/finance/bank-transactions/{txnId}/reconcile', [BankAccountController::class, 'reconcile']);

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
