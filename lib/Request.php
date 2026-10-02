<?php

declare(strict_types=1);

namespace HPanel;

final class Request
{
    private const MAX_BODY = 65536;

    public function __construct(public readonly string $method, private array $query, private array $body)
    {
    }

    public static function fromGlobals(): self
    {
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $body = [];
        if ($method === 'POST') {
            $decoded = json_decode((string) file_get_contents('php://input', false, null, 0, self::MAX_BODY), true);
            $body = is_array($decoded) ? $decoded : [];
        }
        return new self($method, $_GET, $body);
    }

    /** GET lê da query string; POST lê do corpo JSON. */
    public function get(string $key): mixed
    {
        return $this->method === 'GET' ? ($this->query[$key] ?? null) : ($this->body[$key] ?? null);
    }

    public function flag(string $key): bool
    {
        return ($this->query[$key] ?? null) === '1' || ($this->body[$key] ?? null) === true;
    }
}
