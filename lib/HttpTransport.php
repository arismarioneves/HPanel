<?php

declare(strict_types=1);

namespace HPanel;

interface HttpTransport
{
    /**
     * @param list<string> $headers
     * @return array{status:int, body:?string, cookies:array<string,string>} status 0 = falha de rede
     */
    public function send(string $method, string $url, array $headers, string $cookie, ?string $body): array;
}
