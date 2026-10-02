<?php

declare(strict_types=1);

namespace HPanel;

final class CurlTransport implements HttpTransport
{
    public function send(string $method, string $url, array $headers, string $cookie, ?string $body): array
    {
        $cookies = [];
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$cookies): int {
                if (preg_match('/^set-cookie:\s*([^=;\s]+)=([^;]*)/i', $line, $m) === 1) {
                    $cookies[$m[1]] = trim($m[2]);
                }
                return strlen($line);
            },
        ];
        if ($method !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
            $options[CURLOPT_POSTFIELDS] = $body ?? '';
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error !== '') {
            error_log('HPanel transporte: ' . $error);
            return ['status' => 0, 'body' => null, 'cookies' => []];
        }
        return ['status' => $status, 'body' => (string) $response, 'cookies' => $cookies];
    }
}
