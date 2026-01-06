<?php

/**
 * Configuration Management
 * Handles reading and writing of session files in cookies/ folder
 * Each user has a unique session file identified by a hash stored in localStorage
 */

define('COOKIES_DIR', __DIR__ . '/../cookies/');

/**
 * Get the session hash from HTTP headers or cookie
 * Headers used for AJAX, cookie used for direct page navigation
 * @return string|null Session hash or null if not provided
 */
function getSessionHash(): ?string
{
    // First check header (AJAX requests)
    if (!empty($_SERVER['HTTP_X_SESSION_HASH'])) {
        return $_SERVER['HTTP_X_SESSION_HASH'];
    }

    // Fallback to cookie (direct page navigation)
    return $_COOKIE['hostinger_session'] ?? null;
}

/**
 * Get the session file path for a given hash
 * @param string $hash Session hash
 * @return string Full path to session file
 */
function getSessionFilePath(string $hash): string
{
    // Sanitize hash to prevent directory traversal
    $safeHash = preg_replace('/[^a-zA-Z0-9]/', '', $hash);
    return COOKIES_DIR . $safeHash . '.json';
}

/**
 * Get configuration from session file
 * @param string|null $hash Session hash (reads from header if null)
 * @return array Configuration data
 */
function getConfigFromSession(?string $hash = null): array
{
    $hash = $hash ?? getSessionHash();

    if (!$hash) {
        return ['cookies' => '', 'gaid' => ''];
    }

    $filePath = getSessionFilePath($hash);

    if (!file_exists($filePath)) {
        return ['cookies' => '', 'gaid' => ''];
    }

    $content = file_get_contents($filePath);
    return json_decode($content, true) ?? ['cookies' => '', 'gaid' => ''];
}

/**
 * Save configuration to session file
 * @param array $data Configuration data (cookies, gaid)
 * @param string|null $hash Session hash (reads from header if null)
 * @return array Result with success status and hash
 */
function saveConfigToSession(array $data, ?string $hash = null): array
{
    // Generate new hash if not provided
    if (!$hash) {
        $hash = bin2hex(random_bytes(16)); // 32 character hex string
    }

    $filePath = getSessionFilePath($hash);

    $data['lastUpdated'] = date('Y-m-d H:i:s');
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $success = file_put_contents($filePath, $json) !== false;

    return [
        'success' => $success,
        'hash' => $hash
    ];
}

/**
 * Check if session is configured
 * @param string|null $hash Session hash
 * @return bool True if cookies exist
 */
function isSessionConfigured(?string $hash = null): bool
{
    $config = getConfigFromSession($hash);
    return !empty($config['cookies']);
}

/**
 * Delete session file
 * @param string $hash Session hash
 * @return bool Success status
 */
function deleteSession(string $hash): bool
{
    $filePath = getSessionFilePath($hash);

    if (file_exists($filePath)) {
        return unlink($filePath);
    }

    return true;
}

/**
 * Legacy function for HostingerClient compatibility
 * Reads config from session using header hash
 */
function getConfigFromHeaders(): array
{
    return getConfigFromSession();
}

/**
 * Extract cookies from a cURL command or raw cookie string
 * @param string $input cURL command or cookie string
 * @return string Extracted cookies
 */
function extractCookies(string $input): string
{
    // If it's a cURL command, extract the -b or --cookie value
    if (preg_match("/-b\s+'([^']+)'/", $input, $matches)) {
        return $matches[1];
    }
    if (preg_match('/-b\s+"([^"]+)"/', $input, $matches)) {
        return $matches[1];
    }

    // If it looks like raw cookies (contains key=value pairs with semicolons)
    if (strpos($input, '=') !== false && strpos($input, ';') !== false) {
        return trim($input);
    }

    return $input;
}

/**
 * Check if cookies already contain a JWT token
 * @param string $cookies Cookie string
 * @return bool True if JWT is present
 */
function hasJwtToken(string $cookies): bool
{
    return strpos($cookies, 'jwt=') !== false;
}
