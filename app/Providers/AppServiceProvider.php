<?php

namespace App\Providers;

use App\Services\Auth\AuthorizationService;
use App\Services\Auth\BranchAccess;
use App\Services\Audit\AuditLogger;
use App\Services\Billing\TenantBillingService;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
