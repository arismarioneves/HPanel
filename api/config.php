<?php

/**
 * Configuration Management
 * Handles reading and writing of session files in cookies/ folder
 * Each user has a unique session file identified by a hash stored in localStorage
 * 
 * New simplified format:
 * {
 *     "token": "JWT token",
 *     "gaid": "GA1.1.xxx",
 *     "update": "2026-01-07 15:00:00"
 * }
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
 * @return array Configuration data with token, gaid
 */
function getConfigFromSession(?string $hash = null): array
{
    $hash = $hash ?? getSessionHash();

    if (!$hash) {
        return ['token' => '', 'gaid' => ''];
    }

    $filePath = getSessionFilePath($hash);

    if (!file_exists($filePath)) {
        return ['token' => '', 'gaid' => ''];
    }

    $content = file_get_contents($filePath);
    $data = json_decode($content, true) ?? [];

    // Support old format (cookies) and new format (token)
    if (isset($data['cookies']) && !isset($data['token'])) {
        // Extract JWT from cookies for backwards compatibility
        if (preg_match('/jwt=([^;]+)/', $data['cookies'], $matches)) {
            $data['token'] = $matches[1];
        }
    }

    return [
        'token' => $data['token'] ?? '',
        'gaid' => $data['gaid'] ?? '',
        'update' => $data['update'] ?? ''
    ];
}

/**
 * Save configuration to session file
 * @param array $data Configuration data (token, gaid)
 * @param string|null $hash Session hash (generates new if null)
 * @return array Result with success status and hash
 */
function saveConfigToSession(array $data, ?string $hash = null): array
{
    // Generate new hash if not provided
    if (!$hash) {
        $hash = bin2hex(random_bytes(16)); // 32 character hex string
    }

    $filePath = getSessionFilePath($hash);

    $saveData = [
        'token' => $data['token'] ?? '',
        'gaid' => $data['gaid'] ?? '',
        'update' => date('Y-m-d H:i:s')
    ];

    $json = json_encode($saveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $success = file_put_contents($filePath, $json) !== false;

    return [
        'success' => $success,
        'hash' => $hash
    ];
}

/**
 * Get configuration from HTTP headers (for API requests)
 * Uses session hash to load from file
 * @return array Configuration data
 */
function getConfigFromHeaders(): array
{
    return getConfigFromSession();
}

/**
 * Build cookies string from token for API requests
 * @param string $token JWT token
 * @return string Cookie string for cURL
 */
function buildCookiesFromToken(string $token): string
{
    return 'jwt=' . $token . '; language=pt_BR';
}

/**
 * Check if the session has a valid JWT token
 * @param string|null $token Token to check (or read from session)
 * @return bool True if token exists
 */
function hasJwtToken(?string $token = null): bool
{
    if ($token === null) {
        $config = getConfigFromSession();
        $token = $config['token'] ?? '';
    }

    return !empty($token) && strlen($token) > 50;
}
