<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Services\Finance\ExchangeRateService;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function __construct(
        private readonly ExchangeRateService $fx,
    ) {}

    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => ExchangeRate::orderByDesc('rate_date')->limit(100)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0',
            'rate_date' => 'required|date',
        ]);

        $row = ExchangeRate::updateOrCreate(
            [
                'from_currency' => strtoupper($request->from_currency),
                'to_currency' => strtoupper($request->to_currency),
                'rate_date' => $request->rate_date,
            ],
            ['rate' => $request->rate]
        );

        return response()->json(['status' => true, 'data' => $row], 201);
    }

    public function convert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'converted' => $this->fx->convert(
                    (float) $request->amount,
                    strtoupper($request->from),
                    strtoupper($request->to),
                ),
            ],
        ]);
    }
}
