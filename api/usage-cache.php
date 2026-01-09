<?php

/**
 * Usage Cache Management
 * Handles caching of server usage data to reduce API calls
 * Cache validity: 1 hour
 */

define('CACHE_DIR', __DIR__ . '/../cookies/');
define('CACHE_VALIDITY_SECONDS', 3600); // 1 hour

/**
 * Get cache file path for a specific server
 * @param int $orderId Server order ID
 * @return string Full path to cache file
 */
function getUsageCacheFile(int $orderId): string
{
    return CACHE_DIR . 'usage_cache_' . $orderId . '.json';
}

/**
 * Check if cache file is valid (exists and is less than 1 hour old)
 * @param string $cacheFile Path to cache file
 * @return bool True if cache is valid
 */
function isCacheValid(string $cacheFile): bool
{
    if (!file_exists($cacheFile)) {
        return false;
    }

    $mtime = filemtime($cacheFile);
    if ($mtime === false) {
        return false;
    }

    return (time() - $mtime) < CACHE_VALIDITY_SECONDS;
}

/**
 * Get cached usage data for a server
 * @param int $orderId Server order ID
 * @return array|null Cached data or null if not available/expired
 */
function getCachedUsage(int $orderId): ?array
{
    $cacheFile = getUsageCacheFile($orderId);

    if (!isCacheValid($cacheFile)) {
        return null;
    }

    $content = file_get_contents($cacheFile);
    if ($content === false) {
        return null;
    }

    $data = json_decode($content, true);
    if (!$data || !isset($data['usage'])) {
        return null;
    }

    // Add cache metadata
    $data['fromCache'] = true;
    $data['cacheAge'] = time() - filemtime($cacheFile);
    $data['cacheAgeMinutes'] = round($data['cacheAge'] / 60);

    return $data;
}

/**
 * Save usage data to cache
 * @param int $orderId Server order ID
 * @param array $usage Usage data
 * @return bool Success status
 */
function saveCachedUsage(int $orderId, array $usage): bool
{
    $cacheFile = getUsageCacheFile($orderId);

    $data = [
        'orderId' => $orderId,
        'timestamp' => date('c'),
        'usage' => $usage
    ];

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    return file_put_contents($cacheFile, $json) !== false;
}

/**
 * Clear cache for a specific server
 * @param int $orderId Server order ID
 * @return bool Success status
 */
function clearUsageCache(int $orderId): bool
{
    $cacheFile = getUsageCacheFile($orderId);

    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }

    return true;
}

/**
 * Clear all usage cache files
 * @return int Number of files deleted
 */
function clearAllUsageCache(): int
{
    $deleted = 0;
    $files = glob(CACHE_DIR . 'usage_cache_*.json');

    if ($files) {
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
    }

    return $deleted;
}
