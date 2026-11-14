<?php

namespace App\Services\Agent;

use CodeIgniter\Database\BaseConnection;

/** Loads and applies the additive agent-foundation migration (idempotent). Never drops or rewrites anything. */
final class AgentSchema
{
    public static function ensure(?BaseConnection $db = null): void
    {
        $db ??= \Config\Database::connect();
        require_once APPPATH . 'Database/Migrations/20261112000001_ai_agent_foundation.php';
        \App\Database\Migrations\AiAgentFoundation::apply($db);
    }

    public static function exists(?BaseConnection $db = null): bool
    {
        $db ??= \Config\Database::connect();
        return $db->tableExists('ai_agents') && $db->tableExists('ai_agent_tokens');
    }
}
