<?php

namespace App\Assistant;

use App\Models\AssistantAction;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Holds the tool catalogue and is the only place a tool is ever executed.
 *
 * Centralising execution means the authorisation check, the audit row and the
 * error handling cannot be forgotten by an individual tool — a tool author
 * writes `run()` and gets all three.
 */
class ToolRegistry
{
    /** @var array<string, Tool> */
    private array $tools = [];

    public function __construct()
    {
        foreach ([
            new Tools\MySchedule,
            new Tools\FindDeals,
            new Tools\MoveDealStage,
            new Tools\InviteTeamMember,
        ] as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    public function get(string $name): ?Tool
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Definitions for the tools this user may actually use.
     *
     * Filtered by authorisation rather than sent whole: a user without CRM
     * should not be told the CRM tools exist, and offering a tool that will
     * certainly be refused just wastes a turn watching the model try it.
     *
     * Ordering is stable (registration order) because the tool block is part of
     * the cached prompt prefix — reordering it on every request would silently
     * cost a full cache write each time.
     */
    public function definitionsFor(User $user): array
    {
        return array_values(array_map(
            fn (Tool $tool) => $tool->definition(),
            array_filter($this->tools, fn (Tool $tool) => $this->permits($tool, $user))
        ));
    }

    public function permits(Tool $tool, User $user): bool
    {
        // The assistant module itself is the outer gate; a tool cannot be a way
        // in for someone who was never granted the assistant.
        if (! $user->hasModule(Modules::ASSISTANT)) {
            return false;
        }

        try {
            return $tool->authorize($user);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Run a tool as the given user, recording what happened.
     *
     * Returns a result array for the model. Failures are returned rather than
     * thrown: the model needs to see "you may not do that" so it can tell the
     * user, and an exception escaping here would end the whole turn.
     */
    public function execute(User $user, string $name, array $arguments, ?int $conversationId = null): array
    {
        $tool = $this->get($name);

        if ($tool === null) {
            return ['error' => sprintf('No tool named %s.', $name)];
        }

        $record = fn (string $outcome, ?string $detail = null) => AssistantAction::create([
            'user_id' => $user->id,
            'assistant_conversation_id' => $conversationId,
            'tool' => $name,
            'arguments' => $arguments,
            'outcome' => $outcome,
            'detail' => $detail,
        ]);

        if (! $this->permits($tool, $user)) {
            $record(AssistantAction::REFUSED, 'not authorised');

            return ['error' => 'You do not have access to that part of Crewly360.'];
        }

        try {
            $result = $tool->run($user, $arguments);

            // Only writes are worth an audit row; logging every read would bury
            // the writes in noise without telling anyone anything they could
            // not get from the transcript.
            if ($tool->isWrite()) {
                $record(AssistantAction::EXECUTED);
            }

            return $result;
        } catch (AuthorizationException $e) {
            $record(AssistantAction::REFUSED, $e->getMessage());

            return ['error' => $e->getMessage()];
        } catch (Throwable $e) {
            $record(AssistantAction::FAILED, $e->getMessage());

            // The message can carry SQL, table names or another tenant's ids.
            // Log it for us; tell the model something it can act on.
            Log::error('Assistant tool failed', [
                'tool' => $name,
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);

            return ['error' => 'That did not work. Tell the user it failed and do not retry the same call.'];
        }
    }
}
