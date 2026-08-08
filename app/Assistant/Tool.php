<?php

namespace App\Assistant;

use App\Models\User;

/**
 * One thing the assistant can do on the user's behalf.
 *
 * The contract exists to make a single property hold: **a tool can never do
 * something the signed-in user could not do through the UI.**
 *
 * That is not achieved by prompting. The model decides *which* tool to call and
 * with what arguments, and that decision is influenced by text we do not
 * control — a deal title, a task description, a contact's name, all of which a
 * customer (or someone emailing a customer) can write. Treat every tool
 * argument as attacker-supplied.
 *
 * What holds the line instead:
 *
 *   1. `run()` executes inside the caller's tenant context, so the global scope
 *      on every model already limits it to one organisation.
 *   2. `authorize()` re-runs the same check the HTTP route would, so role and
 *      module limits apply identically.
 *   3. Nothing in a tool may call `withoutGlobalScopes()` or `acrossTenants()`.
 *
 * With those, a fully hijacked model is bounded by what its user could already
 * do — which is the only bound worth relying on.
 */
abstract class Tool
{
    /**
     * Name the model sees. Snake case, verb-first, unambiguous.
     */
    abstract public function name(): string;

    /**
     * What it does and — more usefully — when to reach for it. Recent models
     * are conservative about calling tools; a description that states the
     * trigger condition gets called when it should be.
     */
    abstract public function description(): string;

    /**
     * JSON Schema for the arguments.
     */
    abstract public function schema(): array;

    /**
     * Throw or return false to refuse. Runs before `run()`, always.
     */
    abstract public function authorize(User $user): bool;

    /**
     * Do the thing. Return anything json-encodable.
     */
    abstract public function run(User $user, array $arguments): array;

    /**
     * Does this tool change data?
     *
     * Writes are held for explicit user confirmation before they execute. That
     * is defence in depth rather than the primary control — authorisation is
     * the primary control — but it means a misread instruction costs a click
     * rather than a mistake, and it gives the user a place to notice the
     * assistant misunderstood.
     */
    public function isWrite(): bool
    {
        return false;
    }

    /**
     * One line describing the pending change, shown on the confirmation
     * prompt. Written for someone who did not see the reasoning that led here,
     * so it must name the actual records rather than say "the deal".
     */
    public function describeCall(array $arguments): string
    {
        return $this->name();
    }

    /**
     * The definition sent to the API.
     */
    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'input_schema' => $this->schema(),
        ];
    }
}
