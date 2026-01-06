<?php

/**
 * Get file browser link for a domain
 * Returns JSON with the direct URL to file browser
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Session-Hash, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/HostingerClient.php';

// Get parameters
$username = $_GET['username'] ?? '';
$domain = $_GET['domain'] ?? '';
$orderId = (int)($_GET['orderId'] ?? 0);

if (!$username || !$domain || !$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters', 'success' => false]);
    exit;
}

$client = new HostingerClient();
$link = $client->getFileBrowserLink($username, $domain, $orderId);

if ($link) {
    echo json_encode(['success' => true, 'link' => $link]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get file browser link', 'success' => false]);
}
