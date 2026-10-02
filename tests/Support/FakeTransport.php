<?php

declare(strict_types=1);

namespace HPanel\Tests\Support;

use HPanel\HttpTransport;

final class FakeTransport implements HttpTransport
{
    /** @var list<array{method:string, url:string, headers:array, cookie:string, body:?string}> */
    public array $calls = [];

    /** @param list<array{status:int, body?:?string, cookies?:array}> $responses */
    public function __construct(private array $responses)
    {
    }

    public function send(string $method, string $url, array $headers, string $cookie, ?string $body): array
    {
        $this->calls[] = compact('method', 'url', 'headers', 'cookie', 'body');
        $next = array_shift($this->responses) ?? throw new \LogicException("Resposta não programada para {$method} {$url}");
        return $next + ['body' => null, 'cookies' => []];
    }
}
