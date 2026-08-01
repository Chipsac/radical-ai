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

    public const ALL = [self::CRM, self::TASKS, self::HR];

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
        ];
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
