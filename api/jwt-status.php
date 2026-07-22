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

$parts = explode('.', $token);

if (count($parts) !== 3) {
    echo json_encode(['success' => false, 'error' => 'Invalid JWT format']);
    exit;
}

// JWT usa base64url (RFC 7515): converte -_ para +/ antes de decodificar
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

if (!isset($payload['exp'])) {
    echo json_encode(['success' => false, 'error' => 'JWT has no expiration']);
    exit;
}

$now = time();
$exp = $payload['exp'];
$minutesLeft = max(0, floor(($exp - $now) / 60));
$expired = $exp < $now;
$warning = $minutesLeft < 15 && !$expired;

echo json_encode([
    'success' => true,
    'status' => [
        'minutesLeft' => $minutesLeft,
        'expired' => $expired,
        'warning' => $warning,
        'expiresAt' => date('Y-m-d H:i:s', $exp)
    ]
]);
