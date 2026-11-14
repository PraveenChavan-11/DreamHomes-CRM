<?php

$data = json_decode(file_get_contents('scratch/roles_and_perms.json'), true);
echo "=== ROLES ===\n";
foreach ($data['roles'] as $r) {
    echo "- ID: {$r['id']} | Slug: {$r['slug']} | Name: {$r['name']}\n";
}

echo "\n=== PERMISSIONS ===\n";
foreach ($data['permissions'] as $p) {
    echo "- [{$p['category']}] {$p['code']}: {$p['name']}\n";
}

echo "\n=== EMAIL TEMPLATES ===\n";
foreach ($data['email_templates'] as $t) {
    echo "- Code: {$t['code']} | Name: {$t['name']} | Subject: {$t['subject']}\n";
}

