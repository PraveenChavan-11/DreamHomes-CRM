<?php

namespace App\Commands;

use App\Services\IntegrationClientService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark integration:create-client worksphere-prod ["Label"]
 * Creates the client, or rotates its secret if it already exists (previous secret stays valid 24 h).
 * The secret is printed exactly once and is never stored in clear text.
 */
class IntegrationCreateClient extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'integration:create-client';
    protected $description = 'Create or rotate a signed-integration client (prints the secret once)';
    protected $usage       = 'integration:create-client <key_id> [label]';

    public function run(array $params)
    {
        $keyId = strtolower(trim((string) ($params[0] ?? '')));
        if (!IntegrationClientService::validKeyId($keyId)) {
            CLI::error('Usage: php spark integration:create-client <key_id> [label]  (key id: 3-63 chars a-z 0-9 - _)');
            return EXIT_USER_INPUT;
        }
        if (!IntegrationClientService::isConfigured()) {
            CLI::error('encryption.key is not set. Set it in the server environment/.env first (php spark key:generate), then retry.');
            return EXIT_ERROR;
        }
        $res = (new IntegrationClientService())->createOrRotate($keyId, (string) ($params[1] ?? ''));
        CLI::write(($res['created'] ? 'Created' : 'Rotated') . " client '{$res['key_id']}'.", 'green');
        CLI::write('Secret (shown once - copy it into the sender configuration now):', 'yellow');
        CLI::write($res['secret']);
        return EXIT_SUCCESS;
    }
}
