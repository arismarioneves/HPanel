<?php

declare(strict_types=1);

namespace HPanel;

final class Context
{
    public readonly Catalog $catalog;
    private ?DataSource $source = null;
    private ?string $token = null;
    private ?ApiError $tokenError = null;

    /** @param \Closure(): int $clock */
    public function __construct(
        public readonly Config $config,
        public readonly Session $session,
        public readonly HttpTransport $http,
        public readonly RateLimit $rateLimit,
        private \Closure $clock,
    ) {
        $this->catalog = new Catalog($session, fn(): DataSource => $this->source());
    }

    public function now(): int
    {
        return ($this->clock)();
    }

    public function auth(): HostingerAuth
    {
        return new HostingerAuth($this->http);
    }

    /** Token garantidamente fresco; renova no máximo uma vez por requisição. */
    public function freshToken(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }
        if (!$this->session->isConnected()) {
            throw ApiError::notConnected();
        }
        if ($this->isDemo()) {
            throw DemoSource::blocked();
        }
        if ($this->tokenError !== null) {
            throw $this->tokenError;
        }
        try {
            return $this->token = $this->auth()->ensureFresh($this->session, $this->now());
        } catch (ApiError $e) {
            throw $this->tokenError = $e;
        }
    }

    /** Sessão de demonstração: dados fictícios, sem token nem rede. */
    public function isDemo(): bool
    {
        return ($this->session->data()['demo'] ?? false) === true;
    }

    /** Fonte de dados com token garantidamente fresco (renova se preciso); fictícia em sessão demo. */
    public function source(): DataSource
    {
        if ($this->source !== null) {
            return $this->source;
        }
        if ($this->isDemo()) {
            return $this->source = new DemoSource();
        }
        $token = $this->freshToken();
        $data = $this->session->data() ?? throw ApiError::notConnected();
        return $this->source = new HostingerSource($this->http, $token, (string) ($data['gaid'] ?? ''));
    }
}
