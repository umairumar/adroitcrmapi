<?php

namespace App\Services\Sales;

use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\ReferralCode;
use App\Services\Engagement\LoyaltyService;
use Illuminate\Http\Request;

class LeadCaptureService
{
    public function __construct(
        private readonly PipelineService $pipeline,
        private readonly LoyaltyService $loyalty,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function attributionFromRequest(Request $request): array
    {
        return array_filter([
            'source' => $request->input('source'),
            'source_detail' => $request->input('source_detail'),
            'utm_source' => $request->input('utm_source'),
            'utm_medium' => $request->input('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_content' => $request->input('utm_content'),
            'utm_term' => $request->input('utm_term'),
            'referral_code' => $request->input('referral_code'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function findDuplicates(int $tenantId, ?string $email, ?string $phone, ?int $excludeLeadId = null): array
    {
        if (! $email && ! $phone) {
            return [];
        }

        $query = CrmLead::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($email, $phone) {
                if ($email) {
                    $q->orWhere('email', $email);
                }
                if ($phone) {
                    $q->orWhere('phone', $phone);
                }
            });

        if ($excludeLeadId) {
            $query->where('id', '<>', $excludeLeadId);
        }

        return $query->limit(5)->get(['id', 'name', 'email', 'phone', 'status', 'pipeline_stage_id'])->all();
    }

    public function findOrCreateContact(int $tenantId, array $data): Contact
    {
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;

        $existing = Contact::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->when($email, fn ($q) => $q->where('email', $email))
            ->when(! $email && $phone, fn ($q) => $q->where('phone', $phone))
            ->first();

        if ($existing) {
            return $existing;
        }

        return Contact::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'organization_id' => $data['organization_id'] ?? null,
            'type' => $data['type'] ?? 'b2c',
            'name' => $data['name'],
            'email' => $email,
            'phone' => $phone,
        ]);
    }

    public function enrichNewLead(CrmLead $lead, Request $request): CrmLead
    {
        $attrs = $this->attributionFromRequest($request);
        $lead->fill($attrs);

        if ($lead->tenant_id && ($lead->email || $lead->phone)) {
            $contact = $this->findOrCreateContact($lead->tenant_id, [
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'organization_id' => $lead->organization_id,
            ]);
            $lead->contact_id = $contact->id;
        }

        if ($lead->tenant_id && ! $lead->pipeline_stage_id) {
            $stage = $this->pipeline->defaultStageForTenant($lead->tenant_id);
            if ($stage) {
                $lead->pipeline_stage_id = $stage->id;
                $lead->status = $stage->legacy_status ?? 'New';
                $lead->stage_entered_at = now();
            }
        }

        if ($request->filled('referral_code') && $lead->tenant_id) {
            $this->applyReferralCode($lead, $request->referral_code);
        }

        $lead->save();

        return $lead->fresh(['contact', 'pipelineStage']);
    }

    public function applyReferralCode(CrmLead $lead, string $code): void
    {
        $referral = ReferralCode::withoutGlobalScopes()
            ->where('tenant_id', $lead->tenant_id)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $referral) {
            return;
        }

        if ($referral->max_uses && $referral->uses_count >= $referral->max_uses) {
            return;
        }

        $referral->increment('uses_count');
        $lead->referral_code = $code;
        $lead->source = $lead->source ?: 'referral';

        $this->loyalty->rewardReferrer($referral->fresh());
    }
}
