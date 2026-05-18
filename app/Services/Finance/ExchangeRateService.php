<?php

namespace App\Services\Finance;

use App\Models\ExchangeRate;
use Carbon\Carbon;

class ExchangeRateService
{
    public function convert(float $amount, string $from, string $to, ?Carbon $date = null): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getRate($from, $to, $date);

        return round($amount * $rate, 2);
    }

    public function getRate(string $from, string $to, ?Carbon $date = null): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ?? Carbon::today();

        $row = ExchangeRate::where('from_currency', $from)
            ->where('to_currency', $to)
            ->whereDate('rate_date', '<=', $date)
            ->orderByDesc('rate_date')
            ->first();

        if ($row) {
            return (float) $row->rate;
        }

        $inverse = ExchangeRate::where('from_currency', $to)
            ->where('to_currency', $from)
            ->whereDate('rate_date', '<=', $date)
            ->orderByDesc('rate_date')
            ->first();

        if ($inverse && (float) $inverse->rate > 0) {
            return 1 / (float) $inverse->rate;
        }

        return 1.0;
    }
}
