<?php

/**
 * Websites API endpoint
 * Returns list of servers and websites
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Session-Hash, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/HostingerClient.php';

try {
    $client = new HostingerClient();
    $data = $client->getWebsites();

    if ($data && isset($data['data'])) {
        echo json_encode([
            'success' => true,
            'data' => $data['data']['resources'] ?? []
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch websites'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
