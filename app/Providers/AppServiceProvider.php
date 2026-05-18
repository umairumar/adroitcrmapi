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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
