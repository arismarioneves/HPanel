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

        $secret = $raw['app_secret'] ?? null;
        if ($secret !== null && $secret !== '' && (!is_string($secret) || preg_match('/^[a-f0-9]{64}$/D', $secret) !== 1)) {
            throw new ConfigException(
                'app_secret inválido em config.php (esperado 64 caracteres hexadecimais). Corrija ou remova a linha para gerar um novo.'
            );
        }
        if ($secret === null || $secret === '') {
            $raw['app_secret'] = bin2hex(random_bytes(32));
            $php = "<?php\n\n// Gerado pelo HPanel. NÃO versionar: contém a chave das sessões.\nreturn " . var_export($raw, true) . ";\n";
            $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
            if (@file_put_contents($tmp, $php, LOCK_EX) === false || !@rename($tmp, $file)) {
                @unlink($tmp);
                throw new ConfigException(
                    'Não foi possível gravar o config.php. Copie config.exemplo.php para config.php e dê permissão de escrita ao PHP.'
                );
            }
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($file, true); // restrict_api em hospedagem compartilhada emite warning
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
