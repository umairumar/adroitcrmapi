<?php

return [

    'base_currency' => env('FINANCE_BASE_CURRENCY', 'GBP'),

    'invoice_prefix' => env('FINANCE_INVOICE_PREFIX', 'INV'),
    'bill_prefix' => env('FINANCE_BILL_PREFIX', 'BILL'),
    'journal_prefix' => env('FINANCE_JOURNAL_PREFIX', 'JE'),

    'revenue_recognition' => [
        'on_payment' => 'On payment received',
        'on_travel_date' => 'On travel date',
        'on_invoice' => 'On invoice issue',
    ],

    'default_accounts' => [
        ['code' => '1000', 'name' => 'Cash at Bank', 'type' => 'asset'],
        ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset'],
        ['code' => '1200', 'name' => 'Prepayments', 'type' => 'asset'],
        ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
        ['code' => '2100', 'name' => 'VAT/GST Payable', 'type' => 'liability'],
        ['code' => '3000', 'name' => 'Retained Earnings', 'type' => 'equity'],
        ['code' => '4000', 'name' => 'Travel Revenue', 'type' => 'revenue'],
        ['code' => '4100', 'name' => 'Commission Income', 'type' => 'revenue'],
        ['code' => '5000', 'name' => 'Cost of Travel Sales', 'type' => 'expense'],
        ['code' => '5100', 'name' => 'Staff Commission Expense', 'type' => 'expense'],
        ['code' => '5200', 'name' => 'General Expenses', 'type' => 'expense'],
    ],

    'account_roles' => [
        'ar' => '1100',
        'ap' => '2000',
        'cash' => '1000',
        'revenue' => '4000',
        'cogs' => '5000',
        'tax_payable' => '2100',
        'commission_income' => '4100',
        'commission_expense' => '5100',
    ],

];
