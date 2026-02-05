<?php

/**
 * Sites Cache API
 * Caches all sites from all servers for global search
 * Cache validity: 1 hour
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/HostingerClient.php';

define('SITES_CACHE_DIR', __DIR__ . '/../cookies/');
define('SITES_CACHE_VALIDITY_SECONDS', 3600); // 1 hour

header('Content-Type: application/json');

/**
 * Get cache file path for sites
 * @param string $sessionHash Session hash for unique cache per user
 * @return string Full path to cache file
 */
function getSitesCacheFile(string $sessionHash): string
{
    return SITES_CACHE_DIR . 'sites_cache_' . substr($sessionHash, 0, 16) . '.json';
}

/**
 * Check if cache file is valid (exists and is less than 1 hour old)
 * @param string $cacheFile Path to cache file
 * @return bool True if cache is valid
 */
function isSitesCacheValid(string $cacheFile): bool
{
    if (!file_exists($cacheFile)) {
        return false;
    }

    $mtime = filemtime($cacheFile);
    if ($mtime === false) {
        return false;
    }

    return (time() - $mtime) < SITES_CACHE_VALIDITY_SECONDS;
}

/**
 * Get cached sites data
 * @param string $sessionHash Session hash
 * @return array|null Cached data or null if not available/expired
 */
function getCachedSites(string $sessionHash): ?array
{
    $cacheFile = getSitesCacheFile($sessionHash);

    if (!isSitesCacheValid($cacheFile)) {
        return null;
    }

    $content = file_get_contents($cacheFile);
    if ($content === false) {
        return null;
    }

    $data = json_decode($content, true);
    if (!$data || !isset($data['sites'])) {
        return null;
    }

    // Add cache metadata
    $data['fromCache'] = true;
    $data['cacheAge'] = time() - filemtime($cacheFile);
    $data['cacheAgeMinutes'] = round($data['cacheAge'] / 60);

    return $data;
}

/**
 * Save sites data to cache
 * @param string $sessionHash Session hash
 * @param array $sites Sites data
 * @return bool Success status
 */
function saveCachedSites(string $sessionHash, array $sites): bool
{
    $cacheFile = getSitesCacheFile($sessionHash);

    $data = [
        'timestamp' => date('c'),
        'sites' => $sites
    ];

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    return file_put_contents($cacheFile, $json) !== false;
}

/**
 * Fetch all sites from all servers
 * @param HostingerClient $client
 * @return array Sites with server info
 */
function fetchAllSites(HostingerClient $client): array
{
    $allSites = [];
    $page = 1;
    $hasMore = true;

    while ($hasMore) {
        $response = $client->getWebsites($page);
        
        if (!isset($response['data']['resources'])) {
            break;
        }

        $resources = $response['data']['resources'];
        
        if (empty($resources)) {
            break;
        }

        foreach ($resources as $resource) {
            $orderId = $resource['orderId'] ?? null;
            $serverTitle = $resource['title'] ?? 'Servidor';
            $planName = $resource['planDisplayableName'] ?? $resource['planName'] ?? '';
            $websites = $resource['websites'] ?? [];

            foreach ($websites as $website) {
                $allSites[] = [
                    'domain' => $website['domain'] ?? '',
                    'username' => $website['username'] ?? '',
                    'vhostType' => $website['vhostType'] ?? '',
                    'type' => $website['type'] ?? '',
                    'orderId' => $orderId,
                    'serverTitle' => $serverTitle,
                    'planName' => $planName
                ];
            }
        }

        // Check if there are more pages
        $hasMore = count($resources) >= 25; // Assuming 25 per page
        $page++;

        // Safety limit
        if ($page > 50) {
            break;
        }
    }

    return $allSites;
}

// Main execution
try {
    // Get config from headers
    $config = getConfigFromHeaders();
    
    if (!$config || empty($config['token'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }

    $sessionHash = getSessionHash();
    $forceUpdate = isset($_GET['update']) && $_GET['update'] === '1';

    // Try to get from cache first (unless force update)
    if (!$forceUpdate) {
        $cached = getCachedSites($sessionHash);
        if ($cached) {
            echo json_encode([
                'success' => true,
                'sites' => $cached['sites'],
                'fromCache' => true,
                'cacheAge' => $cached['cacheAgeMinutes'] . ' min',
                'total' => count($cached['sites'])
            ]);
            exit;
        }
    }

    // Fetch fresh data
    $client = new HostingerClient($config['token'], $config['gaid'] ?? '');
    $sites = fetchAllSites($client);

    // Save to cache
    saveCachedSites($sessionHash, $sites);

    echo json_encode([
        'success' => true,
        'sites' => $sites,
        'fromCache' => false,
        'total' => count($sites)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
