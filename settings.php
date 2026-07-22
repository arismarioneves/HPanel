<?php require __DIR__ . '/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= APP_BASE ?>">
    <title>Configurações - Hostinger Dashboard</title>
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

        <div class="page-title">
            <h1>Configurações</h1>
            <p>Configure o token JWT para acessar as funcionalidades do dashboard</p>
        </div>

        <!-- Alert container -->
        <div id="alertContainer"></div>

        <!-- Status Section -->
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
                <div style="display: flex; gap: var(--spacing-xs); align-items: center;">
                    <button type="button" class="btn btn-primary btn-sm" id="renewBtn" style="display: none;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                        Renovar agora
                    </button>
                    <a href="https://hpanel.hostinger.com" target="_blank" class="btn btn-secondary btn-sm" id="hpanelLink" style="display: none;">
                        hPanel ↗
                    </a>
                </div>
            </div>
        </div>

        <div class="settings-form">
            <!-- JWT Token Section -->
            <div class="form-section jwt-section">
                <div class="section-header">
                    <h4 class="section-label">🔑 Token JWT</h4>
                    <span class="token-note">Expira a cada ~1 hora</span>
                </div>

                <div class="instructions-box">
                    <p><strong>Como obter:</strong></p>
                    <ol>
                        <li>Acesse o <a href="https://hpanel.hostinger.com" target="_blank">hPanel da Hostinger</a></li>
                        <li>Abra DevTools (<kbd>F12</kbd>)</li>
                        <li>Vá em <strong>Application</strong> → <strong>Cookies</strong> → <strong>hpanel.hostinger.com</strong></li>
                        <li>Copie o valor do cookie <code>jwt</code></li>
                    </ol>
                </div>

                <input
                    type="text"
                    id="jwtInput"
                    class="form-input token-input"
                    placeholder="Cole o token JWT aqui (começa com eyJ...)">
            </div>

            <!-- Buttons -->
            <div class="buttons-row">
                <button type="button" class="btn btn-primary" id="saveBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salvar Token
                </button>

                <button type="button" class="btn btn-secondary" id="clearBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Limpar Sessão
                </button>
            </div>

            <p class="autorenew-note">
                🔄 O token é renovado automaticamente durante a navegação (sessão deslizante),
                enquanto não expirar de vez. Se expirar, cole o JWT novamente aqui.
            </p>
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

        .buttons-row {
            display: flex;
            gap: var(--spacing-sm);
        }

        .buttons-row .btn {
            flex: 1;
            justify-content: center;
            padding: 12px 20px;
        }

        /* Form Sections */
        .form-section {
            /* background: var(--glass-bg); */
            /* border: 1px solid var(--glass-border); */
            /* border-radius: var(--radius-md); */
            /* padding: var(--spacing-lg); */
            margin-bottom: var(--spacing-md);
        }

        .jwt-section {
            /* border-color: var(--primary); */
            /* background: rgba(103, 61, 230, 0.05); */
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--spacing-md);
        }

        .section-label {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .token-note {
            font-size: 0.8rem;
            color: var(--warning);
            background: rgba(245, 158, 11, 0.1);
            padding: 4px 10px;
            border-radius: var(--radius-sm);
        }

        .instructions-box {
            background: rgba(0, 0, 0, 0.2);
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .instructions-box p {
            margin: 0 0 var(--spacing-sm) 0;
        }

        .instructions-box ol {
            margin: 0;
            padding-left: 1.5rem;
        }

        .instructions-box li {
            margin-bottom: 4px;
        }

        .instructions-box code {
            background: rgba(103, 61, 230, 0.2);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            color: var(--primary-light);
        }

        .instructions-box kbd {
            background: var(--bg-tertiary);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            border: 1px solid var(--glass-border);
        }

        .instructions-box a {
            color: var(--primary-light);
        }

        .token-input {
            font-family: monospace;
            font-size: 0.9rem;
            padding: 14px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .autorenew-note {
            margin-top: var(--spacing-md);
            padding: var(--spacing-sm) var(--spacing-md);
            background: rgba(0, 0, 0, 0.2);
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        @media (max-width: 768px) {

            .status-row,
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
            document.getElementById('renewBtn').addEventListener('click', renewNow);
        });

        async function renewNow() {
            const btn = document.getElementById('renewBtn');
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.textContent = 'Renovando...';

            try {
                const res = await HostingerConfig.fetch('api/renew-token.php', { method: 'POST' });
                const data = await res.json();

                if (data.success) {
                    showAlert('success', 'Token renovado com sucesso.');
                } else {
                    showAlert('error', data.error || 'Não foi possível renovar o token.');
                }
            } catch (e) {
                console.error(e);
                showAlert('error', 'Erro ao renovar o token.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = original;
                updateStatus();
            }
        }


        async function saveConfig() {
            const btn = document.getElementById('saveBtn');
            const originalContent = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner" style="width: 18px; height: 18px; border-width: 2px;"></span> Salvando...';

            try {
                const token = document.getElementById('jwtInput').value.trim();

                if (!token) {
                    showAlert('error', 'Por favor, cole o token JWT.');
                    return;
                }

                if (!token.startsWith('eyJ')) {
                    showAlert('error', 'Token JWT inválido. O token deve começar com "eyJ".');
                    return;
                }

                // Save to server
                const result = await HostingerConfig.save(token);

                if (!result.success) {
                    showAlert('error', 'Erro ao salvar: ' + (result.error || 'Erro desconhecido'));
                    return;
                }

                // Test the connection
                btn.innerHTML = '<span class="spinner" style="width: 18px; height: 18px; border-width: 2px;"></span> Testando...';

                try {
                    const testResponse = await HostingerConfig.fetch('api/websites.php');
                    const testData = await testResponse.json();

                    if (testData.success && testData.data && testData.data.length > 0) {
                        showAlert('success', `Conectado! ${testData.data.length} servidor(es) encontrado(s).`);
                        document.getElementById('jwtInput').value = ''; // Clear for security
                    } else {
                        showAlert('warning', 'Token salvo, mas a conexão falhou. Verifique se o token está correto.');
                    }
                } catch (testError) {
                    showAlert('warning', 'Token salvo, mas não foi possível verificar a conexão.');
                }

                updateStatus();
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Erro ao salvar token.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        async function clearConfig() {
            const ok = await UI.confirm('Tem certeza que deseja limpar a sessão atual?', {
                title: 'Limpar sessão',
                variant: 'danger',
                okLabel: 'Limpar'
            });
            if (ok) {
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

            const renewBtn = document.getElementById('renewBtn');

            const hash = HostingerConfig.getHash();

            if (!hash) {
                statusIndicator.className = 'status-indicator disconnected';
                statusText.textContent = 'Não configurado';
                sessionInfo.textContent = '';
                tokenStatus.style.display = 'none';
                renewBtn.style.display = 'none';
                return;
            }

            statusIndicator.className = 'status-indicator connected';
            statusText.textContent = 'Sessão ativa';
            sessionInfo.textContent = 'ID: ' + hash.substring(0, 8) + '...';

            // Check JWT status
            const jwtData = await HostingerConfig.getJwtStatus();

            if (jwtData && jwtData.success && jwtData.status) {
                const jwt = jwtData.status;
                tokenStatus.style.display = 'flex';
                renewBtn.style.display = 'inline-flex'; // renovar disponível sempre que há token

                if (jwt.expired) {
                    tokenStatus.className = 'token-status expired';
                    tokenStatusText.innerHTML = '<strong style="color: var(--danger);">Token EXPIRADO</strong>';
                    hpanelLink.style.display = 'flex';
                } else if (jwt.warning || jwt.minutesLeft < 15) {
                    tokenStatus.className = 'token-status warning';
                    tokenStatusText.innerHTML = `Expira em <strong style="color: var(--warning);">${jwt.minutesLeft} min</strong>`;
                    hpanelLink.style.display = 'flex';
                } else {
                    tokenStatus.className = 'token-status valid';
                    tokenStatusText.innerHTML = `Válido por <strong style="color: var(--success);">${jwt.minutesLeft} min</strong>`;
                    hpanelLink.style.display = 'none';
                }
            } else {
                tokenStatus.style.display = 'none';
                renewBtn.style.display = 'none';
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
