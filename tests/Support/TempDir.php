<?php

declare(strict_types=1);

namespace HPanel\Tests\Support;

/** Pasta temporária de teste já criada, como o deploy entrega storage/sessions e storage/ratelimit. */
final class TempDir
{
    public static function make(string $prefix, string ...$subdirs): string
    {
        $dir = sys_get_temp_dir() . '/' . $prefix . '-' . bin2hex(random_bytes(4));
        mkdir($dir, 0700);
        foreach ($subdirs as $sub) {
            mkdir($dir . '/' . $sub, 0700);
        }
        return $dir;
    }
}
