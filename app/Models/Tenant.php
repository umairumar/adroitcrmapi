<?php

namespace App\Models;

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
        'stripe_customer_id',
        'stripe_subscription_id',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'settings' => 'array',
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

    public function isOnTrial(): bool
    {
        return $this->plan === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function planLimits(): array
    {
        return config('saas.plans.' . $this->plan, config('saas.plans.trial'));
    }
}
