<?php

namespace App\Http\Controllers;

use App\Assistant\Agent;
use App\Models\AssistantConversation;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function __construct(private readonly Agent $agent) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $conversation = AssistantConversation::ownedBy($user)
            ->latest('last_message_at')
            ->first();

        return view('assistant.index', [
            'conversation' => $conversation,
            'messages' => $conversation?->messages()->get() ?? collect(),
            'recent' => AssistantConversation::ownedBy($user)
                ->latest('last_message_at')
                ->limit(10)
                ->get(),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $conversation = $this->conversationFor($request, $data['conversation_id'] ?? null);

        $result = $this->agent->send($user, $conversation, $data['message']);

        return response()->json([
            'conversation_id' => $conversation->id,
            'text' => $result->text,
            'pending' => $result->awaitingConfirmation() ? [
                'tool_use_id' => $result->pendingToolUseId,
                'tool' => $result->pendingTool,
                'description' => $result->pendingDescription,
            ] : null,
        ]);
    }

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'tool_use_id' => ['required', 'string'],
            'approved' => ['required', 'boolean'],
        ]);

        $conversation = $this->conversationFor($request, $data['conversation_id']);

        $result = $this->agent->resumeWithDecision(
            $request->user(),
            $conversation,
            $data['tool_use_id'],
            $data['approved'],
        );

        return response()->json([
            'conversation_id' => $conversation->id,
            'text' => $result->text,
            'pending' => $result->awaitingConfirmation() ? [
                'tool_use_id' => $result->pendingToolUseId,
                'tool' => $result->pendingTool,
                'description' => $result->pendingDescription,
            ] : null,
        ]);
    }

    /**
     * Resolve the conversation, or start one.
     *
     * `ownedBy` on top of the tenant scope: the global scope keeps other
     * organisations out, and this keeps colleagues out of each other's
     * transcripts. A conversation id from another user is simply not found, so
     * the response is a 404 rather than a 403 — which would confirm it exists.
     */
    private function conversationFor(Request $request, ?int $id): AssistantConversation
    {
        $user = $request->user();

        if ($id !== null) {
            return AssistantConversation::ownedBy($user)->findOrFail($id);
        }

        return AssistantConversation::create([
            'user_id' => $user->id,
            'last_message_at' => now(),
        ]);
    }
}
