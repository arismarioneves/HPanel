<?php

declare(strict_types=1);

namespace HPanel;

/**
 * Criptografia das sessões. A chave depende do sid (que só existe no cookie do
 * navegador) e do app_secret (que só existe no servidor): vazar só o disco não
 * revela nenhum token.
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LEN = 12;
    private const TAG_LEN = 16;

    public static function key(string $sid, string $secret): string
    {
        return hash_hkdf('sha256', $sid, 32, 'hpanel-session', $secret);
    }

    public static function fileId(string $sid, string $secret): string
    {
        return hash_hmac('sha256', $sid, $secret);
    }

    public static function seal(string $plain, string $key): string
    {
        $iv = random_bytes(self::IV_LEN);
        $tag = '';
        $ct = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LEN);
        if ($ct === false) {
            throw new \RuntimeException('Falha ao cifrar a sessão.');
        }
        return base64_encode($iv . $tag . $ct);
    }

    public static function open(string $sealed, string $key): ?string
    {
        $bin = base64_decode($sealed, true);
        if ($bin === false || strlen($bin) <= self::IV_LEN + self::TAG_LEN) {
            return null;
        }
        $plain = openssl_decrypt(
            substr($bin, self::IV_LEN + self::TAG_LEN),
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            substr($bin, 0, self::IV_LEN),
            substr($bin, self::IV_LEN, self::TAG_LEN)
        );
        return $plain === false ? null : $plain;
    }
}
