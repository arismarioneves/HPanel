<?php

/**
 * Configuração local de ambiente.
 *
 * COMO USAR:
 *   1. Copie este arquivo para "config.php" (na mesma pasta).
 *   2. Ajuste os valores conforme o ambiente.
 *
 * O "config.php" é local e NÃO é versionado (está no .gitignore).
 * Se o "config.php" não existir, a aplicação assume a raiz do domínio ("/").
 */

return [
    // Caminho base público onde o painel está instalado, relativo ao domínio.
    // Aceita com ou sem barras — é normalizado automaticamente.
    //   Raiz do domínio ....... '/'          ->  https://seudominio.com/
    //   Subpasta .............. '/hpanel/'    ->  https://seudominio.com/hpanel/
    'base' => '/hpanel/',
];
