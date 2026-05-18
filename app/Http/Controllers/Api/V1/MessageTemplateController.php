<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Services\Auth\AuthorizationService;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
    ) {}

    public function index(Request $request)
    {
        $q = MessageTemplate::query();
        if ($request->filled('channel')) {
            $q->where('channel', $request->channel);
        }

        return response()->json(['status' => true, 'data' => $q->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'inbox.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $template = MessageTemplate::create($request->only([
            'name', 'channel', 'subject', 'body', 'variables', 'is_active',
        ]));

        return response()->json(['status' => true, 'data' => $template], 201);
    }
}
