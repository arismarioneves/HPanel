<?php

/**
 * Git SSH key management for a Hostinger account
 * GET: view current public key | POST: create key pair | DELETE: remove key pair
 * Creating a key makes hPanel switch the Git deploy UI back to SSH mode
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Session-Hash, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/HostingerClient.php';

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'POST', 'DELETE'], true)) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'success' => false]);
    exit;
}

// GET reads from query string; POST/DELETE from JSON body
if ($method === 'GET') {
    $username = $_GET['username'] ?? '';
    $domain = $_GET['domain'] ?? '';
    $orderId = (int)($_GET['orderId'] ?? 0);
} else {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $username = $input['username'] ?? '';
    $domain = $input['domain'] ?? '';
    $orderId = (int)($input['orderId'] ?? 0);
}

if (!$username || !$domain || !$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters', 'success' => false]);
    exit;
}

$client = new HostingerClient();

switch ($method) {
    case 'POST':
        $result = $client->createGitKey($username, $domain, $orderId);
        break;
    case 'DELETE':
        $result = $client->deleteGitKey($username, $domain, $orderId);
        break;
    default:
        $result = $client->getGitKey($username, $domain, $orderId);
        break;
}

$httpCode = $result['httpCode'];
$body = $result['body'];

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode([
        'success' => true,
        'publicKey' => $body['data']['publicKey'] ?? null,
    ]);
} else {
    // Relay Hostinger's error message (e.g. "Key pair already exists", errorCode 9999)
    http_response_code($httpCode >= 400 && $httpCode < 600 ? $httpCode : 502);
    echo json_encode([
        'success' => false,
        'error' => $body['message'] ?? 'Falha na comunicação com a Hostinger. Verifique o token JWT.',
        'errorCode' => $body['errorCode'] ?? null,
    ]);
}
