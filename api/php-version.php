<?php

/**
 * Get PHP version info for a domain
 * Returns JSON with current version and available versions
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
$phpInfo = $client->getPhpVersion($username, $domain, $orderId);

if ($phpInfo !== null) {
    // Combine older and newer versions into a single list
    $allVersions = [];
    if (isset($phpInfo['olderVersions'])) {
        foreach ($phpInfo['olderVersions'] as $version => $label) {
            $allVersions[$version] = $label;
        }
    }
    // Add current version
    if (isset($phpInfo['version'])) {
        $allVersions[$phpInfo['version']] = 'PHP ' . $phpInfo['version'] . ' (atual)';
    }
    if (isset($phpInfo['newerVersions'])) {
        foreach ($phpInfo['newerVersions'] as $version => $label) {
            $allVersions[$version] = $label;
        }
    }

    // Sort versions
    uksort($allVersions, 'version_compare');

    echo json_encode([
        'success' => true,
        'current' => $phpInfo['version'] ?? null,
        'currentFull' => $phpInfo['versionFull'] ?? null,
        'versions' => $allVersions
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get PHP version info', 'success' => false]);
}
