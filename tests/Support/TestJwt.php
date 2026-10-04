<?php

declare(strict_types=1);

namespace HPanel\Tests\Support;

final class TestJwt
{
    /** JWT sintaticamente válido (assinatura falsa) com o `exp` informado. */
    public static function make(int $exp): string
    {
        $b64 = static fn(string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        return $b64('{"typ":"JWT","alg":"RS256"}') . '.' . $b64(json_encode(['exp' => $exp, 'sub' => 'x'])) . '.' . $b64('assinatura');
    }
}
