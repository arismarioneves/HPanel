<?php

declare(strict_types=1);

namespace HPanel;

/** Janela deslizante por (bucket, IP). O IP é guardado só como HMAC. */
final class RateLimit
{
    public function __construct(private string $dir, private string $secret)
    {
        // A pasta vem do deploy (storage/ratelimit versionada); o painel não cria diretórios.
        if (!is_dir($dir) || !is_writable($dir)) {
            error_log("HPanel: diretório ausente ou sem escrita: {$dir}");
            throw new ConfigException('A pasta storage/ratelimit não existe ou não tem permissão de escrita (com storage_dir, crie ratelimit/ dentro dela).');
        }
    }

    public function hit(string $bucket, string $ip, int $max, int $window, int $now): bool
    {
        $handle = fopen($this->dir . '/' . hash_hmac('sha256', $bucket . '|' . $ip, $this->secret) . '.json', 'c+');
        if ($handle === false) {
            throw new ConfigException('Não foi possível gravar em storage/ratelimit.');
        }
        try {
            flock($handle, LOCK_EX);
            $hits = json_decode((string) stream_get_contents($handle), true);
            $hits = array_values(array_filter(
                is_array($hits) ? $hits : [],
                static fn(mixed $t): bool => is_int($t) && $t > $now - $window
            ));
            $allowed = count($hits) < $max;
            if ($allowed) {
                $hits[] = $now;
            }
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($hits));
            return $allowed;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function gc(int $now): int
    {
        $removed = 0;
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            if ($now - (int) @filemtime($file) > 86400 && @unlink($file)) {
                $removed++;
            }
        }
        return $removed;
    }
}
