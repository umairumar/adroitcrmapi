<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ConversationThread;
use App\Models\Message;
use App\Services\Auth\AuthorizationService;
use App\Services\Engagement\MessagingService;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly MessagingService $messaging,
    ) {}

    public function index(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'inbox.view')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $q = ConversationThread::with(['contact', 'lead'])
            ->orderByDesc('last_message_at');

        if ($request->filled('channel')) {
            $q->where('channel', $request->channel);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        return response()->json(['status' => true, 'data' => $q->paginate(20)]);
    }

    public function show(int $id)
    {
        $thread = ConversationThread::with(['messages' => fn ($q) => $q->orderBy('created_at')])->findOrFail($id);

        return response()->json(['status' => true, 'data' => $thread]);
    }

    public function reply(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'inbox.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate(['body' => 'required|string']);

        $thread = ConversationThread::findOrFail($id);
        $message = $this->messaging->send($thread, $request->body, $request->subject, $request->user()->id);

        return response()->json(['status' => true, 'data' => $message]);
    }

    public function assign(Request $request, int $id)
    {
        $thread = ConversationThread::findOrFail($id);
        $thread->update(['assigned_user_id' => $request->user_id]);

        return response()->json(['status' => true, 'data' => $thread]);
    }
}
