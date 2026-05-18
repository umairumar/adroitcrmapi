<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TenantWebhookEndpoint;
use App\Models\WebhookDelivery;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class WebhookEndpointController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function events()
    {
        return response()->json([
            'status' => true,
            'data' => config('integrations.webhooks.events', []),
        ]);
    }

    public function index(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'webhooks.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => TenantWebhookEndpoint::orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'webhooks.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'secret' => 'nullable|string|min:8',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $endpoint = TenantWebhookEndpoint::create([
            'url' => $request->url,
            'events' => $request->events,
            'description' => $request->description,
            'secret' => $request->secret ? Crypt::encryptString($request->secret) : null,
            'is_active' => true,
        ]);

        return response()->json(['status' => true, 'data' => $endpoint], 201);
    }

    public function update(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'webhooks.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $endpoint = TenantWebhookEndpoint::findOrFail($id);

        $data = $request->only(['url', 'events', 'description', 'is_active']);
        if ($request->filled('secret')) {
            $data['secret'] = Crypt::encryptString($request->secret);
        }

        $endpoint->update($data);

        return response()->json(['status' => true, 'data' => $endpoint]);
    }

    public function destroy(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'webhooks.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        TenantWebhookEndpoint::findOrFail($id)->delete();

        return response()->json(['status' => true, 'message' => 'Webhook endpoint removed']);
    }

    public function deliveries(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'webhooks.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $endpoint = TenantWebhookEndpoint::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => WebhookDelivery::where('endpoint_id', $endpoint->id)
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
        ]);
    }
}
