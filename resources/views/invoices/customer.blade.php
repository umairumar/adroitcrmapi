<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 24px 32px;
        }
        .header {
            border-bottom: 3px solid {{ $branding->primary_color ?? '#0f766e' }};
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: {{ $branding->primary_color ?? '#0f766e' }};
        }
        .invoice-title {
            font-size: 26px;
            font-weight: bold;
            text-align: right;
            color: {{ $branding->secondary_color ?? '#134e4a' }};
        }
        .subtitle {
            text-align: right;
            font-size: 13px;
            color: #4b5563;
            margin-top: 4px;
        }
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .meta-grid td {
            width: 50%;
            vertical-align: top;
            padding: 8px 10px;
        }
        .box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 10px 12px;
            background: #f9fafb;
        }
        .box h4 {
            margin: 0 0 6px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
        }
        .booking-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .booking-details th,
        .booking-details td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: left;
        }
        .booking-details th {
            background: {{ $branding->accent_color ?? '#14b8a6' }}22;
            font-size: 10px;
            text-transform: uppercase;
            color: #374151;
        }
        .lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .lines th {
            background: {{ $branding->primary_color ?? '#0f766e' }};
            color: #fff;
            padding: 8px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .lines td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px;
        }
        .lines .num { text-align: right; white-space: nowrap; }
        .totals {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals td { padding: 5px 8px; }
        .totals .label { text-align: right; color: #4b5563; }
        .totals .value { text-align: right; font-weight: bold; }
        .totals .grand td {
            border-top: 2px solid {{ $branding->primary_color ?? '#0f766e' }};
            font-size: 13px;
            padding-top: 8px;
        }
        .balance-due {
            color: {{ $branding->primary_color ?? '#0f766e' }};
            font-size: 14px;
        }
        .payment-box {
            margin-top: 18px;
            border: 1px dashed {{ $branding->primary_color ?? '#0f766e' }};
            padding: 12px;
            border-radius: 4px;
        }
        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .passenger-list { margin: 4px 0 0; padding-left: 14px; }
    </style>
</head>
<body>
@php
    $currency = $invoice->currency ?? 'GBP';
    $symbol = match ($currency) {
        'GBP' => '£',
        'EUR' => '€',
        'USD' => '$',
        default => $currency . ' ',
    };
    $money = fn ($n) => $symbol . number_format((float) $n, 2);
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if(!empty($branding->logo_url))
                    <img src="{{ $branding->logo_url }}" alt="Logo" style="max-height: 52px; max-width: 200px;">
                @endif
                <div class="company-name">{{ $branding->app_name ?? $tenant?->name ?? 'Travel Agency' }}</div>
                @if(!empty($branding->company_address))
                    <div style="margin-top:6px; white-space: pre-line;">{{ $branding->company_address }}</div>
                @endif
                @if($branding->support_phone)
                    <div>Tel: {{ $branding->support_phone }}</div>
                @endif
                @if($branding->support_email)
                    <div>{{ $branding->support_email }}</div>
                @endif
                @if($branding->vat_number)
                    <div>VAT: {{ $branding->vat_number }}</div>
                @endif
                @if($branding->company_registration)
                    <div>Reg: {{ $branding->company_registration }}</div>
                @endif
            </td>
            <td style="width: 45%;">
                <div class="invoice-title">{{ $title }}</div>
                <div class="subtitle">For {{ $billTo }}</div>
            </td>
        </tr>
    </table>
</div>

<table class="meta-grid">
    <tr>
        <td>
            <div class="box">
                <h4>Bill To</h4>
                <strong>{{ $billTo }}</strong>
                @if($passengers->count() > 1)
                    <ul class="passenger-list">
                        @foreach($passengers->skip(1) as $p)
                            <li>{{ trim(implode(' ', array_filter([$p->title, $p->fname, $p->mname, $p->lname]))) }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </td>
        <td>
            <div class="box">
                <h4>Invoice Details</h4>
                <table style="width:100%; border-collapse:collapse;">
                    <tr><td>Invoice No.</td><td style="text-align:right;"><strong>{{ $invoice->invoice_number }}</strong></td></tr>
                    <tr><td>Issue Date</td><td style="text-align:right;">{{ $invoice->issue_date?->format('d M Y') }}</td></tr>
                    <tr><td>Due Date</td><td style="text-align:right;">{{ $invoice->due_date?->format('d M Y') }}</td></tr>
                    @if($bookingRef)
                        <tr><td>Booking Ref</td><td style="text-align:right;">#{{ $bookingRef }}</td></tr>
                    @endif
                    @if($vendorRef)
                        <tr><td>Vendor Ref</td><td style="text-align:right;">{{ $vendorRef }}</td></tr>
                    @endif
                </table>
            </div>
        </td>
    </tr>
</table>

@if($folder)
<table class="booking-details">
    <tr>
        <th>Destination</th>
        <th>Travel Date</th>
        <th>Passengers</th>
        <th>Makkah Ziaraat</th>
        <th>Madinah Ziaraat</th>
    </tr>
    <tr>
        <td>{{ $destination ?? '—' }}</td>
        <td>{{ $travelDate ?? '—' }}</td>
        <td>{{ $passengerCount ?? ($passengers->count() ?: '—') }}</td>
        <td>{{ $ziaraatsMakkah ?? '—' }}</td>
        <td>{{ $ziaraatsMadinah ?? '—' }}</td>
    </tr>
</table>
@endif

<table class="lines">
    <thead>
        <tr>
            <th style="width:50%; text-align:left;">Description</th>
            <th style="width:10%;" class="num">Qty</th>
            <th style="width:20%;" class="num">Unit Price</th>
            <th style="width:20%;" class="num">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->lines as $line)
            <tr>
                <td>{{ $line->description }}</td>
                <td class="num">{{ number_format((float) $line->quantity, 0) }}</td>
                <td class="num">{{ $money($line->unit_price) }}</td>
                <td class="num">{{ $money($line->line_total) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td class="label">Subtotal</td>
        <td class="value">{{ $money($invoice->subtotal) }}</td>
    </tr>
    @if((float) $invoice->tax_amount > 0)
        <tr>
            <td class="label">Tax / VAT</td>
            <td class="value">{{ $money($invoice->tax_amount) }}</td>
        </tr>
    @endif
    <tr class="grand">
        <td class="label">Total</td>
        <td class="value">{{ $money($invoice->total) }}</td>
    </tr>
    <tr>
        <td class="label">Amount Paid</td>
        <td class="value">{{ $money($amountPaid) }}</td>
    </tr>
    <tr>
        <td class="label balance-due">Balance Due</td>
        <td class="value balance-due">{{ $money($balanceDue) }}</td>
    </tr>
    @if($balanceDueDate)
        <tr>
            <td class="label">Balance Due By</td>
            <td class="value">{{ $balanceDueDate }}</td>
        </tr>
    @endif
</table>

@if($bank['bank_name'] || $bank['account_number'] || $paymentInstructions)
<div class="payment-box">
    <strong>Payment Details</strong>
    @if($paymentInstructions)
        <p style="margin:6px 0;">{!! nl2br(e($paymentInstructions)) !!}</p>
    @endif
    <table style="margin-top:6px;">
        @if($bank['bank_name'])
            <tr><td>Bank</td><td><strong>{{ $bank['bank_name'] }}</strong></td></tr>
        @endif
        @if($bank['sort_code'])
            <tr><td>Sort Code</td><td><strong>{{ $bank['sort_code'] }}</strong></td></tr>
        @endif
        @if($bank['account_number'])
            <tr><td>Account No.</td><td><strong>{{ $bank['account_number'] }}</strong></td></tr>
        @endif
        @if($bank['iban'])
            <tr><td>IBAN</td><td><strong>{{ $bank['iban'] }}</strong></td></tr>
        @endif
        @if($bookingRef)
            <tr><td>Payment Reference</td><td><strong>Booking #{{ $bookingRef }}</strong></td></tr>
        @endif
    </table>
</div>
@endif

@if($invoice->notes)
    <p style="margin-top:12px;"><strong>Notes:</strong> {{ $invoice->notes }}</p>
@endif

<div class="footer">{{ $terms }}</div>
</body>
</html>
