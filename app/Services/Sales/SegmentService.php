<?php

namespace App\Services\Sales;

use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;

class SegmentService
{
    public function queryContacts(Segment $segment): Builder
    {
        $query = Contact::query();
        $this->applyContactFilters($query, $segment->filters ?? []);

        return $query;
    }

    public function queryLeads(Segment $segment): Builder
    {
        $query = CrmLead::query();
        $this->applyLeadFilters($query, $segment->filters ?? []);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyContactFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['tag_id'])) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $filters['tag_id']));
        }

        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['min_loyalty_points'])) {
            $query->where('loyalty_points', '>=', (int) $filters['min_loyalty_points']);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyLeadFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['pipeline_stage_id'])) {
            $query->where('pipeline_stage_id', $filters['pipeline_stage_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['destination'])) {
            $query->where('destination', 'like', '%' . $filters['destination'] . '%');
        }
    }
}
