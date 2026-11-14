<?php

define('HOMEPATH', __DIR__ . '/../');
define('CONFIGPATH', __DIR__ . '/../app/Config/');
require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

$db = \Config\Database::connect();
$roles = $db->table('roles')->get()->getResultArray();
$permissions = $db->table('permissions')->get()->getResultArray();
$rolePermissions = $db->table('role_permissions')->get()->getResultArray();
$emailTemplates = $db->table('email_templates')->get()->getResultArray();
$websiteSettings = $db->table('website_settings')->get()->getResultArray();

file_put_contents(__DIR__ . '/roles_and_perms.json', json_encode([
    'roles'            => $roles,
    'permissions'      => $permissions,
    'role_permissions' => $rolePermissions,
    'email_templates'  => array_map(function($t) {
        return [
            'id'             => $t['id'],
            'code'           => $t['code'],
            'name'           => $t['name'],
            'subject'        => $t['subject'],
            'is_active'      => $t['is_active'],
            'variables_help' => $t['variables_help'] ?? '',
        ];
    }, $emailTemplates),
    'settings_keys'    => array_map(function($s) {
        return [
            'key'       => $s['setting_key'],
            'group'     => $s['setting_group'] ?? '',
            'value'     => $s['setting_value'] ?? '',
            'is_public' => $s['is_public'] ?? 0,
        ];
    }, $websiteSettings)
], JSON_PRETTY_PRINT));

echo "SUCCESS: Dumped roles, perms, templates, and settings keys.\n";
