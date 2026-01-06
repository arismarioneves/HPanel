<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Hostinger Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="assets/config.js"></script>
</head>

<body>
    <div class="container">
        <header class="header">
            <a href="index.php" class="logo">
                <div class="logo-icon">H</div>
                <span class="logo-text">Hostinger Dashboard</span>
            </a>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary" title="Início">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </a>
                <a href="settings.php" class="btn btn-primary" title="Configurações">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </a>
            </div>
        </header>

        <div class="page-title">
            <h1>Configurações</h1>
            <p>Configure os cookies de autenticação para acessar a API da Hostinger</p>
        </div>

        <!-- Alert container -->
        <div id="alertContainer"></div>

        <!-- Status row: session + token side by side -->
        <div class="status-row">
            <div id="statusIndicator" class="status-indicator disconnected">
                <span class="status-dot"></span>
                <strong id="statusText">Verificando...</strong>
                <span id="sessionInfo" style="color: var(--text-muted); margin-left: auto; font-size: 0.85rem;"></span>
            </div>

            <div id="tokenStatus" class="token-status" style="display: none;">
                <div class="token-info">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span id="tokenStatusText"></span>
                </div>
                <a href="https://hpanel.hostinger.com" target="_blank" class="btn btn-secondary btn-sm" id="hpanelLink" style="display: none;">
                    hPanel ↗
                </a>
            </div>
        </div>

        <div class="settings-form">
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label class="form-label">Cookies de Autenticação</label>
                    <p class="form-hint">Cole aqui os cookies copiados do navegador. Você pode colar o comando cURL completo ou apenas a string de cookies.</p>
                    <textarea
                        id="cookiesInput"
                        class="form-textarea"
                        placeholder="Cole o comando cURL (bash) completo aqui...&#10;&#10;Exemplo:&#10;curl 'https://hpanel.hostinger.com/...' \&#10;  -H 'accept: ...' \&#10;  -b 'language=pt_BR; jwt=eyJ...; ...'"></textarea>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Google Analytics ID</label>
                    <p class="form-hint">ID do Google Analytics usado nas requisições.</p>
                    <input
                        type="text"
                        id="gaidInput"
                        class="form-input"
                        value="GA1.1.000000000.0000000000"
                        placeholder="GA1.1.000000000.0000000000">
                    <p class="form-hint" style="margin-top: 4px; font-size: 0.75rem;">Encontrado nos cookies como _ga</p>
                </div>
            </div>

            <!-- Buttons side by side -->
            <div class="buttons-row">
                <button type="button" class="btn btn-primary" id="saveBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salvar
                </button>

                <button type="button" class="btn btn-secondary" id="clearBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Limpar
                </button>
            </div>
        </div>
    </div>

    <style>
        .status-row {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .status-row .status-indicator {
            flex: 1;
            margin-bottom: 0;
        }

        .token-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            flex: 1;
        }

        .token-status.valid {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .token-status.warning {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .token-status.expired {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .token-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .token-status.valid .token-info svg {
            color: var(--success);
        }

        .token-status.warning .token-info svg {
            color: var(--warning);
        }

        .token-status.expired .token-info svg {
            color: var(--danger);
        }

        .form-row {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        .buttons-row {
            display: flex;
            gap: var(--spacing-sm);
        }

        .buttons-row .btn {
            flex: 1;
            justify-content: center;
            padding: 10px 16px;
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {

            .status-row,
            .form-row,
            .buttons-row {
                flex-direction: column;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            updateStatus();

            document.getElementById('saveBtn').addEventListener('click', saveConfig);
            document.getElementById('clearBtn').addEventListener('click', clearConfig);
        });

        async function saveConfig() {
            const btn = document.getElementById('saveBtn');
            const originalContent = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner" style="width: 18px; height: 18px; border-width: 2px;"></span> Salvando...';

            try {
                let cookies = document.getElementById('cookiesInput').value.trim();
                const gaid = document.getElementById('gaidInput').value.trim();

                if (!cookies) {
                    showAlert('error', 'Por favor, cole os cookies de autenticação.');
                    return;
                }

                // Save to server (returns hash)
                const result = await HostingerConfig.save(cookies, gaid);

                if (!result.success) {
                    showAlert('error', 'Erro ao salvar: ' + (result.error || 'Erro desconhecido'));
                    return;
                }

                // Test the connection by fetching websites
                btn.innerHTML = '<span class="spinner" style="width: 18px; height: 18px; border-width: 2px;"></span> Testando conexão...';

                try {
                    const testResponse = await HostingerConfig.fetch('api/websites.php');
                    const testData = await testResponse.json();

                    if (testData.success && testData.data && testData.data.length > 0) {
                        showAlert('success', `Conexão verificada! ${testData.data.length} servidor(es) encontrado(s).`);
                        document.getElementById('cookiesInput').value = ''; // Clear for security
                    } else {
                        showAlert('warning', 'Cookies salvos, mas a conexão falhou. Verifique se os cookies estão corretos e não expiraram.');
                    }
                } catch (testError) {
                    showAlert('warning', 'Cookies salvos, mas não foi possível verificar a conexão.');
                }

                updateStatus();
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Erro ao salvar configurações.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        function clearConfig() {
            if (confirm('Tem certeza que deseja limpar a sessão atual?')) {
                HostingerConfig.clear();
                showAlert('success', 'Sessão removida.');
                updateStatus();
            }
        }

        async function updateStatus() {
            const statusIndicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');
            const sessionInfo = document.getElementById('sessionInfo');
            const tokenStatus = document.getElementById('tokenStatus');
            const tokenStatusText = document.getElementById('tokenStatusText');
            const hpanelLink = document.getElementById('hpanelLink');

            const hash = HostingerConfig.getHash();

            if (!hash) {
                statusIndicator.className = 'status-indicator disconnected';
                statusText.textContent = 'Não configurado';
                sessionInfo.textContent = '';
                tokenStatus.style.display = 'none';
                return;
            }

            statusIndicator.className = 'status-indicator connected';
            statusText.textContent = 'Sessão ativa';
            sessionInfo.textContent = 'ID: ' + hash.substring(0, 8) + '...';

            // Check JWT status
            const jwtStatus = await HostingerConfig.getJwtStatus();
            if (jwtStatus) {
                tokenStatus.style.display = 'flex';

                if (jwtStatus.expired) {
                    tokenStatus.className = 'token-status expired';
                    tokenStatusText.innerHTML = '<strong style="color: var(--danger);">Token EXPIRADO</strong> — Atualize os cookies';
                    hpanelLink.style.display = 'flex';
                } else if (jwtStatus.warning) {
                    tokenStatus.className = 'token-status warning';
                    tokenStatusText.innerHTML = `Token expira em <strong style="color: var(--warning);">${jwtStatus.minutesLeft} min</strong> — Atualize em breve`;
                    hpanelLink.style.display = 'flex';
                } else {
                    tokenStatus.className = 'token-status valid';
                    tokenStatusText.innerHTML = `Token válido por <strong style="color: var(--success);">${jwtStatus.minutesLeft} min</strong>`;
                    hpanelLink.style.display = 'none';
                }
            } else {
                tokenStatus.style.display = 'none';
            }
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

            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    document.getElementById('alertContainer').innerHTML = '';
                }, 5000);
            }
        }
    </script>
</body>

</html>