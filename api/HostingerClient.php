<?php

/**
 * Hostinger API Client
 * Handles communication with Hostinger's internal APIs
 */

require_once __DIR__ . '/config.php';

class HostingerClient
{
    private string $baseUrl = 'https://hpanel.hostinger.com';
    private string $cookies;
    private string $gaid;

    public function __construct()
    {
        $config = getConfig();
        $this->cookies = $config['cookies'] ?? '';
        $this->gaid = $config['gaid'] ?? '';
    }

    /**
     * Make an HTTP request to the Hostinger API
     * @param string $endpoint API endpoint
     * @param array $headers Additional headers
     * @return array|null Response data or null on error
     */
    private function request(string $endpoint, array $headers = []): ?array
    {
        $url = $this->baseUrl . $endpoint;

        $defaultHeaders = [
            'accept: application/json;charset=utf-8',
            'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'referer: https://hpanel.hostinger.com/',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_COOKIE => $this->cookies,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Hostinger API Error: $error");
            return null;
        }

        if ($httpCode !== 200) {
            error_log("Hostinger API HTTP Error: $httpCode");
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get all websites grouped by servers
     * @param int $page Page number
     * @return array|null Websites data or null on error
     */
    public function getWebsites(int $page = 1): ?array
    {
        $endpoint = "/api/wh-api/api/hapi/v1/orders/websites?page={$page}&ownership=owned&gaid={$this->gaid}";
        return $this->request($endpoint);
    }

    /**
     * Get account details for a specific server
     * @param int $orderId Order ID
     * @param string $username Username
     * @param string $domain Primary domain
     * @return array|null Account data or null on error
     */
    public function getAccountDetails(int $orderId, string $username, string $domain): ?array
    {
        $endpoint = "/api/rest-hosting/v3/account?gaid={$this->gaid}";
        $headers = [
            "x-hpanel-order-id: {$orderId}",
            "x-hpanel-username: {$username}",
            "x-hpanel-domain: {$domain}",
        ];
        return $this->request($endpoint, $headers);
    }

    /**
     * Test the connection using current cookies
     * @return bool True if connection is successful
     */
    public function testConnection(): bool
    {
        $result = $this->getWebsites();
        return $result !== null && isset($result['data']);
    }

    /**
     * Get file browser link for a specific domain
     * @param string $username Account username
     * @param string $domain Domain name
     * @param int $orderId Order ID
     * @return string|null File browser URL or null on error
     */
    public function getFileBrowserLink(string $username, string $domain, int $orderId): ?string
    {
        $endpoint = "/api/wh-api/api/hapi/v1/accounts/{$username}/file-browser-link?vhost={$domain}&locale=pt_BR&gaid={$this->gaid}";
        $headers = [
            "x-hpanel-order-id: {$orderId}",
            "x-hpanel-username: {$username}",
            "x-hpanel-domain: {$domain}",
        ];
        $result = $this->request($endpoint, $headers);
        return $result['data']['link'] ?? null;
    }

    /**
     * Get databases for a specific domain
     * @param string $username Account username
     * @param string $domain Domain name
     * @param int $orderId Order ID
     * @return array|null Databases list or null on error
     */
    public function getDatabases(string $username, string $domain, int $orderId): ?array
    {
        $endpoint = "/api/wh-api/api/hapi/v1/accounts/{$username}/databases?page=1&perPage=100&onlyAssigned=0&vhost={$domain}&gaid={$this->gaid}";
        $headers = [
            "x-hpanel-order-id: {$orderId}",
            "x-hpanel-username: {$username}",
            "x-hpanel-domain: {$domain}",
        ];
        $result = $this->request($endpoint, $headers);

        // Response can have different structures - try common paths
        if ($result === null) {
            return null;
        }

        // Response structure: data.resources
        return $result['data']['resources'] ?? [];
    }

    /**
     * Get phpMyAdmin link for a specific database
     * @param string $username Account username
     * @param string $dbName Database name
     * @param string $domain Domain name
     * @param int $orderId Order ID
     * @return string|null phpMyAdmin URL or null on error
     */
    public function getPhpMyAdminLink(string $username, string $dbName, string $domain, int $orderId): ?string
    {
        $endpoint = "/api/wh-api/api/hapi/v1/accounts/{$username}/databases/{$dbName}/phpmyadmin-link?gaid={$this->gaid}";
        $headers = [
            "x-hpanel-order-id: {$orderId}",
            "x-hpanel-username: {$username}",
            "x-hpanel-domain: {$domain}",
        ];
        $result = $this->request($endpoint, $headers);
        return $result['data']['link'] ?? null;
    }

    /**
     * Make a PATCH request to the Hostinger API
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @param array $headers Additional headers
     * @return array|null Response data or null on error
     */
    private function requestPatch(string $endpoint, array $data, array $headers = []): ?array
    {
        $url = $this->baseUrl . $endpoint;

        $defaultHeaders = [
            'accept: application/json;charset=utf-8',
            'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'content-type: application/json',
            'referer: https://hpanel.hostinger.com/',
            'origin: https://hpanel.hostinger.com',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_COOKIE => $this->cookies,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Hostinger API Error: $error");
            return null;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Hostinger API HTTP Error: $httpCode");
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get PHP version info for a domain
     * @param string $username Account username
     * @param string $domain Domain name
     * @param int $orderId Order ID
     * @return array|null PHP version data or null on error
     */
    public function getPhpVersion(string $username, string $domain, int $orderId): ?array
    {
        $endpoint = "/api/wh-api/api/hapi/v1/accounts/{$username}/vhosts/{$domain}/php/version?gaid={$this->gaid}";
        $headers = [
            "x-hpanel-order-id: {$orderId}",
            "x-hpanel-username: {$username}",
            "x-hpanel-domain: {$domain}",
        ];
        $result = $this->request($endpoint, $headers);
        return $result['data'] ?? null;
    }

    /**
     * Set PHP version for a domain
     * @param string $username Account username
     * @param string $domain Domain name
     * @param int $orderId Order ID
     * @param string $version PHP version to set (e.g., "8.2")
     * @return bool Success status
     */
    public function setPhpVersion(string $username, string $domain, int $orderId, string $version): bool
    {
        $endpoint = "/api/wh-api/api/hapi/v1/accounts/{$username}/vhosts/{$domain}/php/version?gaid={$this->gaid}";
        $headers = [
            "x-hpanel-order-id: {$orderId}",
            "x-hpanel-username: {$username}",
            "x-hpanel-domain: {$domain}",
        ];
        $result = $this->requestPatch($endpoint, ['phpVersion' => $version], $headers);
        return $result !== null;
    }
}
