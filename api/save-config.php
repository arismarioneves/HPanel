<?php

/**
 * Save Configuration API endpoint
 * Saves cookies to a session file and returns the hash
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Session-Hash, Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/config.php';

// Get JSON body
$input = json_decode(file_get_contents('php://input'), true);

$cookies = $input['cookies'] ?? '';
$gaid = $input['gaid'] ?? '';
$existingHash = $input['hash'] ?? null;

if (empty($cookies)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cookies are required']);
    exit;
}

// Extract cookies from cURL if needed
$cookies = extractCookies($cookies);

// Save to session file
$result = saveConfigToSession([
    'cookies' => $cookies,
    'gaid' => $gaid
], $existingHash);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'hash' => $result['hash'],
        'message' => 'Configurações salvas com sucesso!'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save configuration']);
}
