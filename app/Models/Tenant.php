<?php

namespace App\Models;

use App\Services\Billing\TenantBillingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'status',
        'plan',
        'trial_ends_at',
        'billing_status',
        'billing_email',
        'payment_terms_days',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'settings' => 'array',
            'payment_terms_days' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(CrmCompany::class, 'tenant_id');
    }

    public function billingInvoices(): HasMany
    {
        return $this->hasMany(TenantBillingInvoice::class);
    }

    public function isOnTrial(): bool
    {
        return app(TenantBillingService::class)->isOnActiveTrial($this);
    }

    public function planLimits(): array
    {
        return config('saas.plans.' . $this->plan, config('saas.plans.trial'));
    }

    public function billingEmail(): string
    {
        return $this->billing_email ?: $this->email;
    }
}
