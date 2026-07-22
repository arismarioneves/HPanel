<?php

/**
 * Enable/disable automatic token renewal (opt-in) for the current session.
 * When enabled, the global cron (cron/renew-tokens.php) will keep this session's jwt fresh.
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

$hash = getSessionHash();
if (!$hash) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Sessão não identificada']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$enabled = (bool) ($input['enabled'] ?? false);

// Só faz sentido ligar auto-renovação se já existe um token configurado
$config = getConfigFromSession($hash);
if ($enabled && empty($config['token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Configure um token antes de ativar a renovação automática']);
    exit;
}

$ok = setAutoRenew($hash, $enabled);

echo json_encode([
    'success' => $ok,
    'autoRenew' => $enabled
]);
