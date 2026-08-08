<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Models\Deal;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Auth\Access\AuthorizationException;

class MoveDealStage extends Tool
{
    public function name(): string
    {
        return 'move_deal_stage';
    }

    public function description(): string
    {
        return 'Move a deal to a different pipeline stage. Requires the deal id — '
            .'call find_deals first to resolve a company or deal name into one. '
            .'Do not guess an id.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'deal_id' => [
                    'type' => 'integer',
                    'description' => 'Id from find_deals.',
                ],
                'stage' => [
                    'type' => 'string',
                    'enum' => Deal::STAGES,
                ],
            ],
            'required' => ['deal_id', 'stage'],
            'additionalProperties' => false,
        ];
    }

    public function isWrite(): bool
    {
        return true;
    }

    public function authorize(User $user): bool
    {
        return $user->hasModule(Modules::CRM);
    }

    public function describeCall(array $arguments): string
    {
        // Resolved through the tenant scope, so a deal id belonging to another
        // organisation renders as "unknown deal" rather than leaking its title
        // into the confirmation prompt.
        $deal = Deal::find($arguments['deal_id'] ?? 0);

        return $deal
            ? sprintf('Move "%s" to %s', $deal->title, $arguments['stage'] ?? '?')
            : sprintf('Move deal #%s (not found) to %s', $arguments['deal_id'] ?? '?', $arguments['stage'] ?? '?');
    }

    public function run(User $user, array $arguments): array
    {
        // find() rather than findOrFail() through the scoped query: a deal in
        // another organisation is simply not visible, so this is a plain
        // not-found and never a "you may not touch that", which would confirm
        // the row exists.
        $deal = Deal::find($arguments['deal_id']);

        if ($deal === null) {
            return ['error' => 'No deal with that id exists in this workspace.'];
        }

        // Ownership check mirrors DealController::authorizeDeal — an employee
        // may only move their own deals.
        if (! $user->isManager() && $deal->owner_id !== $user->id) {
            throw new AuthorizationException('You can only move deals you own.');
        }

        $from = $deal->stage;
        $deal->update(['stage' => $arguments['stage']]);

        return [
            'ok' => true,
            'deal_id' => $deal->id,
            'title' => $deal->title,
            'moved_from' => $from,
            'moved_to' => $deal->stage,
        ];
    }
}
