<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReferralCodeController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => ReferralCode::with('contact')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'contacts.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|max:50',
            'contact_id' => 'nullable|integer',
            'points_reward' => 'nullable|integer|min:0',
            'max_uses' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $code = $request->code ?: strtoupper(Str::random(8));

        $referral = ReferralCode::create([
            'contact_id' => $request->contact_id,
            'code' => $code,
            'points_reward' => $request->points_reward ?? 0,
            'max_uses' => $request->max_uses,
        ]);

        return response()->json(['status' => true, 'data' => $referral], 201);
    }
}
