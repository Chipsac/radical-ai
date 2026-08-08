<?php

namespace App\Assistant;

use Anthropic\Client;
use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Drives the conversation: send a turn, run whatever read tools the model asks
 * for, and stop when it either answers or asks to change something.
 *
 * A manual loop rather than the SDK's tool runner, for one reason: a write has
 * to pause the turn and wait for the user to click confirm, which happens in a
 * *later HTTP request*. The tool runner's hooks gate within a single loop; they
 * cannot suspend a turn across requests. So the loop belongs here.
 */
class Agent
{
    /**
     * Claude Opus 5. Tool-calling accuracy is the thing that matters here — a
     * model that picks the wrong tool or invents a deal id writes to a
     * customer's CRM — so this is not the place to trade capability for price.
     */
    public const MODEL = 'claude-opus-5';

    /**
     * Cap on tool round-trips within one turn. Not a correctness control (the
     * model stops on its own); a spend control, so a pathological loop costs
     * bounded money.
     */
    private const MAX_ITERATIONS = 8;

    public function __construct(
        private readonly Client $client,
        private readonly ToolRegistry $tools,
    ) {}

    /**
     * Continue the conversation. Returns either a finished answer or a pending
     * write awaiting confirmation.
     */
    public function send(User $user, AssistantConversation $conversation, string $userText): AgentResult
    {
        $this->store($conversation, 'user', [['type' => 'text', 'text' => $userText]]);

        return $this->run($user, $conversation);
    }

    /**
     * Resume after the user has answered a confirmation prompt.
     *
     * The decision is re-checked here rather than trusted from the client: the
     * confirmation token identifies which pending call was approved, and the
     * tool's own authorisation still runs when it executes.
     */
    public function resumeWithDecision(
        User $user,
        AssistantConversation $conversation,
        string $toolUseId,
        bool $approved,
    ): AgentResult {
        $pending = $this->pendingToolUse($conversation, $toolUseId);

        if ($pending === null) {
            return AgentResult::answered('That action is no longer pending.');
        }

        $result = $approved
            ? $this->tools->execute($user, $pending['name'], $pending['input'] ?? [], $conversation->id)
            : ['error' => 'The user declined this action. Do not retry it; ask what they would like instead.'];

        $this->store($conversation, 'user', [[
            'type' => 'tool_result',
            'tool_use_id' => $toolUseId,
            'content' => json_encode($result),
            'is_error' => isset($result['error']),
        ]]);

        return $this->run($user, $conversation);
    }

    private function run(User $user, AssistantConversation $conversation): AgentResult
    {
        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            try {
                $response = $this->client->messages->create(
                    maxTokens: 8000,
                    messages: $this->history($conversation),
                    model: self::MODEL,
                    // Auto-caches the last cacheable block. The system prompt and
                    // the tool list are both stable for a given user, so this
                    // buys a cache read on every turn after the first.
                    cacheControl: ['type' => 'ephemeral'],
                    system: SystemPrompt::for($user),
                    thinking: ['type' => 'adaptive'],
                    tools: $this->tools->definitionsFor($user),
                );
            } catch (Throwable $e) {
                Log::error('Assistant API call failed', [
                    'user_id' => $user->id,
                    'conversation_id' => $conversation->id,
                    'exception' => $e->getMessage(),
                ]);

                return AgentResult::answered(
                    'The assistant is unavailable right now. Please try again in a moment.'
                );
            }

            $blocks = $this->blocksToArray($response->content);

            $this->store(
                $conversation,
                'assistant',
                $blocks,
                $response->usage->inputTokens ?? 0,
                $response->usage->outputTokens ?? 0,
                $response->usage->cacheReadInputTokens ?? 0,
            );

            if ($response->stopReason !== 'tool_use') {
                return AgentResult::answered($this->textOf($blocks));
            }

            $calls = array_values(array_filter($blocks, fn ($b) => ($b['type'] ?? null) === 'tool_use'));

            // A write stops the turn. Everything the model asked for in the same
            // turn stops with it — running the reads and holding only the write
            // would leave the conversation in a state the model did not plan
            // for, and the user would confirm a change whose context had
            // already half-executed.
            foreach ($calls as $call) {
                $tool = $this->tools->get($call['name']);

                if ($tool?->isWrite()) {
                    return AgentResult::needsConfirmation(
                        $call['id'],
                        $call['name'],
                        $tool->describeCall($call['input'] ?? []),
                        $this->textOf($blocks),
                    );
                }
            }

            // All reads: run them and feed every result back in one user turn.
            // Splitting them across turns teaches the model to stop asking for
            // things in parallel.
            $results = array_map(fn ($call) => [
                'type' => 'tool_result',
                'tool_use_id' => $call['id'],
                'content' => json_encode(
                    $this->tools->execute($user, $call['name'], $call['input'] ?? [], $conversation->id)
                ),
            ], $calls);

            $this->store($conversation, 'user', $results);
        }

        return AgentResult::answered(
            'That turned into more steps than expected, so I stopped. Could you narrow it down?'
        );
    }

    /**
     * Find a tool_use the model actually asked for in this conversation.
     *
     * Scanning stored history rather than trusting the id from the browser: it
     * means a crafted request cannot execute a tool the model never proposed,
     * with arguments the user never saw on the confirmation prompt.
     */
    private function pendingToolUse(AssistantConversation $conversation, string $toolUseId): ?array
    {
        $last = $conversation->messages()->where('role', 'assistant')->latest('id')->first();

        foreach ($last?->blocks ?? [] as $block) {
            if (($block['type'] ?? null) === 'tool_use' && ($block['id'] ?? null) === $toolUseId) {
                return $block;
            }
        }

        return null;
    }

    private function history(AssistantConversation $conversation): array
    {
        return $conversation->messages()->get()
            ->map(fn (AssistantMessage $m) => ['role' => $m->role, 'content' => $m->blocks])
            ->all();
    }

    private function store(
        AssistantConversation $conversation,
        string $role,
        array $blocks,
        int $input = 0,
        int $output = 0,
        int $cacheRead = 0,
    ): void {
        $conversation->messages()->create([
            'organization_id' => $conversation->organization_id,
            'role' => $role,
            'blocks' => $blocks,
            'input_tokens' => $input,
            'output_tokens' => $output,
            'cache_read_tokens' => $cacheRead,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();
    }

    /**
     * Normalise SDK response blocks to plain arrays for storage and replay.
     *
     * Thinking blocks are kept exactly as received — the API rejects modified
     * ones, and dropping them breaks the turn.
     */
    private function blocksToArray(array $content): array
    {
        return array_map(
            fn ($block) => is_array($block) ? $block : json_decode(json_encode($block), true),
            $content
        );
    }

    private function textOf(array $blocks): string
    {
        return collect($blocks)
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n\n");
    }
}
