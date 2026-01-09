<?php

/**
 * Server Usage API endpoint
 * Returns usage data for a specific server
 * Uses cache to reduce API calls (1 hour validity)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Session-Hash, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/HostingerClient.php';
require_once __DIR__ . '/usage-cache.php';

$orderId = isset($_GET['orderId']) ? (int)$_GET['orderId'] : 0;
$username = $_GET['username'] ?? '';
$domain = $_GET['domain'] ?? '';
$forceRefresh = isset($_GET['update']) && $_GET['update'] === '1';

if (!$orderId || !$username || !$domain) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Check cache first (unless force refresh requested)
if (!$forceRefresh) {
    $cached = getCachedUsage($orderId);
    if ($cached) {
        echo json_encode([
            'success' => true,
            'orderId' => $orderId,
            'usage' => $cached['usage'],
            'fromCache' => true,
            'cacheAge' => $cached['cacheAgeMinutes'] . ' min'
        ]);
        exit;
    }
}

// Fetch from API
try {
    $client = new HostingerClient();
    $data = $client->getAccountDetails($orderId, $username, $domain);

    if ($data && isset($data['data'])) {
        $usage = $data['data']['usage'] ?? null;

        // Save to cache
        if ($usage) {
            saveCachedUsage($orderId, $usage);
        }

        echo json_encode([
            'success' => true,
            'orderId' => $orderId,
            'usage' => $usage,
            'fromCache' => false
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'orderId' => $orderId,
            'usage' => null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
