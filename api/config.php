<?php

/**
 * Configuration Management
 * Handles reading and writing of config.json
 */

define('CONFIG_FILE', __DIR__ . '/../config.json');

/**
 * Get the current configuration
 * @return array Configuration data
 */
function getConfig(): array
{
    $content = file_get_contents(CONFIG_FILE);
    return json_decode($content, true) ?? [];
}

/**
 * Save configuration data
 * @param array $data Configuration data to save
 * @return bool Success status
 */
function saveConfig(array $data): bool
{
    $data['lastUpdated'] = date('Y-m-d H:i:s');
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(CONFIG_FILE, $json) !== false;
}

/**
 * Check if cookies are configured
 * @return bool True if cookies exist and are not empty
 */
function isConfigured(): bool
{
    $config = getConfig();
    return !empty($config['cookies']);
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

/**
 * Generate a new JWT token using the token generation endpoint
 * This works even with cookies copied via bookmarklet (without HttpOnly jwt)
 * @param string $cookies Cookie string
 * @param string $gaid Google Analytics ID
 * @return array|null Token data with 'token' and 'expires_at', or null on failure
 */
function generateJwtToken(string $cookies, string $gaid): ?array
{
    $url = "https://hpanel.hostinger.com/api/communication/api/external/v1/auth/token/generate?gaid=" . urlencode($gaid);

    $headers = [
        'accept: application/json;charset=utf-8',
        'content-type: application/json',
        'origin: https://hpanel.hostinger.com',
        'referer: https://hpanel.hostinger.com/',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'null',
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_COOKIE => $cookies,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 200) {
        error_log("Token generation failed: HTTP $httpCode - $error");
        return null;
    }

    $data = json_decode($response, true);

    if (isset($data['success']) && $data['success'] && isset($data['data']['token'])) {
        return [
            'token' => $data['data']['token'],
            'expires_at' => $data['data']['expires_at'] ?? null
        ];
    }

    return null;
}

/**
 * Add JWT token to cookies string if not present
 * @param string $cookies Cookie string
 * @param string $token JWT token
 * @return string Updated cookies with JWT
 */
function addJwtToCookies(string $cookies, string $token): string
{
    // Remove existing jwt if present
    $cookies = preg_replace('/\bjwt=[^;]+;?\s*/', '', $cookies);

    // Add the new token
    $cookies = trim($cookies);
    if (!empty($cookies) && substr($cookies, -1) !== ';') {
        $cookies .= '; ';
    }
    $cookies .= 'jwt=' . $token;

    return $cookies;
}
