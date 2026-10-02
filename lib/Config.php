<?php

declare(strict_types=1);

namespace HPanel;

final class Config
{
    public function __construct(
        public readonly string $root,
        public readonly string $base,
        public readonly string $appSecret,
        public readonly string $storageDir,
    ) {
    }

    /** Carrega config.php; na primeira execução gera e grava o app_secret. */
    public static function load(string $root, ?string $file = null): self
    {
        $file ??= $root . '/config.php';
        $raw = is_file($file) ? require $file : [];
        $raw = is_array($raw) ? $raw : [];

        if (!is_string($raw['app_secret'] ?? null) || preg_match('/^[a-f0-9]{64}$/', $raw['app_secret']) !== 1) {
            $raw['app_secret'] = bin2hex(random_bytes(32));
            $php = "<?php\n\n// Gerado pelo HPanel. NÃO versionar: contém a chave das sessões.\nreturn " . var_export($raw, true) . ";\n";
            if (@file_put_contents($file, $php, LOCK_EX) === false) {
                throw new ConfigException(
                    'Não foi possível gravar o config.php. Copie config.exemplo.php para config.php e dê permissão de escrita ao PHP.'
                );
            }
        }

        return self::fromArray($raw, $root);
    }

    public static function fromArray(array $raw, string $root): self
    {
        $base = '/' . trim(str_replace('\\', '/', (string) ($raw['base'] ?? '/')), '/');
        $base = $base === '/' ? '/' : $base . '/';
        $storage = rtrim(str_replace('\\', '/', (string) ($raw['storage_dir'] ?? $root . '/storage')), '/');

        return new self($root, $base, (string) hex2bin((string) $raw['app_secret']), $storage);
    }
}
