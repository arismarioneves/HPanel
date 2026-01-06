<?php

/**
 * Set PHP version for a domain
 * Expects POST request with phpVersion parameter
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Session-Hash, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/HostingerClient.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'success' => false]);
    exit;
}

// Get JSON body
$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$domain = $input['domain'] ?? '';
$orderId = (int)($input['orderId'] ?? 0);
$phpVersion = $input['phpVersion'] ?? '';

if (!$username || !$domain || !$orderId || !$phpVersion) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters', 'success' => false]);
    exit;
}

$client = new HostingerClient();
$success = $client->setPhpVersion($username, $domain, $orderId, $phpVersion);

if ($success) {
    echo json_encode(['success' => true, 'message' => "PHP version changed to $phpVersion"]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to change PHP version', 'success' => false]);
}
