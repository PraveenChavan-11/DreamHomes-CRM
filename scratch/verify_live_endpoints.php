<?php

$baseUrl = 'https://dhiscrm.talenthubconsultant.com';
$cookieFile = sys_get_temp_dir() . '/dhis_live_cookie.txt';
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

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

echo "=== 1. Testing Root URL redirect ===\n";
$resRoot = req($baseUrl . '/', 'GET', [], $cookieFile);
echo "GET / -> HTTP {$resRoot['code']} [Final: {$resRoot['url']}]\n";

echo "\n=== 2. Testing Login Page ===\n";
$resLoginGet = req($baseUrl . '/crm/login', 'GET', [], $cookieFile);
echo "GET /crm/login -> HTTP {$resLoginGet['code']}\n";

// Grab CSRF token if present
preg_match('/name="([^"]*csrf[^"]*)" value="([^"]+)"/i', $resLoginGet['body'], $csrfMatch);
$csrfName = $csrfMatch[1] ?? 'csrf_test_name';
$csrfToken = $csrfMatch[2] ?? '';
echo "CSRF: {$csrfName} = " . ($csrfToken ? substr($csrfToken, 0, 10) . '...' : 'none') . "\n";

echo "\n=== 3. Authenticating demo.admin1 ===\n";
$postData = [
    'identity' => 'demo.admin1',
    'password' => 'Demo@DHIS#2026',
];
if ($csrfToken) {
    $postData[$csrfName] = $csrfToken;
}
$resLoginPost = req($baseUrl . '/crm/login', 'POST', $postData, $cookieFile);
echo "POST /crm/login -> HTTP {$resLoginPost['code']} [Final: {$resLoginPost['url']}]\n";

echo "\n=== 4. Testing Core CRM Endpoints ===\n";
$endpoints = [
    '/crm' => 'Dashboard',
    '/crm/leads' => 'Leads / Enquiries',
    '/crm/customers' => 'Customers',
    '/crm/quotations' => 'Quotations',
    '/crm/orders' => 'Orders',
    '/crm/payments' => 'Payments',
    '/crm/deliveries' => 'Deliveries',
    '/crm/installations' => 'Installations',
    '/crm/projects' => 'Projects',
    '/crm/service' => 'After Sales Tickets',
    '/crm/measurements' => 'Site Measurements',
    '/crm/masters' => 'Master Data Management',
    '/crm/products' => 'Products Catalogue',
    '/crm/reports' => 'Reports Engine',
    '/crm/tour/get' => 'Tour JSON API',
    '/crm/quotations/1/pdf/1' => 'Self-Healing Quotation PDF (V1)',
];

$passCount = 0;
$failCount = 0;

foreach ($endpoints as $uri => $label) {
    $res = req($baseUrl . $uri, 'GET', [], $cookieFile);
    $status = ($res['code'] === 200) ? 'PASS' : 'FAIL';
    if ($res['code'] === 200) {
        $passCount++;
    } else {
        $failCount++;
    }
    echo sprintf("[%s] %-25s HTTP %d (URL: %s) - %s\n", $status, $uri, $res['code'], $res['url'], $label);
    if ($res['code'] !== 200) {
        echo "   Error: " . substr(strip_tags($res['body']), 0, 150) . "...\n";
    }
}

echo "\n=== Summary: {$passCount} PASSED, {$failCount} FAILED ===\n";
