<?php

namespace App\Services\Finance;

use App\Models\TaxRate;

class TaxService
{
    public function calculate(float $netAmount, ?int $taxRateId = null): array
    {
        $rate = $taxRateId
            ? TaxRate::find($taxRateId)
            : TaxRate::where('is_default', true)->where('is_active', true)->first();

        if (! $rate) {
            return ['tax_amount' => 0.0, 'gross' => $netAmount, 'rate' => 0];
        }

        $tax = round($netAmount * ((float) $rate->rate / 100), 2);

        return [
            'tax_amount' => $tax,
            'gross' => round($netAmount + $tax, 2),
            'rate' => (float) $rate->rate,
            'tax_rate_id' => $rate->id,
        ];
    }
}
