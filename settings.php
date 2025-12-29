<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Hostinger Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <header class="header">
            <a href="index.php" class="logo">
                <div class="logo-icon">H</div>
                <span class="logo-text">Hostinger Dashboard</span>
            </a>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Início
                </a>
                <a href="settings.php" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    Configurações
                </a>
            </div>
        </header>

        <?php
        require_once 'api/config.php';
        require_once 'api/HostingerClient.php';

        $message = '';
        $messageType = '';

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cookies = $_POST['cookies'] ?? '';
            $gaid = $_POST['gaid'] ?? '';

            // Extract cookies from cURL command if needed
            $cookies = extractCookies($cookies);

            $config = [
                'cookies' => $cookies,
                'gaid' => $gaid,
            ];

            if (saveConfig($config)) {
                // Test connection
                $client = new HostingerClient();
                if ($client->testConnection()) {
                    $message = 'Configurações salvas com sucesso! Conexão verificada.';
                    $messageType = 'success';
                } else {
                    $message = 'Configurações salvas, mas a conexão falhou. Verifique se os cookies estão corretos.';
                    $messageType = 'warning';
                }
            } else {
                $message = 'Erro ao salvar configurações.';
                $messageType = 'error';
            }
        }

        $config = getConfig();
        $isConnected = isConfigured();

        // Test current connection
        if ($isConnected) {
            $client = new HostingerClient();
            $isConnected = $client->testConnection();
        }
        ?>

        <a href="index.php" class="back-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Voltar para Dashboard
        </a>

        <div class="page-title">
            <h1>Configurações</h1>
            <p>Configure os cookies de autenticação para acessar a API da Hostinger</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?php if ($messageType === 'success'): ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                <?php elseif ($messageType === 'error'): ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                <?php else: ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                <?php endif; ?>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="status-indicator <?= $isConnected ? 'connected' : 'disconnected' ?>">
            <span class="status-dot"></span>
            <strong><?= $isConnected ? 'Conectado' : 'Desconectado' ?></strong>
            <?php if ($config['lastUpdated']): ?>
                <span style="color: var(--text-muted); margin-left: auto;">
                    Última atualização: <?= htmlspecialchars($config['lastUpdated']) ?>
                </span>
            <?php endif; ?>
        </div>

        <form method="post" action="" class="settings-form">
            <div class="form-group">
                <label class="form-label">Cookies de Autenticação</label>
                <p class="form-hint">
                    Cole aqui os cookies copiados do navegador. Você pode colar o comando cURL completo ou apenas a string de cookies.
                    <br><br>
                    <strong>Como obter:</strong>
                <ol style="margin-top: 10px; padding-left: 20px; color: var(--text-secondary);">
                    <li>Acesse <a href="https://hpanel.hostinger.com" target="_blank" style="color: var(--primary-light);">hpanel.hostinger.com</a> e faça login</li>
                    <li>Abra o DevTools (F12) → Aba Network</li>
                    <li>Clique em qualquer requisição</li>
                    <li>Clique com botão direito → Copy → Copy as cURL</li>
                    <li>Cole aqui</li>
                </ol>
                </p>
                <textarea
                    name="cookies"
                    class="form-textarea"
                    placeholder="Cole o comando cURL ou os cookies aqui..."
                    style="min-height: 300px;"><?= htmlspecialchars($config['cookies'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Google Analytics ID (GAID)</label>
                <p class="form-hint">ID do Google Analytics usado nas requisições.</p>
                <input
                    type="text"
                    name="gaid"
                    class="form-input"
                    value="<?= htmlspecialchars($config['gaid'] ?? 'GA1.1.000000000.0000000000') ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Salvar Configurações
            </button>
        </form>
    </div>
</body>

</html>