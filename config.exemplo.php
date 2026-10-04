<?php

/**
 * Configuração local (copie para config.php — não versionado).
 * Se config.php não existir, o HPanel tenta criá-lo sozinho no primeiro acesso.
 */

return [
    // Caminho público do painel. '/' na raiz do domínio, '/hpanel/' numa subpasta.
    'base' => '/',

    // Onde ficam sessões e rate limit. Recomendado: FORA da pasta pública.
    // Padrão: <projeto>/storage (protegido por .htaccess no Apache).
    // 'storage_dir' => '/home/usuario/hpanel-storage',

    // 'app_secret' é gerado automaticamente. Trocar invalida todas as sessões.
];
