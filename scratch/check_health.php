<?php
$baseUrl = 'https://dhiscrm.talenthubconsultant.com';
$cookieFile = sys_get_temp_dir() . '/dhis_live_cookie_h.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function req($url, $method = 'GET', $data = [], $cookieFile = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

$rLogin = req($baseUrl . '/crm/login', 'GET', [], $cookieFile);
preg_match('/name="([^"]*csrf[^"]*)" value="([^"]+)"/i', $rLogin['body'], $csrfMatch);
$csrfName = $csrfMatch[1] ?? 'csrf_test_name';
$csrfToken = $csrfMatch[2] ?? '';

req($baseUrl . '/crm/login', 'POST', [
    $csrfName => $csrfToken,
    'identity' => 'demo.admin1',
    'password' => 'Demo@DHIS#2026',
], $cookieFile);

$r = req($baseUrl . '/crm/health', 'GET', [], $cookieFile);
echo 'Health page HTTP: ' . $r['code'] . PHP_EOL;
preg_match('/<main[^>]*>(.*?)<\/main>/s', $r['body'], $m);
echo substr(strip_tags($m[1] ?? $r['body']), 0, 1500);
