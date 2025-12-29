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
