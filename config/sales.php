<?php

return [

    'sources' => [
        'website', 'whatsapp', 'email', 'phone', 'referral', 'walk_in',
        'facebook', 'instagram', 'google_ads', 'agent', 'other',
    ],

    'default_stages' => [
        ['name' => 'New Inquiry', 'slug' => 'new', 'sort_order' => 1, 'color' => '#3b82f6', 'legacy_status' => 'New', 'sla_hours' => 4],
        ['name' => 'Contacted', 'slug' => 'contacted', 'sort_order' => 2, 'color' => '#8b5cf6', 'legacy_status' => 'Open', 'sla_hours' => 24],
        ['name' => 'Qualified', 'slug' => 'qualified', 'sort_order' => 3, 'color' => '#06b6d4', 'legacy_status' => 'Open', 'sla_hours' => 48],
        ['name' => 'Proposal Sent', 'slug' => 'proposal', 'sort_order' => 4, 'color' => '#f59e0b', 'legacy_status' => 'Open', 'sla_hours' => 72],
        ['name' => 'Booked', 'slug' => 'booked', 'sort_order' => 5, 'color' => '#22c55e', 'legacy_status' => 'Booked', 'is_won' => true, 'sla_hours' => null],
        ['name' => 'Lost', 'slug' => 'lost', 'sort_order' => 6, 'color' => '#ef4444', 'legacy_status' => 'Not Booked', 'is_lost' => true, 'sla_hours' => null],
        ['name' => 'Archived', 'slug' => 'archived', 'sort_order' => 7, 'color' => '#6b7280', 'legacy_status' => 'Archive', 'sla_hours' => null],
    ],

];
