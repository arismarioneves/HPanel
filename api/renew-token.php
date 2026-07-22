<?php

/**
 * Renew the JWT of the current session (one-click renew).
 * Uses hPanel's sliding-session /auth/refresh: the current jwt returns a fresh jwt.
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
require_once __DIR__ . '/HostingerClient.php';

$hash = getSessionHash();
if (!$hash) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Sessão não identificada']);
    exit;
}

$config = getConfigFromSession($hash);
$token = $config['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nenhum token configurado']);
    exit;
}

$client = new HostingerClient($token, $config['gaid'] ?? '');
$newToken = $client->renewToken($token);

if ($newToken === null) {
    // Provável token expirado de vez: precisa recapturar via login
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'expired' => true,
        'error' => 'Não foi possível renovar. O token pode ter expirado — reconfigure o token JWT.'
    ]);
    exit;
}

updateSessionToken($hash, $newToken);

$info = getJwtInfo($newToken);

echo json_encode([
    'success' => true,
    'message' => 'Token renovado',
    'status' => $info ? [
        'minutesLeft' => $info['minutesLeft'],
        'expired' => $info['expired'],
        'warning' => $info['warning'],
        'expiresAt' => $info['expiresAt']
    ] : null
]);
