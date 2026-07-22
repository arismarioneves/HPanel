<?php

/**
 * Server Details API endpoint
 * Returns details for a specific server/order
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Session-Hash, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/HostingerClient.php';

$orderId = isset($_GET['orderId']) ? (int)$_GET['orderId'] : 0;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing orderId']);
    exit;
}

try {
    $client = new HostingerClient();
    $websitesData = $client->getWebsites();

    if (!$websitesData || !isset($websitesData['data'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch data']);
        exit;
    }

    // Find the specific server (page 1 first, then remaining pages)
    $server = null;
    foreach ($websitesData['data']['resources'] ?? [] as $resource) {
        if ($resource['orderId'] == $orderId) {
            $server = $resource;
            break;
        }
    }

    if (!$server) {
        $server = $client->findServerByOrderId($orderId);
    }

    if (!$server) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Server not found']);
        exit;
    }

    // Get main domain and username
    $websites = $server['websites'] ?? [];
    $mainDomain = '';
    $username = '';
    foreach ($websites as $site) {
        if ($site['vhostType'] === 'main') {
            $mainDomain = $site['domain'];
            $username = $site['username'];
            break;
        }
    }
    if (!$mainDomain && !empty($websites)) {
        $mainDomain = $websites[0]['domain'];
        $username = $websites[0]['username'];
    }

    // Fetch account details
    $accountData = null;
    if ($username && $mainDomain) {
        $accountData = $client->getAccountDetails($orderId, $username, $mainDomain);
    }

    echo json_encode([
        'success' => true,
        'server' => $server,
        'account' => $accountData['data'] ?? null,
        'mainDomain' => $mainDomain,
        'username' => $username
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
