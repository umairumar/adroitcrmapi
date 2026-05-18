<?php

return [

    'booking_statuses' => [
        'quote' => 'Quote',
        'confirmed' => 'Confirmed',
        'in_travel' => 'In Travel',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'commission_bases' => ['sell', 'cost', 'profit', 'folder_commission', 'line_commission'],

    'commission_types' => ['percent', 'fixed'],

    'attendance' => [
        'standard_check_in' => '09:00',
        'standard_check_out' => '18:00',
    ],

    'leave_types' => ['annual', 'sick', 'unpaid', 'other'],

    'receipt_categories' => [
        'supplier', 'petty_cash', 'transport', 'accommodation', 'visa', 'other',
    ],

];
