<?php

return [

    'default_terms' => env(
        'INVOICE_DEFAULT_TERMS',
        'Payment is due by the date shown above. Please use your booking reference as the payment reference. '
        . 'All prices are in the currency stated. Late payments may incur additional charges.'
    ),

    'title' => env('INVOICE_TITLE', 'INVOICE'),

];
