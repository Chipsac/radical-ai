<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Models\Deal;
use App\Models\User;
use App\Support\Modules;

class FindDeals extends Tool
{
    public function name(): string
    {
        return 'find_deals';
    }

    public function description(): string
    {
        return 'Search the deal pipeline. Call this whenever the question concerns '
            .'deals, opportunities, the pipeline, forecast or a named company\'s '
            .'sales status — including before moving a deal, to resolve the name '
            .'the person used into an actual deal id.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Free text matched against deal title and account name. Omit to list all.',
                ],
                'stage' => [
                    'type' => 'string',
                    'enum' => Deal::STAGES,
                    'description' => 'Restrict to one pipeline stage.',
                ],
                'mine_only' => [
                    'type' => 'boolean',
                    'description' => 'Only deals owned by the person asking. Default false.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->hasModule(Modules::CRM);
    }

    public function run(User $user, array $arguments): array
    {
        // No withoutGlobalScopes anywhere: the tenant scope on Deal is what
        // keeps this inside one organisation, and it fails closed.
        $deals = Deal::with(['account', 'owner'])
            ->when(
                filled($arguments['query'] ?? null),
                fn ($q) => $q->where(function ($q) use ($arguments) {
                    $term = '%'.$arguments['query'].'%';
                    $q->where('title', 'like', $term)
                        ->orWhereHas('account', fn ($a) => $a->where('name', 'like', $term));
                })
            )
            ->when(
                filled($arguments['stage'] ?? null),
                fn ($q) => $q->where('stage', $arguments['stage'])
            )
            // An employee sees their own deals; a manager sees the team's. Same
            // rule the pipeline view applies — not a looser one because the
            // request arrived through chat.
            ->when(
                ($arguments['mine_only'] ?? false) || ! $user->isManager(),
                fn ($q) => $q->where('owner_id', $user->id)
            )
            ->orderByDesc('value')
            ->limit(25)
            ->get();

        return [
            'count' => $deals->count(),
            'deals' => $deals->map(fn (Deal $deal) => [
                'id' => $deal->id,
                'title' => $deal->title,
                'account' => $deal->account?->name,
                'stage' => $deal->stage,
                'value' => $deal->value,
                'owner' => $deal->owner?->name,
                'expected_close_date' => $deal->expected_close_date?->toDateString(),
            ])->all(),
        ];
    }
}
