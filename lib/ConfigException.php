<?php

declare(strict_types=1);

namespace HPanel;

/** Problema de instalação (config.php ou storage não graváveis). Mensagem é exibida ao usuário. */
final class ConfigException extends \RuntimeException
{
}
