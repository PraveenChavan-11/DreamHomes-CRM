<?php

namespace App\Services\Agent;

/**
 * The COMPLETE set of capabilities an external agent can ever hold. It is a closed whitelist:
 * there is deliberately no scope for publishing, scheduling, credentials, account configuration,
 * admin/user management or database access, so no configuration or request can grant them.
 */
final class AgentScopes
{
    public const ACCOUNTS_READ   = 'social.accounts.read';
    public const GUIDELINES_READ = 'social.guidelines.read';
    public const TEMPLATES_READ  = 'social.templates.read';
    public const DRAFTS_CREATE   = 'social.drafts.create';
    public const DRAFTS_SUBMIT   = 'social.drafts.submit';
    public const POSTS_READ      = 'social.posts.read';
    public const CALENDAR_READ   = 'social.calendar.read';
    public const ANALYTICS_READ  = 'social.analytics.read';

    /** scope => [label, what it allows, endpoint(s)] — drives the admin permission matrix and the docs. */
    public const CATALOGUE = [
        self::ACCOUNTS_READ   => ['Read social account status', 'Platform, account name, connection and token-health category. Never tokens or platform IDs.', 'GET social/accounts'],
        self::GUIDELINES_READ => ['Read content guidelines', 'Platform limits and TalentHub content rules.', 'GET social/content-guidelines'],
        self::TEMPLATES_READ  => ['Read approved templates', 'Active content templates.', 'GET social/templates'],
        self::DRAFTS_CREATE   => ['Create social drafts', 'Create a draft (status "draft", origin "agent"). Cannot publish or schedule.', 'POST social/posts/drafts'],
        self::DRAFTS_SUBMIT   => ['Submit own draft for human review', 'Moves the agent\'s own draft to "pending approval". Does not approve it.', 'POST social/posts/{id}/submit-approval'],
        self::POSTS_READ      => ['Read own draft status', 'Status, review decision and reviewer note of drafts this agent created.', 'GET social/posts/{id}'],
        self::CALENDAR_READ   => ['Read content calendar', 'Titles, platforms, dates and statuses (no internal notes).', 'GET social/calendar'],
        self::ANALYTICS_READ  => ['Read aggregate analytics', 'Aggregate publishing KPIs only.', 'GET social/analytics'],
    ];

    /** Default for a newly registered agent: read-only + draft creation + request review. */
    public const DEFAULTS = [
        self::ACCOUNTS_READ, self::GUIDELINES_READ, self::TEMPLATES_READ,
        self::DRAFTS_CREATE, self::DRAFTS_SUBMIT, self::POSTS_READ, self::CALENDAR_READ, self::ANALYTICS_READ,
    ];

    public const PROVIDERS = [
        'chatgpt'  => 'ChatGPT',
        'claude'   => 'Claude',
        'internal' => 'TalentHub Internal Automation',
        'custom'   => 'Custom External Agent',
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::CATALOGUE);
    }

    /** Keep only known scopes; unknown/forbidden names are dropped. */
    public static function sanitize(array $requested): array
    {
        return array_values(array_intersect(self::all(), array_map('strval', $requested)));
    }

    public static function parse(?string $stored): array
    {
        return $stored === null || $stored === '' ? [] : self::sanitize(explode(',', $stored));
    }
}
