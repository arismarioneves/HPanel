<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostinger Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="assets/config.js"></script>
</head>

<body>
    <div class="container">
        <header class="header">
            <a href="/hpanel/" class="logo">
                <div class="logo-icon">H</div>
                <span class="logo-text">Hostinger Dashboard</span>
            </a>
            <div class="header-actions">
                <a href="/hpanel/" class="btn btn-secondary" title="Início">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </a>
                <a href="settings" class="btn btn-primary" title="Configurações">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </a>
            </div>
        </header>

        <div class="page-title">
            <h1>Meus Servidores</h1>
            <p>Gerencie todos os seus recursos Hostinger em um só lugar</p>
        </div>

        <!-- Alert container -->
        <div id="alertContainer"></div>

        <!-- Global Search Bar -->
        <div class="global-search-container">
            <div class="global-search-box">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" id="globalSearchInput" placeholder="Buscar site em todos os servidores..." autocomplete="off">
                <span id="searchStatus" class="search-status"></span>
            </div>
            <div id="searchResults" class="search-results"></div>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-value" id="totalServers">-</span>
                <span class="stat-label">Servidores</span>
            </div>
            <div class="stat-item">
                <span class="stat-value" id="totalWebsites">-</span>
                <span class="stat-label">Websites</span>
            </div>
        </div>

        <div id="cardsGrid" class="cards-grid">
            <div class="loading" style="grid-column: 1 / -1; display: flex; justify-content: center; padding: 60px;">
                <div class="spinner"></div>
            </div>
        </div>
    </div>

    <script>
        // Global search state
        let allSitesCache = null;
        let searchTimeout = null;

        document.addEventListener('DOMContentLoaded', async () => {
            // Check if configured
            if (!HostingerConfig.isConfigured()) {
                showAlert('warning', 'Configure os cookies de autenticação para começar. <a href="settings" style="color: inherit; font-weight: 600;">Ir para Configurações</a>');
                document.getElementById('cardsGrid').innerHTML = '';
                return;
            }

            // Initialize global search
            initGlobalSearch();

            try {
                const response = await HostingerConfig.fetch('api/websites.php');
                const data = await response.json();

                if (!data.success) {
                    showAlert('error', 'Erro ao carregar dados. Verifique se os cookies estão atualizados. <a href="settings" style="color: inherit; font-weight: 600;">Atualizar Cookies</a>');
                    document.getElementById('cardsGrid').innerHTML = '';
                    return;
                }

                // Render cards immediately without usage data
                renderServers(data.data);

                // Load usage data in background for each server
                loadUsageInBackground(data.data);

                // Load sites cache in background for search
                loadSitesCache();
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Erro de conexão. <a href="settings" style="color: inherit; font-weight: 600;">Verificar Configurações</a>');
                document.getElementById('cardsGrid').innerHTML = '';
            }
        });

        // Global Search Functions
        function initGlobalSearch() {
            const searchInput = document.getElementById('globalSearchInput');
            const searchResults = document.getElementById('searchResults');

            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    searchResults.innerHTML = '';
                    searchResults.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 200);
            });

            // Close results when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.global-search-container')) {
                    searchResults.style.display = 'none';
                }
            });

            // Focus back shows results
            searchInput.addEventListener('focus', () => {
                if (searchInput.value.trim().length >= 2 && searchResults.innerHTML) {
                    searchResults.style.display = 'block';
                }
            });
        }

        async function loadSitesCache() {
            const statusEl = document.getElementById('searchStatus');
            statusEl.innerHTML = '<span class="loading-dots">Carregando sites...</span>';

            try {
                const response = await HostingerConfig.fetch('api/sites-cache.php');
                const data = await response.json();

                if (data.success) {
                    allSitesCache = data.sites;
                    const cacheInfo = data.fromCache ? `<span title="Cache de ${data.cacheAge}">&#9889;</span> ` : '';
                    statusEl.innerHTML = `${cacheInfo}${data.total} sites`;
                } else {
                    statusEl.innerHTML = '<span class="error-text">Erro ao carregar</span>';
                }
            } catch (error) {
                console.error('Error loading sites cache:', error);
                statusEl.innerHTML = '<span class="error-text">Erro</span>';
            }
        }

        function performSearch(query) {
            const searchResults = document.getElementById('searchResults');
            
            if (!allSitesCache) {
                searchResults.innerHTML = '<div class="search-result-item loading">Carregando dados...</div>';
                searchResults.style.display = 'block';
                return;
            }

            const queryLower = query.toLowerCase();
            const results = allSitesCache.filter(site => 
                site.domain.toLowerCase().includes(queryLower)
            ).slice(0, 10); // Limit to 10 results

            if (results.length === 0) {
                searchResults.innerHTML = '<div class="search-result-item no-results">Nenhum site encontrado</div>';
                searchResults.style.display = 'block';
                return;
            }

            searchResults.innerHTML = results.map(site => {
                const typeIcon = site.type === 'wordpress' ? '<span class="site-type-icon wp">WP</span>' : '';
                const vhostBadge = getVhostBadge(site.vhostType);
                const hostingerUrl = `https://hpanel.hostinger.com/websites/${site.domain}`;
                const siteUrl = `https://${site.domain}`;
                
                return `
                    <div class="search-result-item">
                        <div class="search-result-info">
                            <div class="search-result-domain">
                                ${typeIcon}
                                <span class="domain-text">${escapeHtml(site.domain)}</span>
                                ${vhostBadge}
                            </div>
                            <div class="search-result-server">
                                <span class="server-name">${escapeHtml(site.serverTitle)}</span>
                                <span class="plan-badge">${escapeHtml(site.planName)}</span>
                            </div>
                        </div>
                        <div class="search-result-actions">
                            <a href="server?orderId=${site.orderId}" class="action-btn" title="Abrir servidor no painel">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                    <line x1="8" y1="21" x2="16" y2="21"></line>
                                    <line x1="12" y1="17" x2="12" y2="21"></line>
                                </svg>
                            </a>
                            <a href="${hostingerUrl}" target="_blank" class="action-btn hostinger-btn" title="Abrir na Hostinger">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15 3 21 3 21 9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                            </a>
                            <a href="${siteUrl}" target="_blank" class="action-btn site-btn" title="Visitar site">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="2" y1="12" x2="22" y2="12"></line>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                `;
            }).join('');

            searchResults.style.display = 'block';
        }

        function getVhostBadge(vhostType) {
            switch (vhostType) {
                case 'main':
                    return '<span class="vhost-badge main">Principal</span>';
                case 'addon':
                    return '<span class="vhost-badge addon">Addon</span>';
                case 'subdomain':
                    return '<span class="vhost-badge subdomain">Subdomínio</span>';
                default:
                    return '';
            }
        }

        async function loadUsageInBackground(resources) {
            for (const resource of resources) {
                // Get main domain and username
                const mainSite = (resource.websites || []).find(s => s.vhostType === 'main') || resource.websites?.[0];
                if (!mainSite) continue;

                try {
                    const url = `api/server-usage.php?orderId=${resource.orderId}&username=${encodeURIComponent(mainSite.username)}&domain=${encodeURIComponent(mainSite.domain)}`;
                    const response = await HostingerConfig.fetch(url);
                    const data = await response.json();

                    if (data.success && data.usage) {
                        updateServerUsageBar(resource.orderId, data.usage, data.fromCache, data.cacheAge);
                    }
                } catch (error) {
                    console.error(`Error loading usage for ${resource.orderId}:`, error);
                }
            }
        }

        function updateServerUsageBar(orderId, usage, fromCache = false, cacheAge = null) {
            const usageInfo = getHighestUsageFromData(usage);
            if (!usageInfo) return;

            const card = document.querySelector(`a[href="server.php?orderId=${orderId}"]`);
            if (!card) return;

            // Find or create usage container - add at the END of card
            let usageContainer = card.querySelector('.server-usage');
            if (!usageContainer) {
                usageContainer = document.createElement('div');
                usageContainer.className = 'server-usage';
                card.appendChild(usageContainer);
            }

            const cacheIndicator = fromCache ? `<span class="cache-indicator" title="Dados em cache (${cacheAge})">⚡</span>` : '';

            usageContainer.innerHTML = `
                <div class="usage-header">
                    <span class="usage-label">${usageInfo.icon} ${usageInfo.label} ${cacheIndicator}</span>
                    <span class="usage-percent ${usageInfo.class}">${usageInfo.percent}%</span>
                </div>
                <div class="usage-bar">
                    <div class="usage-bar-fill ${usageInfo.class}" style="width: ${usageInfo.percent}%"></div>
                </div>
            `;
        }

        function getHighestUsageFromData(usage) {
            if (!usage) return null;

            const usageTypes = [{
                    key: 'storage',
                    label: 'Disco',
                    icon: '💾'
                },
                {
                    key: 'inodes',
                    label: 'Inodes',
                    icon: '📁'
                },
                {
                    key: 'databases',
                    label: 'BD',
                    icon: '🗄️'
                },
                {
                    key: 'subdomains',
                    label: 'Subdomínios',
                    icon: '🌐'
                },
                {
                    key: 'ftp_accounts',
                    label: 'FTP',
                    icon: '📂'
                }
            ];

            let highest = null;
            let highestPercent = -1;

            for (const type of usageTypes) {
                const data = usage[type.key];
                if (data && data.limit && data.limit > 0) {
                    // Use 1 decimal place like server.php does
                    const percent = Math.min(100, (data.value / data.limit) * 100);
                    const percentRounded = Math.round(percent * 10) / 10; // 1 decimal
                    if (percent > highestPercent) {
                        highestPercent = percent;
                        highest = {
                            ...type,
                            percent: percentRounded,
                            class: percent >= 90 ? 'danger' : percent >= 70 ? 'warning' : 'normal'
                        };
                    }
                }
            }

            return highest;
        }

        function showAlert(type, message) {
            const icons = {
                warning: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
                error: '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
                success: '<polyline points="20 6 9 17 4 12"></polyline>'
            };

            document.getElementById('alertContainer').innerHTML = `
                <div class="alert alert-${type}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        ${icons[type]}
                    </svg>
                    ${message}
                </div>
            `;
        }

        function renderServers(resources) {
            const grid = document.getElementById('cardsGrid');

            if (!resources || resources.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h3>Nenhum servidor encontrado</h3>
                        <p>Não há servidores associados à sua conta.</p>
                    </div>
                `;
                return;
            }

            // Update stats
            let totalWebsites = 0;
            resources.forEach(r => totalWebsites += (r.websites || []).length);
            document.getElementById('totalServers').textContent = resources.length;
            document.getElementById('totalWebsites').textContent = totalWebsites;

            // Render cards (usage will be loaded in background)
            grid.innerHTML = resources.map(resource => {
                const websiteCount = (resource.websites || []).length;
                const planName = resource.planDisplayableName || resource.planName || '';
                const planClass = getPlanClass(planName);

                return `
                    <a href="server.php?orderId=${resource.orderId}" class="server-card">
                        <div class="server-card-header">
                            <div>
                                <h3 class="server-title" data-tooltip="${escapeHtml(resource.title || 'Servidor')}"><span class="server-title-text">${escapeHtml(resource.title || 'Servidor')}</span></h3>
                                <span class="server-plan ${planClass}">${escapeHtml(planName)}</span>
                            </div>
                        </div>
                        <div class="server-stats">
                            <div class="server-stat">
                                <span class="server-stat-value">${websiteCount}</span>
                                <span class="server-stat-label">Websites</span>
                            </div>
                            <div class="server-stat">
                                <span class="server-stat-value">${escapeHtml(resource.server?.hostname || 'N/A')}</span>
                                <span class="server-stat-label">Servidor</span>
                            </div>
                        </div>
                        <div class="server-datacenter">
                            <span class="datacenter-flag">🌎</span>
                            ${escapeHtml(resource.datacenter?.title || 'N/A')}
                        </div>
                    </a>
                `;
            }).join('');
        }

        function getPlanClass(planName) {
            const name = (planName || '').toLowerCase();
            if (name.includes('enterprise')) return 'plan-enterprise';
            if (name.includes('professional')) return 'plan-professional';
            if (name.includes('startup')) return 'plan-startup';
            if (name.includes('business')) return 'plan-business';
            if (name.includes('premium')) return 'plan-premium';
            return '';
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>

</html>