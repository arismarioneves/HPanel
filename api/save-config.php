<?php

/**
 * Save Configuration API endpoint
 * Saves JWT token to a session file and returns the hash
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

$token = $input['token'] ?? '';
$gaid = $input['gaid'] ?? 'GA1.1.000000000.0000000000';
$existingHash = $input['hash'] ?? null;

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token JWT é obrigatório']);
    exit;
}

// Validate token looks like a JWT
if (!preg_match('/^eyJ[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token JWT inválido']);
    exit;
}

// Save to session file
$result = saveConfigToSession([
    'token' => $token,
    'gaid' => $gaid
], $existingHash);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'hash' => $result['hash'],
        'message' => 'Token salvo com sucesso!'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Falha ao salvar token']);
}
