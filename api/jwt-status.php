<?php

/**
 * JWT Status API endpoint
 * Returns JWT expiration info for the current session
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Session-Hash, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/config.php';

$config = getConfigFromSession();
$token = $config['token'] ?? '';

if (empty($token) || !hasJwtToken($token)) {
    echo json_encode(['success' => false, 'error' => 'No JWT found']);
    exit;
}

$info = getJwtInfo($token);

if ($info === null) {
    echo json_encode(['success' => false, 'error' => 'Invalid JWT format']);
    exit;
}

echo json_encode([
    'success' => true,
    'status' => [
        'minutesLeft' => $info['minutesLeft'],
        'expired' => $info['expired'],
        'warning' => $info['warning'],
        'expiresAt' => $info['expiresAt']
    ]
]);
