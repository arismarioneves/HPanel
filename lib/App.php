<?php

declare(strict_types=1);

namespace HPanel;

final class App
{
    private static ?Context $context = null;

    public static function root(): string
    {
        return dirname(__DIR__);
    }

    public static function context(): Context
    {
        if (self::$context !== null) {
            return self::$context;
        }
        $env = getenv('HPANEL_CONFIG');
        $config = Config::load(self::root(), is_string($env) && $env !== '' ? $env : null);
        if (is_dir(self::root() . '/cookies')) {
            // Legado (v1): sessões em texto puro e caches; apaga uma vez.
            foreach (glob(self::root() . '/cookies/*.json') ?: [] as $legacy) {
                @unlink($legacy);
            }
        }
        $store = new SessionStore($config->storageDir . '/sessions', $config->appSecret);
        $rateLimit = new RateLimit($config->storageDir . '/ratelimit', $config->appSecret);
        if (random_int(1, 100) === 1) {
            $store->gc(time());
            $rateLimit->gc(time());
        }
        return self::$context = new Context(
            $config,
            Session::fromGlobals($store, $config),
            new CurlTransport(),
            $rateLimit,
            static fn(): int => time(),
        );
    }
}
