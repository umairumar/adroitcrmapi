<?php

namespace App\Services\Engagement;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CampaignStep;
use App\Models\Contact;
use App\Models\Segment;
use App\Services\Sales\SegmentService;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(
        private readonly SegmentService $segments,
        private readonly MessagingService $messaging,
    ) {}

    public function buildRecipientsFromSegment(Campaign $campaign): int
    {
        if (! $campaign->segment_id) {
            return 0;
        }

        $segment = Segment::findOrFail($campaign->segment_id);
        $contacts = $this->segments->queryContacts($segment)->get();

        $count = 0;
        foreach ($contacts as $contact) {
            CampaignRecipient::firstOrCreate(
                ['campaign_id' => $campaign->id, 'contact_id' => $contact->id],
                [
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'status' => 'pending',
                    'next_send_at' => $campaign->scheduled_at ?? now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    public function launch(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign) {
            if ($campaign->recipients()->count() === 0) {
                $this->buildRecipientsFromSegment($campaign);
            }

            $campaign->update([
                'status' => 'running',
                'started_at' => now(),
            ]);

            return $campaign->fresh();
        });
    }

    public function processDueSends(): int
    {
        $sent = 0;
        $recipients = CampaignRecipient::where('status', 'pending')
            ->where('next_send_at', '<=', now())
            ->whereHas('campaign', fn ($q) => $q->where('status', 'running'))
            ->with(['campaign.steps.template'])
            ->limit(100)
            ->get();

        foreach ($recipients as $recipient) {
            $campaign = Campaign::with('steps.template')->find($recipient->campaign_id);
            if (! $campaign) {
                continue;
            }

            $steps = $campaign->steps;
            $stepIndex = (int) $recipient->current_step;

            if ($stepIndex >= $steps->count()) {
                $recipient->update(['status' => 'completed']);
                continue;
            }

            $step = $steps[$stepIndex];
            $contact = $recipient->contact_id ? Contact::find($recipient->contact_id) : null;

            if ($contact && $step->template) {
                $this->messaging->sendFromTemplate($step->template, $contact);
            }

            $nextStep = $stepIndex + 1;
            if ($nextStep >= $steps->count()) {
                $recipient->update(['status' => 'completed', 'current_step' => $nextStep]);
            } else {
                $delay = $steps[$nextStep]->delay_hours ?? 24;
                $recipient->update([
                    'current_step' => $nextStep,
                    'next_send_at' => Carbon::now()->addHours($delay),
                ]);
            }

            $sent++;
        }

        return $sent;
    }
}
