<?php

/**
 * Server Usage API endpoint
 * Returns usage data for a specific server
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Session-Hash, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/HostingerClient.php';

$orderId = isset($_GET['orderId']) ? (int)$_GET['orderId'] : 0;
$username = $_GET['username'] ?? '';
$domain = $_GET['domain'] ?? '';

if (!$orderId || !$username || !$domain) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    $client = new HostingerClient();
    $data = $client->getAccountDetails($orderId, $username, $domain);

    if ($data && isset($data['data'])) {
        $usage = $data['data']['usage'] ?? null;
        echo json_encode([
            'success' => true,
            'orderId' => $orderId,
            'usage' => $usage
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
