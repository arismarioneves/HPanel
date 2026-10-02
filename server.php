<?php require __DIR__ . '/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= APP_BASE ?>">
    <title>Detalhes do Servidor - Hostinger Dashboard</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" href="icon.png">
    <link rel="apple-touch-icon" href="icon.png">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="assets/config.js"></script>
    <script src="assets/ui.js"></script>
    <script src="assets/token-status.js"></script>
</head>

<body>
    <div class="container">
        <header class="header">
            <a href="<?= APP_BASE ?>" class="logo">
                <div class="logo-icon">H</div>
                <span class="logo-text">Hostinger Dashboard</span>
            </a>
            <div class="header-actions">
                <a href="<?= APP_BASE ?>" class="btn btn-secondary" data-tooltip="Início" data-tooltip-position="bottom" aria-label="Início">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </a>
                <a href="settings" class="btn btn-primary" data-tooltip="Configurações" data-tooltip-position="bottom" aria-label="Configurações">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </a>
            </div>
        </header>

        <?php
        require_once 'api/HostingerClient.php';
        require_once 'api/config.php';

        $orderId = isset($_GET['orderId']) ? (int)$_GET['orderId'] : 0;

        if (!$orderId) {
            header('Location: ' . APP_BASE);
            exit;
        }

        // Note: Configuration check is now done client-side via JavaScript
        // The HostingerClient will read cookies from HTTP headers sent by JavaScript

        $client = new HostingerClient();
        $websitesData = $client->getWebsites();

        if (!$websitesData || !isset($websitesData['data'])) {
            echo '<div class="alert alert-error">';
            echo 'Erro ao carregar dados. <a href="settings" style="color: inherit; font-weight: 600;">Atualizar Cookies</a>';
            echo '</div>';
            echo '</div></body></html>';
            exit;
        }

        // Find the specific server (page 1 first, then remaining pages)
        $server = null;
        foreach ($websitesData['data']['resources'] ?? [] as $resource) {
            if ($resource['orderId'] == $orderId) {
                $server = $resource;
                break;
            }
        }

        if (!$server) {
            $server = $client->findServerByOrderId($orderId);
        }

        if (!$server) {
            header('Location: ' . APP_BASE);
            exit;
        }

        $websites = $server['websites'] ?? [];

        // Get the main domain and username for account details
        $mainDomain = '';
        $username = '';
        foreach ($websites as $site) {
            if ($site['vhostType'] === 'main') {
                $mainDomain = $site['domain'];
                $username = $site['username'];
                break;
            }
        }
        if (!$mainDomain && !empty($websites)) {
            $mainDomain = $websites[0]['domain'];
            $username = $websites[0]['username'];
        }

        // Fetch account details for this server
        $accountData = null;
        if ($username && $mainDomain) {
            $accountData = $client->getAccountDetails($orderId, $username, $mainDomain);
        }
        $account = $accountData['data'] ?? null;

        $filter = $_GET['filter'] ?? 'all';
        $search = $_GET['search'] ?? '';

        // Filter websites
        $filteredWebsites = array_filter($websites, function ($site) use ($filter, $search) {
            if ($search && stripos($site['domain'], $search) === false) {
                return false;
            }
            switch ($filter) {
                case 'wordpress':
                    return $site['type'] === 'wordpress';
                case 'other':
                    return $site['type'] === 'other';
                case 'main':
                    return $site['vhostType'] === 'main';
                case 'addon':
                    return $site['vhostType'] === 'addon';
                case 'subdomain':
                    return $site['vhostType'] === 'subdomain';
                default:
                    return true;
            }
        });

        // Count by type
        $counts = [
            'all' => count($websites),
            'wordpress' => count(array_filter($websites, fn($s) => $s['type'] === 'wordpress')),
            'other' => count(array_filter($websites, fn($s) => $s['type'] === 'other')),
            'main' => count(array_filter($websites, fn($s) => $s['vhostType'] === 'main')),
            'addon' => count(array_filter($websites, fn($s) => $s['vhostType'] === 'addon')),
            'subdomain' => count(array_filter($websites, fn($s) => $s['vhostType'] === 'subdomain')),
        ];

        // Helper function for usage percentage
        function getUsageClass(float $percentage): string
        {
            if ($percentage < 50) return 'low';
            if ($percentage < 80) return 'medium';
            return 'high';
        }

        function formatBytes(float $bytes, int $precision = 2): string
        {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= pow(1024, $pow);
            return round($bytes, $precision) . ' ' . $units[$pow];
        }

        function formatNumber(float $num): string
        {
            if ($num >= 1000000) {
                return round($num / 1000000, 1) . 'M';
            }
            if ($num >= 1000) {
                return round($num / 1000, 1) . 'K';
            }
            return (string)$num;
        }
        ?>

        <a href="<?= APP_BASE ?>" class="back-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Voltar para Servidores
        </a>

        <div class="page-title">
            <h1><?= htmlspecialchars($server['title'] ?? 'Servidor') ?></h1>
            <p><?= htmlspecialchars($server['planDisplayableName'] ?? $server['planName'] ?? 'N/A') ?> • <?= htmlspecialchars($server['datacenter']['title'] ?? 'N/A') ?></p>
        </div>

        <?php if ($account): ?>
            <!-- Server Quick Info -->
            <div class="server-quick-info">
                <div class="quick-info-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                        <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                        <line x1="6" y1="6" x2="6.01" y2="6"></line>
                        <line x1="6" y1="18" x2="6.01" y2="18"></line>
                    </svg>
                    IP: <strong><?= htmlspecialchars($account['ip'] ?? 'N/A') ?></strong>
                </div>
                <div class="quick-info-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                    Web Server: <strong><?= htmlspecialchars($account['web_server'] ?? 'N/A') ?></strong>
                </div>
                <div class="quick-info-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                    </svg>
                    MariaDB: <strong><?= htmlspecialchars($account['database_version'] ?? 'N/A') ?></strong>
                </div>
                <div class="quick-info-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Username: <strong><?= htmlspecialchars($account['username'] ?? 'N/A') ?></strong>
                </div>
                <div class="quick-info-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Criado: <strong><?= !empty($account['created_at']) ? date('d/m/Y', strtotime($account['created_at'])) : 'N/A' ?></strong>
                </div>
            </div>

            <!-- Usage Charts -->
            <div class="server-info-section">
                <h3 class="section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                    Uso de Recursos
                </h3>
                <div class="usage-charts-grid">
                    <?php
                    $usageItems = [
                        'storage' => ['label' => 'Armazenamento', 'icon' => '💾', 'format' => 'bytes'],
                        'inodes' => ['label' => 'Inodes', 'icon' => '📁', 'format' => 'number'],
                        'databases' => ['label' => 'Bancos de Dados', 'icon' => '🗄️', 'format' => 'number'],
                        'subdomains' => ['label' => 'Subdomínios', 'icon' => '🌐', 'format' => 'number'],
                        'ftp_accounts' => ['label' => 'Contas FTP', 'icon' => '📂', 'format' => 'number'],
                    ];

                    foreach ($usageItems as $key => $item):
                        $usage = $account['usage'][$key] ?? null;
                        if (!$usage) continue;

                        $value = $usage['value'] ?? 0;
                        $limit = $usage['limit'] ?? null;

                        if ($limit === null) continue;

                        $percentage = $limit > 0 ? min(100, round(($value / $limit) * 100, 1)) : 0;
                        $usageClass = getUsageClass($percentage);

                        // Format values based on type
                        if ($item['format'] === 'bytes') {
                            $valueFormatted = formatBytes($value * 1024 * 1024); // Convert MB to bytes
                            $limitFormatted = formatBytes($limit * 1024 * 1024);
                        } else {
                            $valueFormatted = formatNumber($value);
                            $limitFormatted = formatNumber($limit);
                        }
                    ?>
                        <div class="usage-chart-card">
                            <div class="usage-chart-header">
                                <span class="usage-chart-title"><span aria-hidden="true"><?= $item['icon'] ?></span> <?= htmlspecialchars($item['label']) ?></span>
                                <span class="usage-chart-percentage <?= $usageClass ?>"><?= $percentage ?>%</span>
                            </div>
                            <div class="progress-bar" role="progressbar" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= htmlspecialchars($item['label']) ?>">
                                <div class="progress-bar-fill <?= $usageClass ?>" style="width: <?= $percentage ?>%"></div>
                            </div>
                            <div class="usage-chart-details">
                                <span><?= $valueFormatted ?> usado</span>
                                <span><?= $limitFormatted ?> total</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Plan Limits -->
            <div class="server-info-section">
                <h3 class="section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                    Limites do Plano
                </h3>
                <div class="plan-limits-grid">
                    <?php if (isset($account['plan_limits'])): ?>
                        <div class="plan-limit-item">
                            <div class="plan-limit-value"><?= htmlspecialchars((string)($account['plan_limits']['cpu_cores'] ?? 'N/A')) ?></div>
                            <div class="plan-limit-label">CPU Cores</div>
                        </div>
                        <div class="plan-limit-item">
                            <div class="plan-limit-value"><?= formatBytes(($account['plan_limits']['ram'] ?? 0) * 1024) ?></div>
                            <div class="plan-limit-label">RAM</div>
                        </div>
                        <div class="plan-limit-item">
                            <div class="plan-limit-value"><?= htmlspecialchars((string)($account['plan_limits']['entry_processes'] ?? 'N/A')) ?></div>
                            <div class="plan-limit-label">Entry Processes</div>
                        </div>
                        <div class="plan-limit-item">
                            <div class="plan-limit-value"><?= htmlspecialchars((string)($account['plan_limits']['active_processes'] ?? 'N/A')) ?></div>
                            <div class="plan-limit-label">Active Processes</div>
                        </div>
                        <div class="plan-limit-item">
                            <div class="plan-limit-value"><?= htmlspecialchars((string)($account['plan_limits']['max_addons'] ?? 'N/A')) ?></div>
                            <div class="plan-limit-label">Max Addons</div>
                        </div>
                        <div class="plan-limit-item">
                            <div class="plan-limit-value">∞</div>
                            <div class="plan-limit-label">Bandwidth</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Server Info Cards -->
            <div class="server-info-section">
                <h3 class="section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    Informações do Servidor
                </h3>
                <div class="info-cards-grid">
                    <div class="info-card">
                        <span class="info-card-label">Hostname</span>
                        <span class="info-card-value"><?= htmlspecialchars($account['server']['hostname'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-card">
                        <span class="info-card-label">Database Host</span>
                        <span class="info-card-value"><?= htmlspecialchars($account['server']['database']['hostname'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-card">
                        <span class="info-card-label">Database IP</span>
                        <span class="info-card-value"><?= htmlspecialchars($account['server']['database']['ip'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-card">
                        <span class="info-card-label">FTP User</span>
                        <span class="info-card-value"><?= htmlspecialchars($account['ftp_user'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-card">
                        <span class="info-card-label">Shell</span>
                        <span class="info-card-value highlight"><?= ($account['shell_enabled'] ?? false) ? 'Habilitado' : 'Desabilitado' ?></span>
                    </div>
                    <div class="info-card">
                        <span class="info-card-label">Backup</span>
                        <span class="info-card-value"><?php $bi = (int)($account['backup_interval'] ?? 0); echo $bi === 1 ? 'Diário' : ($bi > 0 ? 'A cada ' . $bi . ' dias' : 'Indisponível'); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($username && $mainDomain): ?>
            <!-- SSH Key (Git) -->
            <div class="server-info-section">
                <h3 class="section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                    </svg>
                    Chave SSH (Git)
                </h3>
                <div class="ssh-key-section">
                    <p class="ssh-key-hint">
                        Chave pública usada para deploy via Git nesta conta (<strong><?= htmlspecialchars($username) ?></strong>).
                        Ao criar uma chave SSH, o hPanel volta a exibir a interface de deploy via SSH (em vez do GitHub App).
                    </p>
                    <button class="btn btn-primary" onclick="openSshKeyModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                        </svg>
                        Gerenciar chave SSH
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-value"><?= count($websites) ?></span>
                <span class="stat-label">Websites</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?= $counts['wordpress'] ?></span>
                <span class="stat-label">WordPress</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?= $counts['subdomain'] ?></span>
                <span class="stat-label">Subdomínios</span>
            </div>
        </div>

        <form method="get" action="" class="search-box">
            <input type="hidden" name="orderId" value="<?= $orderId ?>">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="search" placeholder="Buscar domínio..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px;">Buscar</button>
        </form>

        <div class="filter-tabs">
            <a href="?orderId=<?= $orderId ?>&filter=all&search=<?= urlencode($search) ?>" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
                Todos (<?= $counts['all'] ?>)
            </a>
            <a href="?orderId=<?= $orderId ?>&filter=wordpress&search=<?= urlencode($search) ?>" class="filter-tab <?= $filter === 'wordpress' ? 'active' : '' ?>">
                WordPress (<?= $counts['wordpress'] ?>)
            </a>
            <a href="?orderId=<?= $orderId ?>&filter=other&search=<?= urlencode($search) ?>" class="filter-tab <?= $filter === 'other' ? 'active' : '' ?>">
                Outros (<?= $counts['other'] ?>)
            </a>
            <a href="?orderId=<?= $orderId ?>&filter=main&search=<?= urlencode($search) ?>" class="filter-tab <?= $filter === 'main' ? 'active' : '' ?>">
                Principal (<?= $counts['main'] ?>)
            </a>
            <a href="?orderId=<?= $orderId ?>&filter=addon&search=<?= urlencode($search) ?>" class="filter-tab <?= $filter === 'addon' ? 'active' : '' ?>">
                Addon (<?= $counts['addon'] ?>)
            </a>
            <a href="?orderId=<?= $orderId ?>&filter=subdomain&search=<?= urlencode($search) ?>" class="filter-tab <?= $filter === 'subdomain' ? 'active' : '' ?>">
                Subdomínio (<?= $counts['subdomain'] ?>)
            </a>
        </div>

        <?php
        // Organize websites: group subdomains under their parent domains
        $organizedSites = [];
        $subdomains = [];

        // First pass: separate main/addon domains from subdomains (flat list)
        foreach ($filteredWebsites as $site) {
            if (($site['vhostType'] ?? '') === 'subdomain') {
                $subdomains[] = $site;
            } else {
                $organizedSites[] = $site;
            }
        }

        // Sort: main first, then addons
        usort($organizedSites, function ($a, $b) {
            if ($a['vhostType'] === 'main' && $b['vhostType'] !== 'main') return -1;
            if ($a['vhostType'] !== 'main' && $b['vhostType'] === 'main') return 1;
            return strcmp($a['domain'], $b['domain']);
        });

        // Attach each subdomain to its longest-matching parent; keep the rest as orphans
        $childrenByParent = [];
        $orphanSubdomains = [];
        $parentDomains = array_column($organizedSites, 'domain');
        foreach ($subdomains as $sub) {
            $bestParent = null;
            foreach ($parentDomains as $parent) {
                if ($parent !== '' && str_ends_with($sub['domain'], '.' . $parent)) {
                    if ($bestParent === null || strlen($parent) > strlen($bestParent)) {
                        $bestParent = $parent;
                    }
                }
            }
            if ($bestParent !== null) {
                $childrenByParent[$bestParent][] = $sub;
            } else {
                $orphanSubdomains[] = $sub;
            }
        }
        ?>

        <div class="websites-list">
            <?php foreach ($organizedSites as $site): ?>
                <?php
                $siteSubdomains = $childrenByParent[$site['domain']] ?? [];
                $hasSubdomains = !empty($siteSubdomains);
                ?>
                <div class="website-group">
                    <div class="website-item <?= $hasSubdomains ? 'has-subdomains' : '' ?>">
                        <div class="website-info">
                            <span class="website-domain">
                                <?php if ($site['vhostType'] === 'main'): ?>
                                    <span style="color: var(--warning); margin-right: 4px;">★</span>
                                <?php endif; ?>
                                <?= htmlspecialchars($site['domain']) ?>
                            </span>
                            <div class="website-meta">
                                <span>Criado: <?= !empty($site['createdAt']) ? date('d/m/Y', strtotime($site['createdAt'])) : 'N/A' ?></span>
                                <span>•</span>
                                <span>Atualizado: <?= !empty($site['updatedAt']) ? date('d/m/Y', strtotime($site['updatedAt'])) : 'N/A' ?></span>
                                <?php if ($hasSubdomains): ?>
                                    <span>•</span>
                                    <span style="color: var(--accent);"><?= count($siteSubdomains) ?> subdomínio(s)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="website-actions">
                            <div class="website-badges">
                                <span class="badge badge-<?= $site['type'] === 'wordpress' ? 'wordpress' : 'other' ?>">
                                    <?= $site['type'] === 'wordpress' ? 'WordPress' : 'Outro' ?>
                                </span>
                                <span class="badge badge-<?= $site['vhostType'] ?>">
                                    <?= ucfirst($site['vhostType']) ?>
                                </span>
                                <span class="badge badge-<?= $site['status'] === 'enabled' ? 'enabled' : 'disabled' ?>">
                                    <?= $site['status'] === 'enabled' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </div>
                            <?php if ($hasSubdomains): ?>
                                <button class="btn btn-secondary btn-icon toggle-subdomains"
                                    onclick="toggleSubdomains(this)"
                                    data-tooltip="Ver subdomínios" aria-label="Ver subdomínios">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-secondary btn-icon btn-databases"
                                onclick="toggleDatabases(this, '<?= htmlspecialchars($site['username']) ?>', '<?= htmlspecialchars($site['domain']) ?>', <?= $orderId ?>)"
                                data-tooltip="Bancos de Dados" aria-label="Bancos de Dados">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                                </svg>
                            </button>
                            <button class="btn btn-secondary btn-icon btn-files"
                                onclick="openFileBrowser(this, '<?= htmlspecialchars($site['username']) ?>', '<?= htmlspecialchars($site['domain']) ?>', <?= $orderId ?>)"
                                data-tooltip="Gerenciador de Arquivos" aria-label="Gerenciador de Arquivos">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                </svg>
                            </button>
                            <a href="https://hpanel.hostinger.com/websites/<?= htmlspecialchars($site['domain']) ?>"
                                target="_blank"
                                class="btn btn-external btn-icon"
                                data-tooltip="Abrir no hPanel" data-tooltip-position="left" aria-label="Abrir no hPanel">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15 3 21 3 21 9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                            </a>
                            <!-- Dropdown Menu -->
                            <div class="dropdown-menu-wrapper">
                                <button class="btn btn-secondary btn-icon btn-more"
                                    onclick="toggleDropdownMenu(this, event)"
                                    data-tooltip="Mais opções" data-tooltip-position="left" aria-label="Mais opções">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="1"></circle>
                                        <circle cx="19" cy="12" r="1"></circle>
                                        <circle cx="5" cy="12" r="1"></circle>
                                    </svg>
                                </button>
                                <div class="dropdown-menu" style="display: none;">
                                    <button class="dropdown-item"
                                        onclick="openPhpVersionModal('<?= htmlspecialchars($site['username']) ?>', '<?= htmlspecialchars($site['domain']) ?>', <?= $orderId ?>)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                            <polyline points="2 17 12 22 22 17"></polyline>
                                            <polyline points="2 12 12 17 22 12"></polyline>
                                        </svg>
                                        Alterar versão PHP
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Databases Container -->
                    <div class="databases-container" style="display: none;" data-loaded="false">
                        <div class="databases-loading">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 6v6l4 2"></path>
                            </svg>
                            Carregando bancos de dados...
                        </div>
                        <div class="databases-list"></div>
                        <div class="databases-empty" style="display: none;">
                            <span>Nenhum banco de dados encontrado</span>
                        </div>
                    </div>

                    <?php if ($hasSubdomains): ?>
                        <div class="subdomains-container" style="display: none;">
                            <?php foreach ($siteSubdomains as $subdomain): ?>
                                <div class="website-item subdomain-item">
                                    <div class="website-info">
                                        <span class="website-domain subdomain-domain">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px; opacity: 0.5;">
                                                <polyline points="9 18 15 12 9 6"></polyline>
                                            </svg>
                                            <?= htmlspecialchars($subdomain['domain']) ?>
                                        </span>
                                        <div class="website-meta">
                                            <span>Criado: <?= !empty($subdomain['createdAt']) ? date('d/m/Y', strtotime($subdomain['createdAt'])) : 'N/A' ?></span>
                                            <span>•</span>
                                            <span>Atualizado: <?= !empty($subdomain['updatedAt']) ? date('d/m/Y', strtotime($subdomain['updatedAt'])) : 'N/A' ?></span>
                                        </div>
                                    </div>
                                    <div class="website-actions">
                                        <div class="website-badges">
                                            <span class="badge badge-<?= $subdomain['type'] === 'wordpress' ? 'wordpress' : 'other' ?>">
                                                <?= $subdomain['type'] === 'wordpress' ? 'WordPress' : 'Outro' ?>
                                            </span>
                                            <span class="badge badge-subdomain">Subdomain</span>
                                            <span class="badge badge-<?= $subdomain['status'] === 'enabled' ? 'enabled' : 'disabled' ?>">
                                                <?= $subdomain['status'] === 'enabled' ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </div>
                                        <!-- No hPanel link for subdomains -->
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($orphanSubdomains)): ?>
                <?php foreach ($orphanSubdomains as $subdomain): ?>
                    <div class="website-group">
                        <div class="website-item subdomain-item">
                            <div class="website-info">
                                <span class="website-domain subdomain-domain">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px; opacity: 0.5;" aria-hidden="true">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                    <?= htmlspecialchars($subdomain['domain']) ?>
                                </span>
                                <div class="website-meta">
                                    <span>Criado: <?= !empty($subdomain['createdAt']) ? date('d/m/Y', strtotime($subdomain['createdAt'])) : 'N/A' ?></span>
                                    <span>•</span>
                                    <span>Atualizado: <?= !empty($subdomain['updatedAt']) ? date('d/m/Y', strtotime($subdomain['updatedAt'])) : 'N/A' ?></span>
                                </div>
                            </div>
                            <div class="website-actions">
                                <div class="website-badges">
                                    <span class="badge badge-<?= ($subdomain['type'] ?? '') === 'wordpress' ? 'wordpress' : 'other' ?>">
                                        <?= ($subdomain['type'] ?? '') === 'wordpress' ? 'WordPress' : 'Outro' ?>
                                    </span>
                                    <span class="badge badge-subdomain">Subdomain</span>
                                    <span class="badge badge-<?= ($subdomain['status'] ?? '') === 'enabled' ? 'enabled' : 'disabled' ?>">
                                        <?= ($subdomain['status'] ?? '') === 'enabled' ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (empty($organizedSites) && empty($orphanSubdomains)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon" aria-hidden="true">🔍</div>
                    <h3>Nenhum website encontrado</h3>
                    <p>Tente ajustar os filtros ou a busca.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSubdomains(button) {
            const group = button.closest('.website-group');
            const container = group.querySelector('.subdomains-container');
            const icon = button.querySelector('svg');

            if (container.style.display === 'none') {
                container.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                container.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }

        async function openFileBrowser(button, username, domain, orderId) {
            const originalContent = button.innerHTML;

            // Show loading state
            button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>';
            button.disabled = true;

            try {
                const response = await HostingerConfig.fetch(`api/file-browser.php?username=${encodeURIComponent(username)}&domain=${encodeURIComponent(domain)}&orderId=${orderId}`);
                const data = await response.json();

                if (data.success && data.link) {
                    window.open(data.link, '_blank');
                } else {
                    UI.alert('Erro ao obter link do gerenciador de arquivos', { variant: 'danger', title: 'Erro' });
                }
            } catch (error) {
                console.error('Error:', error);
                UI.alert('Erro ao conectar com o servidor', { variant: 'danger', title: 'Erro' });
            } finally {
                button.innerHTML = originalContent;
                button.disabled = false;
            }
        }

        async function toggleDatabases(button, username, domain, orderId) {
            const group = button.closest('.website-group');
            const container = group.querySelector('.databases-container');
            const loading = container.querySelector('.databases-loading');
            const list = container.querySelector('.databases-list');
            const empty = container.querySelector('.databases-empty');

            // Toggle visibility
            if (container.style.display === 'none') {
                container.style.display = 'block';

                // Load databases if not already loaded
                if (container.dataset.loaded === 'false') {
                    loading.style.display = 'flex';
                    list.innerHTML = '';
                    empty.style.display = 'none';

                    try {
                        const response = await HostingerConfig.fetch(`api/databases.php?username=${encodeURIComponent(username)}&domain=${encodeURIComponent(domain)}&orderId=${orderId}`);
                        const data = await response.json();

                        loading.style.display = 'none';
                        container.dataset.loaded = 'true';

                        if (data.success && data.databases && data.databases.length > 0) {
                            data.databases.forEach(db => {
                                const dbItem = document.createElement('div');
                                dbItem.className = 'database-item';
                                const sizeMb = db.diskUsageMb || 0;
                                const sizeDisplay = sizeMb >= 1024 ? (sizeMb / 1024).toFixed(1) + ' GB' : sizeMb + ' MB';
                                dbItem.innerHTML = `
                                    <div class="database-info">
                                        <span class="database-name">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                                                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                                                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                                            </svg>
                                            <span class="database-name-text"></span>
                                        </span>
                                        <span class="database-meta"></span>
                                    </div>
                                    <button class="btn btn-primary btn-icon btn-phpmyadmin" data-tooltip="Abrir phpMyAdmin" data-tooltip-position="left" aria-label="Abrir phpMyAdmin">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                            <polyline points="15 3 21 3 21 9"></polyline>
                                            <line x1="10" y1="14" x2="21" y2="3"></line>
                                        </svg>
                                    </button>
                                `;
                                // Dados dinâmicos via textContent/listener (sem interpolação em HTML)
                                dbItem.querySelector('.database-name-text').textContent = db.name;
                                dbItem.querySelector('.database-meta').textContent = `Usuário: ${db.user || 'N/A'} • Tamanho: ${sizeDisplay}`;
                                dbItem.querySelector('.btn-phpmyadmin').addEventListener('click', function() {
                                    openPhpMyAdmin(username, db.name, domain, orderId, this);
                                });
                                list.appendChild(dbItem);
                            });
                        } else {
                            empty.style.display = 'block';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        loading.style.display = 'none';
                        container.dataset.loaded = 'false';
                        list.innerHTML = '<div class="databases-error" style="color: var(--danger); padding: var(--spacing-sm) 0;">Erro ao carregar bancos. Tente novamente.</div>';
                    }
                }
            } else {
                container.style.display = 'none';
            }
        }

        async function openPhpMyAdmin(username, dbName, domain, orderId, button) {
            const originalContent = button.innerHTML;

            button.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>';
            button.disabled = true;

            try {
                const response = await HostingerConfig.fetch(`api/phpmyadmin.php?username=${encodeURIComponent(username)}&dbName=${encodeURIComponent(dbName)}&domain=${encodeURIComponent(domain)}&orderId=${orderId}`);
                const data = await response.json();

                if (data.success && data.link) {
                    window.open(data.link, '_blank');
                } else {
                    UI.alert('Erro ao obter link do phpMyAdmin', { variant: 'danger', title: 'Erro' });
                }
            } catch (error) {
                console.error('Error:', error);
                UI.alert('Erro ao conectar com o servidor', { variant: 'danger', title: 'Erro' });
            } finally {
                button.innerHTML = originalContent;
                button.disabled = false;
            }
        }
    </script>

    <style>
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        .btn-files {
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.3);
        }

        .btn-files:hover {
            background: rgba(139, 92, 246, 0.25);
            border-color: rgba(139, 92, 246, 0.5);
        }

        .btn-databases {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .btn-databases:hover {
            background: rgba(34, 197, 94, 0.25);
            border-color: rgba(34, 197, 94, 0.5);
        }

        .databases-container {
            margin-left: var(--spacing-lg);
            padding-left: var(--spacing-md);
            border-left: 2px solid rgba(34, 197, 94, 0.3);
            margin-top: var(--spacing-xs);
        }

        .databases-loading {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .database-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-sm) var(--spacing-md);
            background: rgba(34, 197, 94, 0.05);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-xs);
        }

        .database-item:hover {
            border-color: rgba(34, 197, 94, 0.4);
        }

        .database-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .database-name {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .database-name svg {
            color: var(--success);
        }

        .database-meta {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-left: 20px;
        }

        .databases-empty {
            padding: var(--spacing-sm);
            color: var(--text-muted);
            font-size: 0.9rem;
            font-style: italic;
        }

        .btn-phpmyadmin {
            padding: 4px 8px;
        }

        /* Dropdown Menu */
        .dropdown-menu-wrapper {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            min-width: 180px;
            background: var(--bg-card);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            z-index: 100;
            margin-top: 4px;
            overflow: hidden;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 10px 14px;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 0.9rem;
            cursor: pointer;
            text-align: left;
            transition: background var(--transition-fast);
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .dropdown-item svg {
            color: var(--accent);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-normal);
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: var(--bg-card);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            max-width: 400px;
            width: 90%;
            transform: translateY(-20px);
            transition: transform var(--transition-normal);
        }

        .modal-overlay.active .modal {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }

        .modal-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
        }

        .modal-close:hover {
            color: var(--text-primary);
        }

        .modal-body {
            margin-bottom: var(--spacing-md);
        }

        .modal-domain {
            font-size: 0.9rem;
            color: var(--accent);
            margin-bottom: var(--spacing-sm);
        }

        .version-select {
            width: 100%;
            padding: 10px 14px;
            background: #1a1a2e;
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            color: #e0e0e0;
            font-size: 0.95rem;
        }

        .version-select option {
            background: #1a1a2e;
            color: #e0e0e0;
        }

        .version-select:focus {
            outline: none;
            border-color: var(--accent);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-sm);
        }

        .modal-wide {
            max-width: 640px;
        }

        /* SSH Key */
        .ssh-key-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }

        .ssh-key-section .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .ssh-key-hint {
            color: var(--text-muted);
            font-size: 0.9rem;
            max-width: 640px;
        }

        .ssh-key-value {
            background: #1a1a2e;
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            color: #e0e0e0;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.8rem;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 220px;
            overflow-y: auto;
            margin: 0;
        }

        .ssh-key-status {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) 0;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .ssh-key-note {
            font-size: 0.85rem;
            color: var(--warning);
            margin-top: var(--spacing-sm);
        }

        .ssh-key-error {
            color: var(--danger);
            font-size: 0.9rem;
            padding: var(--spacing-sm) 0;
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
        }

        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: rgba(239, 68, 68, 0.5);
        }
    </style>

    <!-- SSH Key Modal -->
    <div id="sshKeyModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="sshKeyModalTitle">
        <div class="modal modal-wide">
            <div class="modal-header">
                <span class="modal-title" id="sshKeyModalTitle">Chave SSH (Git)</span>
                <button class="modal-close" onclick="closeSshKeyModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-domain"><?= htmlspecialchars($username) ?> • <?= htmlspecialchars($mainDomain) ?></p>

                <div id="sshKeyLoading" class="ssh-key-status" style="display: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                    <span id="sshKeyLoadingText">Carregando...</span>
                </div>

                <div id="sshKeyEmpty" style="display: none;">
                    <div class="ssh-key-status">Nenhuma chave SSH criada para esta conta.</div>
                    <p class="ssh-key-note">
                        ⚠️ Ao criar uma chave SSH, o hPanel passa a exibir a interface de deploy via SSH
                        (em vez do GitHub App) para os sites desta conta.
                    </p>
                </div>

                <div id="sshKeyView" style="display: none;">
                    <pre id="sshKeyValue" class="ssh-key-value"></pre>
                    <p class="ssh-key-note" style="display: none;" id="sshKeyRecreateNote">
                        ⚠️ Recriar a chave invalida a chave atual: deploys que usam a chave antiga deixarão de funcionar
                        até você cadastrar a nova chave no repositório remoto.
                    </p>
                </div>

                <div id="sshKeyError" class="ssh-key-error" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeSshKeyModal()">Fechar</button>
                <button class="btn btn-danger" id="sshKeyRecreateBtn" style="display: none;" onclick="recreateSshKey()">Recriar chave</button>
                <button class="btn btn-secondary" id="sshKeyCopyBtn" style="display: none;" onclick="copySshKey(this)">Copiar</button>
                <button class="btn btn-primary" id="sshKeyCreateBtn" style="display: none;" onclick="createSshKey()">Criar chave SSH</button>
            </div>
        </div>
    </div>

    <!-- PHP Version Modal -->
    <div id="phpVersionModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="phpVersionModalTitle">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title" id="phpVersionModalTitle">Alterar Versão PHP</span>
                <button class="modal-close" onclick="closePhpVersionModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-domain" id="phpModalDomain"></p>
                <label style="display: block; margin-bottom: 8px; color: var(--text-secondary);">
                    Selecione a versão:
                </label>
                <select id="phpVersionSelect" class="version-select">
                    <option value="">Carregando...</option>
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closePhpVersionModal()">Cancelar</button>
                <button class="btn btn-primary" id="phpVersionSaveBtn" onclick="savePhpVersion()">Salvar</button>
            </div>
        </div>
    </div>

    <script>
        // Store current modal data
        let currentPhpModal = {
            username: '',
            domain: '',
            orderId: 0
        };

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown-menu-wrapper')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });

        // Fecha modais com Esc ou clique no overlay (fundo)
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.classList.remove('active');
            });
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
            }
        });

        function toggleDropdownMenu(button, event) {
            event.stopPropagation();
            const menu = button.nextElementSibling;
            const isVisible = menu.style.display === 'block';

            // Close all other menus
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                m.style.display = 'none';
            });

            menu.style.display = isVisible ? 'none' : 'block';
        }

        async function openPhpVersionModal(username, domain, orderId) {
            // Close dropdown
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                m.style.display = 'none';
            });

            currentPhpModal = {
                username,
                domain,
                orderId
            };
            const modal = document.getElementById('phpVersionModal');
            const select = document.getElementById('phpVersionSelect');

            document.getElementById('phpModalDomain').textContent = domain;
            select.innerHTML = '<option value="">Carregando...</option>';
            select.disabled = true;

            modal.classList.add('active');

            try {
                const response = await HostingerConfig.fetch(`api/php-version.php?username=${encodeURIComponent(username)}&domain=${encodeURIComponent(domain)}&orderId=${orderId}`);
                const data = await response.json();

                if (data.success && data.versions) {
                    select.innerHTML = '';
                    for (const [version, label] of Object.entries(data.versions)) {
                        const option = document.createElement('option');
                        option.value = version;
                        option.textContent = label;
                        if (version === data.current) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    }
                    select.disabled = false;
                } else {
                    select.innerHTML = '<option value="">Erro ao carregar versões</option>';
                }
            } catch (error) {
                console.error('Error:', error);
                select.innerHTML = '<option value="">Erro ao carregar versões</option>';
            }
        }

        function closePhpVersionModal() {
            document.getElementById('phpVersionModal').classList.remove('active');
        }

        async function savePhpVersion() {
            const select = document.getElementById('phpVersionSelect');
            const saveBtn = document.getElementById('phpVersionSaveBtn');
            const version = select.value;

            if (!version) {
                await UI.alert('Selecione uma versão', { variant: 'warning' });
                return;
            }

            saveBtn.disabled = true;
            saveBtn.textContent = 'Salvando...';

            try {
                const response = await HostingerConfig.fetch('api/set-php-version.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        username: currentPhpModal.username,
                        domain: currentPhpModal.domain,
                        orderId: currentPhpModal.orderId,
                        phpVersion: version
                    })
                });
                const data = await response.json().catch(() => ({}));

                if (response.ok && data.success) {
                    closePhpVersionModal();
                    UI.alert(`Versão PHP alterada para ${version} com sucesso!`, { variant: 'success', title: 'Pronto' });
                } else {
                    UI.alert(data.error || 'Não foi possível alterar a versão PHP. Tente novamente.', { variant: 'danger', title: 'Erro' });
                }
            } catch (error) {
                console.error('Error:', error);
                UI.alert('Erro de conexão ao alterar a versão PHP.', { variant: 'danger', title: 'Erro' });
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Salvar';
            }
        }

        // ===== SSH Key (Git) =====
        const sshKeyCtx = {
            username: '<?= htmlspecialchars($username) ?>',
            domain: '<?= htmlspecialchars($mainDomain) ?>',
            orderId: <?= $orderId ?>
        };

        function openSshKeyModal() {
            document.getElementById('sshKeyModal').classList.add('active');
            loadSshKey();
        }

        function closeSshKeyModal() {
            document.getElementById('sshKeyModal').classList.remove('active');
        }

        function sshKeySetState(state, message = '') {
            const el = id => document.getElementById(id);
            el('sshKeyLoading').style.display = state === 'loading' ? 'flex' : 'none';
            el('sshKeyEmpty').style.display = state === 'empty' ? 'block' : 'none';
            el('sshKeyView').style.display = state === 'view' ? 'block' : 'none';
            el('sshKeyError').style.display = state === 'error' ? 'block' : 'none';
            el('sshKeyRecreateNote').style.display = state === 'view' ? 'block' : 'none';
            el('sshKeyCreateBtn').style.display = state === 'empty' ? 'inline-block' : 'none';
            el('sshKeyCopyBtn').style.display = state === 'view' ? 'inline-block' : 'none';
            el('sshKeyRecreateBtn').style.display = state === 'view' ? 'inline-block' : 'none';
            if (state === 'error') el('sshKeyError').textContent = message;
            if (state === 'loading') el('sshKeyLoadingText').textContent = message || 'Carregando...';
        }

        async function loadSshKey() {
            sshKeySetState('loading');
            try {
                const response = await HostingerConfig.fetch(`api/ssh-key.php?username=${encodeURIComponent(sshKeyCtx.username)}&domain=${encodeURIComponent(sshKeyCtx.domain)}&orderId=${sshKeyCtx.orderId}`);
                const data = await response.json();

                if (data.success) {
                    if (data.publicKey) {
                        document.getElementById('sshKeyValue').textContent = data.publicKey;
                        sshKeySetState('view');
                    } else {
                        sshKeySetState('empty');
                    }
                } else {
                    sshKeySetState('error', data.error || 'Erro ao consultar a chave SSH. Verifique o token JWT.');
                }
            } catch (error) {
                console.error('Error:', error);
                sshKeySetState('error', 'Erro ao conectar com o servidor');
            }
        }

        async function createSshKey() {
            sshKeySetState('loading', 'Criando chave SSH...');
            try {
                const response = await HostingerConfig.fetch('api/ssh-key.php', {
                    method: 'POST',
                    body: JSON.stringify(sshKeyCtx)
                });
                const data = await response.json();

                if (data.success && data.publicKey) {
                    document.getElementById('sshKeyValue').textContent = data.publicKey;
                    sshKeySetState('view');
                } else if (data.errorCode === 9999) {
                    // Chave já existe — recarrega e exibe a atual
                    await loadSshKey();
                } else {
                    sshKeySetState('error', data.error || 'Erro ao criar a chave SSH');
                }
            } catch (error) {
                console.error('Error:', error);
                sshKeySetState('error', 'Erro ao conectar com o servidor');
            }
        }

        async function recreateSshKey() {
            const ok = await UI.confirm(
                'A chave atual deixará de funcionar imediatamente. Deploys via Git que usam a chave antiga ' +
                'falharão até você cadastrar a nova chave pública no repositório remoto (GitHub, GitLab etc).',
                {
                    title: 'Recriar a chave SSH?',
                    variant: 'danger',
                    okLabel: 'Recriar'
                }
            );
            if (!ok) return;

            sshKeySetState('loading', 'Removendo chave atual...');
            try {
                const response = await HostingerConfig.fetch('api/ssh-key.php', {
                    method: 'DELETE',
                    body: JSON.stringify(sshKeyCtx)
                });
                const data = await response.json();

                if (!data.success) {
                    sshKeySetState('error', 'Não foi possível remover a chave atual: ' + (data.error || 'erro desconhecido'));
                    return;
                }

                await createSshKey();
            } catch (error) {
                console.error('Error:', error);
                sshKeySetState('error', 'Erro ao conectar com o servidor');
            }
        }

        function copySshKey(button) {
            const key = document.getElementById('sshKeyValue').textContent;
            navigator.clipboard.writeText(key).then(() => {
                const original = button.textContent;
                button.textContent = 'Copiado!';
                setTimeout(() => { button.textContent = original; }, 2000);
            }).catch(() => {
                UI.alert('Não foi possível copiar automaticamente. Selecione o texto e copie manualmente.', { variant: 'warning' });
            });
        }
    </script>
</body>

</html>