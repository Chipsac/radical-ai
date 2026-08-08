<?php

namespace App\Support;

/**
 * The three product modules a user can be granted access to, independently of
 * their role.
 *
 * Role and module access answer different questions:
 *   - role   = how much you can do (employee sees own work, manager sees the team)
 *   - module = which parts of the product you can see at all
 *
 * A manager with CRM access only still manages the team — but only in CRM.
 */
class Modules
{
    public const CRM = 'crm';

    public const TASKS = 'tasks';

    public const HR = 'hr';

    /**
     * The AI assistant. Included in ALL so it flows through the existing grant
     * UI and middleware unchanged, but listed in ADD_ONS because it is billed
     * separately — granting a user the module does not entitle the
     * organisation to it, and buying it does not grant it to every user.
     */
    public const ASSISTANT = 'assistant';

    public const ALL = [self::CRM, self::TASKS, self::HR, self::ASSISTANT];

    /**
     * Modules the customer pays extra for, on top of the base subscription.
     */
    public const ADD_ONS = [self::ASSISTANT];

    /**
     * Presentation metadata, used by the grant UI and the empty state.
     */
    public static function catalogue(): array
    {
        return [
            self::CRM => [
                'name' => 'CRM',
                'description' => 'Deal pipeline, leads and accounts',
                'icon' => 'deals',
                'route' => 'crm.deals.index',
            ],
            self::TASKS => [
                'name' => 'Daily tracker',
                'description' => 'Task board, progress updates and reports',
                'icon' => 'board',
                'route' => 'tasks.board',
            ],
            self::HR => [
                'name' => 'HR',
                'description' => 'Directory, leave, calendar and payslips',
                'icon' => 'people',
                'route' => 'hr.index',
            ],
            self::ASSISTANT => [
                'name' => 'Assistant',
                'description' => 'Ask for anything you could do yourself, in plain English',
                'icon' => 'sparkle',
                'route' => 'assistant.index',
                'add_on' => true,
            ],
        ];
    }

    public static function isAddOn(string $module): bool
    {
        return in_array($module, self::ADD_ONS, true);
    }

    public static function label(string $module): string
    {
        return static::catalogue()[$module]['name'] ?? ucfirst($module);
    }

    public static function isValid(string $module): bool
    {
        return in_array($module, self::ALL, true);
    }

    /**
     * Normalise arbitrary input to a clean, ordered list of valid modules.
     */
    public static function sanitise(array $modules): array
    {
        return array_values(array_intersect(self::ALL, array_unique($modules)));
    }
}
