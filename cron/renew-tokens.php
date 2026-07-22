<?php

/**
 * Cron de renovação automática de tokens (acionável via URL).
 *
 * Agende um gatilho para chamar esta URL a cada ~30 min:
 *   https://SEU-HOST/cron/renew-tokens?key=SEU_SEGREDO
 *
 * Renova o jwt de todas as sessões que optaram por renovação automática
 * (autoRenew=true) e cujo token ainda não expirou de vez.
 *
 * Segurança: exige a chave 'cron_secret' definida em /config.php (não versionado).
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/HostingerClient.php';

// --- Autenticação por chave secreta ---
$rootConfig = is_file(__DIR__ . '/../config.php') ? (require __DIR__ . '/../config.php') : [];
$secret = is_array($rootConfig) ? ($rootConfig['cron_secret'] ?? '') : '';

if ($secret === '') {
    http_response_code(403);
    echo "cron_secret não configurado em config.php. Defina 'cron_secret' antes de usar o cron.\n";
    exit;
}

$provided = $_GET['key'] ?? ($_SERVER['HTTP_X_CRON_KEY'] ?? '');
if (!hash_equals($secret, (string) $provided)) {
    http_response_code(403);
    echo "Acesso negado.\n";
    exit;
}

// --- Renovação ---
$dir = __DIR__ . '/../cookies/';
$files = glob($dir . '*.json') ?: [];

$stats = ['total' => 0, 'renovados' => 0, 'ignorados' => 0, 'expirados' => 0, 'falhas' => 0];
$now = date('Y-m-d H:i:s');
echo "[$now] Iniciando renovação automática\n";

foreach ($files as $file) {
    $base = basename($file);

    // Ignora arquivos de cache (não são sessões)
    if (strpos($base, 'usage_cache_') === 0 || strpos($base, 'sites_cache_') === 0) {
        continue;
    }

    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) {
        continue;
    }

    $stats['total']++;

    // Só renova quem optou
    if (empty($data['autoRenew'])) {
        $stats['ignorados']++;
        continue;
    }

    $token = $data['token'] ?? '';
    if ($token === '') {
        $stats['ignorados']++;
        continue;
    }

    // Sempre tenta renovar: o /auth/refresh tolera tokens até já expirados por uma
    // janela. Só tratamos como morto quando o refresh de fato recusa (null).
    $hash = basename($file, '.json');
    $client = new HostingerClient($token, $data['gaid'] ?? '');
    $newToken = $client->renewToken($token);

    if ($newToken === null) {
        // refresh recusou: token expirado de vez — precisa recapturar via login
        $stats['expirados']++;
        echo "  - {$hash}: não renovável (recapturar via login)\n";
        continue;
    }

    if (updateSessionToken($hash, $newToken)) {
        $stats['renovados']++;
        $newInfo = getJwtInfo($newToken);
        $mins = $newInfo ? $newInfo['minutesLeft'] : '?';
        echo "  - {$hash}: renovado (válido por {$mins} min)\n";
    } else {
        $stats['falhas']++;
        echo "  - {$hash}: renovado mas falhou ao gravar\n";
    }
}

echo "Resumo: {$stats['renovados']} renovados, {$stats['ignorados']} ignorados, "
    . "{$stats['expirados']} expirados, {$stats['falhas']} falhas "
    . "(de {$stats['total']} sessões)\n";
