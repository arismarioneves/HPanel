<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostinger Dashboard</title>
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
        require_once 'api/HostingerClient.php';
        require_once 'api/config.php';

        // Check if configured
        if (!isConfigured()) {
            echo '<div class="alert alert-warning">';
            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            echo '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>';
            echo '<line x1="12" y1="9" x2="12" y2="13"></line>';
            echo '<line x1="12" y1="17" x2="12.01" y2="17"></line>';
            echo '</svg>';
            echo 'Configure os cookies de autenticação para começar. <a href="settings.php" style="color: inherit; font-weight: 600;">Ir para Configurações</a>';
            echo '</div>';
            echo '</div></body></html>';
            exit;
        }

        $client = new HostingerClient();
        $websitesData = $client->getWebsites();

        if (!$websitesData || !isset($websitesData['data'])) {
            echo '<div class="alert alert-error">';
            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            echo '<circle cx="12" cy="12" r="10"></circle>';
            echo '<line x1="15" y1="9" x2="9" y2="15"></line>';
            echo '<line x1="9" y1="9" x2="15" y2="15"></line>';
            echo '</svg>';
            echo 'Erro ao carregar dados. Verifique se os cookies estão atualizados. <a href="settings.php" style="color: inherit; font-weight: 600;">Atualizar Cookies</a>';
            echo '</div>';
            echo '</div></body></html>';
            exit;
        }

        $resources = $websitesData['data']['resources'] ?? [];
        $totalServers = count($resources);
        $totalWebsites = 0;

        foreach ($resources as $resource) {
            $totalWebsites += count($resource['websites'] ?? []);
        }
        ?>

        <div class="page-title">
            <h1>Meus Servidores</h1>
            <p>Gerencie todos os seus recursos Hostinger em um só lugar</p>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-value"><?= $totalServers ?></span>
                <span class="stat-label">Servidores</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?= $totalWebsites ?></span>
                <span class="stat-label">Websites</span>
            </div>
        </div>

        <div class="cards-grid">
            <?php foreach ($resources as $resource): ?>
                <?php
                $websiteCount = count($resource['websites'] ?? []);
                $mainDomain = '';
                foreach ($resource['websites'] ?? [] as $site) {
                    if ($site['vhostType'] === 'main') {
                        $mainDomain = $site['domain'];
                        break;
                    }
                }
                if (!$mainDomain && !empty($resource['websites'])) {
                    $mainDomain = $resource['websites'][0]['domain'];
                }
                ?>
                <a href="server.php?orderId=<?= $resource['orderId'] ?>" class="server-card">
                    <div class="server-card-header">
                        <div>
                            <h3 class="server-title"><?= htmlspecialchars($resource['title'] ?? 'Servidor') ?></h3>
                            <span class="server-plan"><?= htmlspecialchars($resource['planDisplayableName'] ?? $resource['planName']) ?></span>
                        </div>
                    </div>

                    <div class="server-stats">
                        <div class="server-stat">
                            <span class="server-stat-value"><?= $websiteCount ?></span>
                            <span class="server-stat-label">Websites</span>
                        </div>
                        <div class="server-stat">
                            <span class="server-stat-value"><?= htmlspecialchars($resource['server']['hostname'] ?? 'N/A') ?></span>
                            <span class="server-stat-label">Servidor</span>
                        </div>
                    </div>

                    <div class="server-datacenter">
                        <span class="datacenter-flag">🌎</span>
                        <?= htmlspecialchars($resource['datacenter']['title'] ?? 'N/A') ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($resources)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3>Nenhum servidor encontrado</h3>
                <p>Não há servidores associados à sua conta.</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>