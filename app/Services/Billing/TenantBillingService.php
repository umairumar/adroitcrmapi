<?php

namespace App\Services\Billing;

use App\Models\Tenant;
use App\Models\TenantBillingInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TenantBillingService
{
    public function canAccessPlatform(Tenant $tenant): bool
    {
        if ($tenant->status !== 'active') {
            return false;
        }

        if ($tenant->billing_status === 'suspended') {
            return false;
        }

        if ($this->isOnActiveTrial($tenant)) {
            return true;
        }

        if (in_array($tenant->billing_status, ['active', 'grace_period'], true)) {
            return ! $this->hasBlockingOverdueInvoice($tenant);
        }

        return $this->hasPaidInvoiceCoveringNow($tenant);
    }

    public function billingBlockReason(Tenant $tenant): ?string
    {
        if ($tenant->status !== 'active') {
            return 'account_inactive';
        }

        if ($tenant->billing_status === 'suspended') {
            return 'account_suspended';
        }

        if ($this->isOnActiveTrial($tenant)) {
            return null;
        }

        if ($tenant->billing_status === 'trial' && $tenant->trial_ends_at?->isPast()) {
            return 'trial_expired';
        }

        if ($this->hasBlockingOverdueInvoice($tenant)) {
            return 'invoice_overdue';
        }

        if (! in_array($tenant->billing_status, ['active', 'grace_period'], true)
            && ! $this->hasPaidInvoiceCoveringNow($tenant)) {
            return 'billing_inactive';
        }

        return null;
    }

    public function isOnActiveTrial(Tenant $tenant): bool
    {
        return $tenant->plan === 'trial'
            && $tenant->trial_ends_at
            && $tenant->trial_ends_at->isFuture();
    }

    public function hasPaidInvoiceCoveringNow(Tenant $tenant): bool
    {
        $today = Carbon::today();

        return TenantBillingInvoice::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->whereDate('period_start', '<=', $today)
            ->whereDate('period_end', '>=', $today)
            ->exists();
    }

    public function hasBlockingOverdueInvoice(Tenant $tenant): bool
    {
        $graceDays = (int) config('saas.billing.grace_period_days', 7);

        return TenantBillingInvoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['sent', 'overdue'])
            ->whereDate('due_date', '<', Carbon::today()->subDays($graceDays))
            ->exists();
    }

    public function markOverdueInvoices(): int
    {
        return TenantBillingInvoice::where('status', 'sent')
            ->whereDate('due_date', '<', Carbon::today())
            ->update(['status' => 'overdue']);
    }

    public function syncTenantBillingStatus(Tenant $tenant): void
    {
        if ($this->isOnActiveTrial($tenant)) {
            $tenant->update(['billing_status' => 'trial']);

            return;
        }

        if ($this->hasBlockingOverdueInvoice($tenant)) {
            $tenant->update(['billing_status' => 'suspended']);

            return;
        }

        $hasOverdue = TenantBillingInvoice::where('tenant_id', $tenant->id)
            ->where('status', 'overdue')
            ->exists();

        if ($hasOverdue) {
            $tenant->update(['billing_status' => 'grace_period']);

            return;
        }

        if ($this->hasPaidInvoiceCoveringNow($tenant)) {
            $tenant->update(['billing_status' => 'active']);

            return;
        }

        if ($tenant->trial_ends_at?->isPast() && $tenant->billing_status === 'trial') {
            $tenant->update(['billing_status' => 'grace_period']);
        }
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = config('saas.billing.invoice_number_prefix', 'SAAS');
        $seq = (int) TenantBillingInvoice::max('id') + 1;

        return sprintf('%s-%s-%04d', $prefix, now()->format('Y'), $seq);
    }

    public function createInvoice(
        Tenant $tenant,
        string $plan,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?float $amount = null,
        ?int $createdBy = null,
    ): TenantBillingInvoice {
        $planConfig = config('saas.plans.' . $plan, []);
        $amount ??= (float) ($planConfig['monthly_amount'] ?? 0);
        $terms = $tenant->payment_terms_days
            ?: (int) config('saas.billing.default_payment_terms_days', 30);

        $issueDate = Carbon::today();
        $dueDate = $issueDate->copy()->addDays($terms);

        return DB::transaction(function () use (
            $tenant, $plan, $periodStart, $periodEnd, $amount, $createdBy, $issueDate, $dueDate
        ) {
            $invoice = TenantBillingInvoice::create([
                'tenant_id' => $tenant->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'plan' => $plan,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'amount' => $amount,
                'currency' => config('saas.billing.currency', 'GBP'),
                'status' => 'draft',
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'created_by' => $createdBy,
            ]);

            return $invoice;
        });
    }

    public function markInvoicePaid(
        TenantBillingInvoice $invoice,
        ?string $paymentReference = null,
        ?Carbon $paidAt = null,
    ): TenantBillingInvoice {
        return DB::transaction(function () use ($invoice, $paymentReference, $paidAt) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => $paidAt ?? now(),
                'payment_reference' => $paymentReference,
            ]);

            $tenant = $invoice->tenant;
            $tenant->update([
                'plan' => $invoice->plan,
                'billing_status' => 'active',
            ]);

            $this->syncTenantBillingStatus($tenant->fresh());

            return $invoice->fresh();
        });
    }

    public function sendInvoice(TenantBillingInvoice $invoice): TenantBillingInvoice
    {
        $invoice->update(['status' => 'sent']);
        $this->syncTenantBillingStatus($invoice->tenant);

        return $invoice->fresh();
    }
}
