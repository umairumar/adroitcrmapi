<?php

namespace App\Providers;

use App\Services\Auth\AuthorizationService;
use App\Services\Auth\BranchAccess;
use App\Services\Audit\AuditLogger;
use App\Services\Billing\TenantBillingService;
use App\Services\Sales\LeadAssignmentService;
use App\Services\Sales\LeadCaptureService;
use App\Services\Sales\PipelineService;
use App\Services\Sales\SegmentService;
use App\Services\Operations\AttendanceService;
use App\Services\Operations\BookingOperationsService;
use App\Services\Operations\CommissionCalculationService;
use App\Services\Operations\DepositService;
use App\Services\Finance\AccountsPayableService;
use App\Services\Finance\AccountsReceivableService;
use App\Services\Finance\BankReconciliationService;
use App\Services\Finance\BudgetService;
use App\Services\Finance\ChartOfAccountsService;
use App\Services\Finance\ExchangeRateService;
use App\Services\Finance\FinanceIntegrationService;
use App\Services\Finance\GeneralLedgerService;
use App\Services\Finance\RevenueRecognitionService;
use App\Services\Finance\TaxService;
use App\Services\Engagement\AnalyticsService;
use App\Services\Engagement\CampaignService;
use App\Services\Engagement\LoyaltyService;
use App\Services\Engagement\MessagingService;
use App\Services\Engagement\PortalService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BranchAccess::class);
        $this->app->singleton(AuthorizationService::class);
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(TenantBillingService::class);
        $this->app->singleton(PipelineService::class);
        $this->app->singleton(LeadCaptureService::class);
        $this->app->singleton(LeadAssignmentService::class);
        $this->app->singleton(SegmentService::class);
        $this->app->singleton(CommissionCalculationService::class);
        $this->app->singleton(DepositService::class);
        $this->app->singleton(AttendanceService::class);
        $this->app->singleton(BookingOperationsService::class);
        $this->app->singleton(ChartOfAccountsService::class);
        $this->app->singleton(GeneralLedgerService::class);
        $this->app->singleton(TaxService::class);
        $this->app->singleton(ExchangeRateService::class);
        $this->app->singleton(AccountsReceivableService::class);
        $this->app->singleton(AccountsPayableService::class);
        $this->app->singleton(RevenueRecognitionService::class);
        $this->app->singleton(BankReconciliationService::class);
        $this->app->singleton(BudgetService::class);
        $this->app->singleton(FinanceIntegrationService::class);
        $this->app->singleton(MessagingService::class);
        $this->app->singleton(CampaignService::class);
        $this->app->singleton(AnalyticsService::class);
        $this->app->singleton(PortalService::class);
        $this->app->singleton(LoyaltyService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
