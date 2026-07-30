<?php

namespace App\Support;

/**
 * Single source of truth for in-app help: page tours and the searchable
 * articles in the help drawer. Keeping the copy here (rather than scattered
 * through Blade) means it can be searched, reused and reviewed in one place.
 */
class HelpLibrary
{
    /**
     * Guided tours, keyed by route name pattern. Each step points at a CSS
     * selector on the page; the tour skips steps whose target is absent.
     */
    public static function tours(): array
    {
        return [
            'home' => [
                'title' => 'Your dashboard',
                'steps' => [
                    ['target' => '[data-tour="metrics"]', 'title' => 'Your numbers at a glance', 'body' => 'Open deals, tasks due today, who is on leave, and the next payday — refreshed every time you load the page.'],
                    ['target' => '[data-tour="my-tasks"]', 'title' => 'What needs you today', 'body' => 'Your own open work, soonest deadline first. Overdue items turn orange.'],
                    ['target' => '[data-tour="pipeline"]', 'title' => 'Sales at a glance', 'body' => 'Value by pipeline stage. Click through to the full pipeline to drag deals along.'],
                ],
            ],
            'tasks.board' => [
                'title' => 'Using the task board',
                'steps' => [
                    ['target' => '[data-tour="board-columns"]', 'title' => 'Drag to update', 'body' => 'Drag any card into another column. The new status saves immediately — no save button.'],
                    ['target' => '[data-tour="board-filters"]', 'title' => 'Narrow the view', 'body' => 'Filter by category or assignee when the board gets busy.'],
                    ['target' => '[data-tour="new-task"]', 'title' => 'Add work', 'body' => 'Every new task gets a reference like SAL-2026-JUL-014-AK so you can quote it in email.'],
                ],
            ],
            'crm.deals.index' => [
                'title' => 'Working the pipeline',
                'steps' => [
                    ['target' => '[data-tour="pipeline-columns"]', 'title' => 'Five stages', 'body' => 'Drag deals from Lead through to Won or Lost. Stage totals update as you go.'],
                    ['target' => '[data-tour="new-deal"]', 'title' => 'Log a new opportunity', 'body' => 'Add a deal with its value and expected close date to see it in your forecast.'],
                ],
            ],
            'hr.leave.index' => [
                'title' => 'Leave, end to end',
                'steps' => [
                    ['target' => '[data-tour="leave-request"]', 'title' => 'Request time off', 'body' => 'Pick your dates — working days are counted automatically, weekends excluded.'],
                    ['target' => '[data-tour="leave-balances"]', 'title' => 'Track your allowance', 'body' => 'Your remaining days per leave type, kept up to date as requests are approved.'],
                ],
            ],
        ];
    }

    public static function tourFor(string $routeName): ?array
    {
        return static::tours()[$routeName] ?? null;
    }

    /**
     * Help drawer articles. Plain arrays keep them searchable without a CMS.
     */
    public static function articles(): array
    {
        return [
            [
                'section' => 'Getting started',
                'title' => 'How Radical AI fits together',
                'body' => 'Three modules share one team and one database. Winning a deal in CRM creates a delivery project with tasks. Approving leave in HR makes that person unavailable for new task assignments and shows them on the shared calendar. You never re-enter the same person twice.',
                'keywords' => 'overview modules crm tasks hr introduction',
            ],
            [
                'section' => 'Getting started',
                'title' => 'Roles and what each can see',
                'body' => 'Admins manage settings, billing and payslips for everyone. Managers see the whole team’s work and approve leave. Employees see their own tasks, their own leave and their own payslips — payslip access is enforced on the server, not just hidden in the interface.',
                'keywords' => 'roles permissions admin manager employee access security',
            ],
            [
                'section' => 'Tasks',
                'title' => 'What the reference numbers mean',
                'body' => 'A reference like SAL-2026-JUL-014-AK reads as: category prefix, year, month, a sequential number for your organisation, and the assignee’s initials. They are generated automatically and never reused.',
                'keywords' => 'reference number task id naming convention',
            ],
            [
                'section' => 'Tasks',
                'title' => 'Progress updates and reports',
                'body' => 'Each task has a running thread of progress updates. The report generator turns those threads into a shareable status report, so keeping the diary up to date produces your weekly report for free.',
                'keywords' => 'progress updates reports status weekly thread',
            ],
            [
                'section' => 'CRM',
                'title' => 'What happens when a deal is won',
                'body' => 'Marking a deal won creates a delivery project linked back to that deal, plus onboarding tasks (kick-off call, statement of work, workspace setup). Those tasks are assigned only to people who are not on approved leave.',
                'keywords' => 'deal won project onboarding automation glue',
            ],
            [
                'section' => 'HR',
                'title' => 'The leave approval flow',
                'body' => 'An employee submits dates and a reason; working days are counted automatically. A manager approves or rejects. On approval the balance is deducted, the calendar updates, and the person is marked unavailable for new work.',
                'keywords' => 'leave holiday approval calendar balance absence',
            ],
            [
                'section' => 'HR',
                'title' => 'Payslip privacy',
                'body' => 'Admins upload payslip PDFs with the period and gross/net figures. Employees can only ever list and download their own — the download route checks ownership on every request.',
                'keywords' => 'payslip payroll privacy download salary',
            ],
            [
                'section' => 'Security',
                'title' => 'Two-factor authentication',
                'body' => 'Turn on 2FA from your profile. Scan the QR code with any authenticator app, confirm one code to prove it works, then save your recovery codes somewhere safe — they are the way back in if you lose your phone.',
                'keywords' => '2fa two factor mfa authenticator security totp recovery',
            ],
            [
                'section' => 'Security',
                'title' => 'How your data is separated',
                'body' => 'Every record carries an organisation id and every query is filtered by it automatically. The filter fails closed: with no organisation context a query returns nothing rather than everything, and writes that cross organisations are blocked outright.',
                'keywords' => 'tenant isolation data separation privacy multi-tenant security',
            ],
        ];
    }
}
