<?php

namespace App\Services\Engagement;

use App\Models\Contact;
use App\Models\LoyaltyTransaction;
use App\Models\ReferralCode;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public function earn(Contact $contact, int $points, string $reason, ?string $refType = null, ?int $refId = null): void
    {
        DB::transaction(function () use ($contact, $points, $reason, $refType, $refId) {
            LoyaltyTransaction::create([
                'tenant_id' => $contact->tenant_id,
                'contact_id' => $contact->id,
                'points' => $points,
                'type' => 'earn',
                'reason' => $reason,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'created_at' => now(),
            ]);

            $contact->increment('loyalty_points', $points);
        });
    }

    public function redeem(Contact $contact, int $points, string $reason): bool
    {
        if ($contact->loyalty_points < $points) {
            return false;
        }

        DB::transaction(function () use ($contact, $points, $reason) {
            LoyaltyTransaction::create([
                'tenant_id' => $contact->tenant_id,
                'contact_id' => $contact->id,
                'points' => -$points,
                'type' => 'redeem',
                'reason' => $reason,
                'created_at' => now(),
            ]);

            $contact->decrement('loyalty_points', $points);
        });

        return true;
    }

    public function rewardReferrer(ReferralCode $code): void
    {
        if (! $code->contact_id || $code->points_reward <= 0) {
            return;
        }

        $contact = Contact::find($code->contact_id);
        if ($contact) {
            $this->earn($contact, (int) $code->points_reward, 'Referral reward', 'referral_code', $code->id);
        }
    }
}
