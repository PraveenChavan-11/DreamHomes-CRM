<?php

$baseUrl = 'https://dhiscrm.talenthubconsultant.com';
$cookieFile = sys_get_temp_dir() . '/dhis_live_cookie_reset.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function req($url, $method = 'GET', $data = [], $cookieFile = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $err = curl_error($ch);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => $response,
        'url'  => $finalUrl,
        'err'  => $err
    ];
}

echo "=== 1. Logging in as demo.admin1 ===\n";
$resLoginGet = req($baseUrl . '/crm/login', 'GET', [], $cookieFile);
preg_match('/name="([^"]*csrf[^"]*)" value="([^"]+)"/i', $resLoginGet['body'], $csrfMatch);
$csrfName = $csrfMatch[1] ?? 'csrf_test_name';
$csrfToken = $csrfMatch[2] ?? '';

$resLoginPost = req($baseUrl . '/crm/login', 'POST', [
    $csrfName => $csrfToken,
    'identity' => 'demo.admin1',
    'password' => 'Demo@DHIS#2026',
], $cookieFile);

echo "Login status: HTTP {$resLoginPost['code']} [Final: {$resLoginPost['url']}]\n";

echo "=== 2. Fetching Demo Control Page to get new CSRF token ===\n";
$resDemoGet = req($baseUrl . '/crm/demo', 'GET', [], $cookieFile);
echo "Demo page: HTTP {$resDemoGet['code']}\n";

preg_match('/name="([^"]*csrf[^"]*)" value="([^"]+)"/i', $resDemoGet['body'], $csrfMatch2);
$csrfName2 = $csrfMatch2[1] ?? 'csrf_test_name';
$csrfToken2 = $csrfMatch2[2] ?? '';
echo "CSRF token: " . substr($csrfToken2, 0, 10) . "...\n";

echo "=== 3. Sending Demo Reset POST ('RESET DEMO') ===\n";
$t0 = microtime(true);
$resReset = req($baseUrl . '/crm/demo/reset', 'POST', [
    $csrfName2 => $csrfToken2,
    'confirm' => 'RESET DEMO',
], $cookieFile);
$dur = microtime(true) - $t0;
echo "Reset response in " . round($dur, 2) . "s: HTTP {$resReset['code']} [Final: {$resReset['url']}]\n";
if (strpos($resReset['body'], 'Snapshot saved') !== false || strpos($resReset['body'], 'Demo data was reset') !== false) {
    echo "SUCCESS: Demo reset completed!\n";
} else {
    echo "Output snippet:\n" . substr(strip_tags($resReset['body']), 0, 500) . "\n";
}
