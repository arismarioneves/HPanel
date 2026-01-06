<?php

/**
 * Get databases list for a domain
 * Returns JSON with the list of databases
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
$databases = $client->getDatabases($username, $domain, $orderId);

if ($databases !== null) {
    echo json_encode(['success' => true, 'databases' => $databases]);
} else {
    // Return empty array instead of error - domain may have no databases
    echo json_encode(['success' => true, 'databases' => []]);
}
