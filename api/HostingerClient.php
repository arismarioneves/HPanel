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

    public function __construct(?string $token = null, ?string $gaid = null)
    {
        // If token/gaid provided, use them; otherwise read from session
        if ($token !== null) {
            $this->cookies = buildCookiesFromToken($token);
            $this->gaid = $gaid ?? '';
        } else {
            $config = getConfigFromHeaders();
            $token = $config['token'] ?? '';
            $this->cookies = buildCookiesFromToken($token);
            $this->gaid = $config['gaid'] ?? '';
        }
    }

    /**
     * Make an HTTP request to the Hostinger API (any method)
     * Returns HTTP code and decoded body so callers can inspect API error messages
     * @param string $method HTTP method (GET, POST, PATCH, DELETE...)
     * @param string $endpoint API endpoint
     * @param array|null $data Request body data (non-GET only)
     * @param array $headers Additional headers
     * @return array ['httpCode' => int, 'body' => array|null] (httpCode 0 = connection error)
     */
    private function httpRequest(string $method, string $endpoint, ?array $data = null, array $headers = []): array
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

        if ($method !== 'GET') {
            $defaultHeaders[] = 'content-type: application/json';
            $defaultHeaders[] = 'origin: https://hpanel.hostinger.com';
        }

        $allHeaders = array_merge($defaultHeaders, $headers);

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_COOKIE => $this->cookies,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
            $options[CURLOPT_POSTFIELDS] = json_encode($data ?? new stdClass());
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Hostinger API Error: $error");
            return ['httpCode' => 0, 'body' => null];
        }

        return ['httpCode' => $httpCode, 'body' => json_decode((string)$response, true)];
    }

    /**
     * Build the standard account-scoped headers used by hPanel APIs
     * @param string $username Account username
     * @param string $domain Domain name
     * @param int $orderId Order ID
     * @return array Headers array
     */
    private function accountHeaders(string $username, string $domain, int $orderId): array
    {
        return [
            "x-hpanel-order-id: {$orderId}",
            "x-hpanel-username: {$username}",
            "x-hpanel-domain: {$domain}",
        ];
    }

    /**
     * Make a GET request to the Hostinger API
     * @param string $endpoint API endpoint
     * @param array $headers Additional headers
     * @return array|null Response data or null on error
     */
    private function request(string $endpoint, array $headers = []): ?array
    {
        $result = $this->httpRequest('GET', $endpoint, null, $headers);

        if ($result['httpCode'] !== 200) {
            if ($result['httpCode'] !== 0) {
                error_log("Hostinger API HTTP Error: {$result['httpCode']}");
            }
            return null;
        }

        return $result['body'];
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
     * Find a server/order across all pages of the websites listing
     * @param int $orderId Order ID to look for
     * @return array|null Server resource or null if not found
     */
    public function findServerByOrderId(int $orderId): ?array
    {
        $page = 1;

        do {
            $response = $this->getWebsites($page);
            $resources = $response['data']['resources'] ?? [];

            foreach ($resources as $resource) {
                if (($resource['orderId'] ?? null) == $orderId) {
                    return $resource;
                }
            }

            $page++;
            // API returns up to 25 resources per page; safety limit of 50 pages
        } while (count($resources) >= 25 && $page <= 50);

        return null;
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
        $result = $this->httpRequest('PATCH', $endpoint, $data, $headers);

        if ($result['httpCode'] < 200 || $result['httpCode'] >= 300) {
            if ($result['httpCode'] !== 0) {
                error_log("Hostinger API HTTP Error: {$result['httpCode']}");
            }
            return null;
        }

        return $result['body'];
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

    /**
     * Get the Git SSH public key for an account
     * @param string $username Account username
     * @param string $domain Domain name
     * @param int $orderId Order ID
     * @return array ['httpCode' => int, 'body' => array|null]
     *               body: {"data":{"publicKey":null|"ssh-rsa ..."}}
     */
    public function getGitKey(string $username, string $domain, int $orderId): array
    {
        $endpoint = "/api/wh-api/api/hapi/v1/accounts/{$username}/git-key?gaid={$this->gaid}";
        return $this->httpRequest('GET', $endpoint, null, $this->accountHeaders($username, $domain, $orderId));
    }

    /**
     * Create the Git SSH key pair for an account
     * Side effect: hPanel switches the Git deploy UI back to SSH mode
     * @param string $username Account username
     * @param string $domain Domain name
     * @param int $orderId Order ID
     * @return array ['httpCode' => int, 'body' => array|null]
     *               body: {"data":{"publicKey":"ssh-rsa ..."}} or {"message":"Key pair already exists","errorCode":9999}
     */
    public function createGitKey(string $username, string $domain, int $orderId): array
    {
        $endpoint = "/api/wh-api/api/hapi/v1/accounts/{$username}/git-key?gaid={$this->gaid}";
        return $this->httpRequest('POST', $endpoint, null, $this->accountHeaders($username, $domain, $orderId));
    }

    /**
     * Delete the Git SSH key pair for an account
     * Note: this hPanel endpoint is undocumented for DELETE; callers must
     * surface the API response if the operation is rejected
     * @param string $username Account username
     * @param string $domain Domain name
     * @param int $orderId Order ID
     * @return array ['httpCode' => int, 'body' => array|null]
     */
    public function deleteGitKey(string $username, string $domain, int $orderId): array
    {
        $endpoint = "/api/wh-api/api/hapi/v1/accounts/{$username}/git-key?gaid={$this->gaid}";
        return $this->httpRequest('DELETE', $endpoint, null, $this->accountHeaders($username, $domain, $orderId));
    }
}
