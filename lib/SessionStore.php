<?php

declare(strict_types=1);

namespace HPanel;

/**
 * Persistência das sessões: um arquivo JSON por sessão com metadados em claro
 * (touched, exp — usados pelo GC) e o payload cifrado.
 */
final class SessionStore
{
    public const IDLE_TTL = 604800;      // 7 dias sem uso
    public const DEAD_TOKEN_TTL = 86400; // JWT expirado há mais de 24 h

    public function __construct(private string $dir, private string $secret)
    {
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new ConfigException("Não foi possível criar o diretório de sessões ({$dir}).");
        }
    }

    public function load(string $sid): ?array
    {
        $file = $this->path($sid);
        if (!is_file($file)) {
            return null;
        }
        $env = json_decode((string) @file_get_contents($file), true);
        $plain = is_array($env) && is_string($env['data'] ?? null)
            ? Crypto::open($env['data'], Crypto::key($sid, $this->secret))
            : null;
        $data = $plain === null ? null : json_decode($plain, true);
        if (!is_array($data)) {
            @unlink($file);
            return null;
        }
        return $data;
    }

    public function save(string $sid, array $data, int $now): void
    {
        $token = $data['token'] ?? null;
        $env = [
            'v' => 1,
            'touched' => $now,
            'exp' => is_string($token) ? Jwt::expiry($token) : null,
            'data' => Crypto::seal(
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                Crypto::key($sid, $this->secret)
            ),
        ];
        $file = $this->path($sid);
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($tmp, json_encode($env), LOCK_EX) === false || !rename($tmp, $file)) {
            @unlink($tmp);
            throw new ConfigException('Não foi possível gravar a sessão. Verifique a permissão de escrita em storage/.');
        }
    }

    public function delete(string $sid): void
    {
        $file = $this->path($sid);
        @unlink($file);
        @unlink($file . '.lock');
    }

    /** Exclusão mútua por sessão (renovação de token e escrita de cache). */
    public function withLock(string $sid, callable $fn): mixed
    {
        $handle = fopen($this->path($sid) . '.lock', 'c');
        if ($handle === false) {
            throw new ConfigException('Não foi possível travar a sessão. Verifique a permissão de escrita em storage/.');
        }
        try {
            flock($handle, LOCK_EX);
            return $fn();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function gc(int $now): int
    {
        $removed = 0;
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $env = json_decode((string) @file_get_contents($file), true);
            $touched = (int) ($env['touched'] ?? 0);
            $exp = $env['exp'] ?? null;
            $idle = $now - $touched > self::IDLE_TTL;
            $dead = is_int($exp) && $now - $exp > self::DEAD_TOKEN_TTL;
            if ($idle || $dead) {
                @unlink($file);
                @unlink($file . '.lock');
                $removed++;
            }
        }
        foreach (glob($this->dir . '/*.tmp') ?: [] as $tmp) {
            $mtime = @filemtime($tmp);
            if ($mtime !== false && $now - $mtime > 3600 && @unlink($tmp)) {
                $removed++;
            }
        }
        return $removed;
    }

    private function path(string $sid): string
    {
        return $this->dir . '/' . Crypto::fileId($sid, $this->secret) . '.json';
    }
}
