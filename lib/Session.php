<?php

declare(strict_types=1);

namespace HPanel;

final class Session
{
    public const COOKIE = 'hp_sid';

    private ?array $data = null;
    private bool $loaded = false;

    /**
     * @param \Closure(string, string, array): void $emitCookie
     * @param \Closure(): int $clock
     */
    public function __construct(
        private SessionStore $store,
        private ?string $sid,
        private string $path,
        private bool $secure,
        private \Closure $emitCookie,
        private \Closure $clock,
    ) {
    }

    public static function fromGlobals(SessionStore $store, Config $config): self
    {
        $sid = $_COOKIE[self::COOKIE] ?? null;
        $sid = is_string($sid) && preg_match('/^[A-Za-z0-9_-]{43}$/', $sid) === 1 ? $sid : null;
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';

        return new self(
            $store,
            $sid,
            $config->base,
            $https,
            static function (string $name, string $value, array $options): void {
                setcookie($name, $value, $options);
            },
            static fn(): int => time(),
        );
    }

    public function data(): ?array
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $this->data = $this->sid !== null ? $this->store->load($this->sid) : null;
        }
        return $this->data;
    }

    public function reload(): ?array
    {
        $this->loaded = false;
        return $this->data();
    }

    public function isConnected(): bool
    {
        return $this->data() !== null;
    }

    /** Nova sessão com sid novo (anti fixation); apaga a anterior. */
    public function start(array $data): void
    {
        if ($this->sid !== null) {
            $this->withLock(fn() => $this->store->delete((string) $this->sid));
        }
        $this->sid = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->write($data);
    }

    public function write(array $data): void
    {
        if ($this->sid === null) {
            throw new \LogicException('Sessão inexistente.');
        }
        $now = ($this->clock)();
        $this->store->save($this->sid, $data, $now);
        $this->data = $data;
        $this->loaded = true;
        $this->cookie($this->sid, $now + SessionStore::IDLE_TTL);
    }

    public function destroy(): void
    {
        if ($this->sid !== null) {
            // Sob o lock: uma renovação em andamento não recria o arquivo depois do logout.
            $this->withLock(fn() => $this->store->delete((string) $this->sid));
        }
        $this->sid = null;
        $this->data = null;
        $this->loaded = true;
        $this->cookie('', 1);
    }

    public function withLock(callable $fn): mixed
    {
        return $this->sid === null ? $fn() : $this->store->withLock($this->sid, $fn);
    }

    /**
     * Cache por sessão (cifrado junto com o token). O produtor roda fora do lock;
     * a gravação relê a sessão dentro do lock para não desfazer uma renovação
     * de token feita por outra requisição.
     *
     * @return array{value: mixed, cachedAt: int}
     */
    public function cached(string $key, int $ttl, callable $produce, bool $refresh = false): array
    {
        $data = $this->data() ?? throw ApiError::notConnected();
        $now = ($this->clock)();
        $hit = $data['cache'][$key] ?? null;
        if (!$refresh && is_array($hit) && $now - (int) $hit['at'] < $ttl) {
            return ['value' => $hit['value'], 'cachedAt' => (int) $hit['at']];
        }

        $value = $produce();

        $this->withLock(function () use ($key, $value, $now): void {
            $fresh = $this->reload() ?? throw ApiError::notConnected();
            $fresh['cache'][$key] = ['at' => $now, 'value' => $value];
            $this->write($fresh);
        });

        return ['value' => $value, 'cachedAt' => $now];
    }

    private function cookie(string $value, int $expires): void
    {
        ($this->emitCookie)(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => $this->path,
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
}
