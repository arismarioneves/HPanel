# 🚀 Hostinger Dashboard

Dashboard personalizado para gerenciar múltiplos websites hospedados na Hostinger de forma centralizada.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

## 📋 Visão Geral

O Hostinger Dashboard oferece uma interface simplificada para gerenciar todos os seus websites em um só lugar, sem precisar navegar pelo hPanel da Hostinger para cada operação.

### ✨ Funcionalidades

- **📊 Visão geral de servidores** - Lista todos os planos de hospedagem com estatísticas de uso
- **🔎 Busca global** - Encontra um site em todos os servidores a partir da página inicial
- **🌐 Gerenciamento de websites** - Visualize todos os domínios, subdomínios e addons
- **📁 Acesso rápido ao Gerenciador de Arquivos** - Link direto para cada domínio
- **🗄️ Bancos de dados** - Lista bancos de dados com acesso ao phpMyAdmin
- **🐘 Versão PHP** - Altere a versão PHP de qualquer domínio
- **🔑 Chave SSH (Git)** - Veja, crie ou recrie a chave SSH de deploy de cada servidor
- **📈 Estatísticas de uso** - Disco, inodes, RAM, CPU (com cache de 1h para reduzir chamadas)
- **🔄 Renovação do token** - Renove o JWT em 1 clique e, opcionalmente, mantenha-o renovado automaticamente via cron

---

## 🔐 Autenticação

O dashboard utiliza o **token JWT** do hPanel da Hostinger para autenticação. O token é armazenado de forma segura no servidor em arquivos de sessão.

### Como funciona

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Seu Navegador │  >  │  hPanel Hostinger│  >  │    Dashboard    │
│   (Logado)      │     │  (Token JWT)     │     │  (Servidor)     │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

### Formato de Sessão

As sessões são armazenadas em arquivos JSON na pasta `cookies/`:

```json
{
    "token": "eyJ0eXAiOiJKV1Qi...",
    "gaid": "GA1.1.000000000.0000000000",
    "autoRenew": false,
    "update": "2026-01-07 15:00:00"
}
```

### Sobre o Token JWT

| Campo | Descrição |
|-------|-----------|
| `token` | Token JWT de autenticação (~1h de validade) |
| `gaid` | ID do Google Analytics (opcional) |
| `autoRenew` | Se `true`, o cron renova o token automaticamente |
| `update` | Data/hora da última atualização |

> ⚠️ O token JWT expira a cada **~1 hora**. Você pode renová-lo em 1 clique ou ativar a
> **renovação automática** (veja abaixo) para não precisar colar o token de novo.

---

## 📦 Instalação

### Requisitos

- PHP 7.4 ou superior
- Extensão cURL habilitada
- Servidor web (Apache, Nginx, XAMPP, etc.)

### Passos

1. **Clone ou copie os arquivos** para seu servidor local:
```bash
git clone https://github.com/seu-usuario/Hostinger-Dashboard.git
cd Hostinger-Dashboard
```

2. **Configure o caminho base** (apenas se o painel não estiver na raiz do domínio):
```bash
cp config.exemplo.php config.php
# edite 'base' => '/hpanel/' para a subpasta onde o painel está instalado
```
O `config.php` é local e não é versionado. Sem ele, a aplicação assume a raiz do domínio (`/`).

3. **Crie a pasta de cookies** (se não existir):
```bash
mkdir cookies
```

4. **Acesse o dashboard** no navegador:
```
http://localhost/Hostinger-Dashboard/
```

5. **Configure o token JWT** na página de Configurações.

---

## ⚙️ Configuração do Token JWT

### Passo a passo

1. Acesse [hpanel.hostinger.com](https://hpanel.hostinger.com) e faça login

2. Abra o DevTools (`F12`)

3. Vá para **Application** → **Cookies** → **hpanel.hostinger.com**

4. Encontre o cookie chamado `jwt` e copie seu valor

5. Cole na página de Configurações do Dashboard e salve

### Dica

O token começa com `eyJ` e é uma string longa. Exemplo:

```
eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3Mzc...
```

---

## 🔑 Chave SSH (Git)

Na página de cada servidor há a seção **Chave SSH (Git)**, que gerencia a chave pública usada nos deploys via Git da conta:

- **Ver** - Exibe a chave pública atual (com botão de copiar)
- **Criar** - Gera o par de chaves quando a conta ainda não tem
- **Recriar** - Remove a chave atual e gera uma nova (a antiga deixa de funcionar)

> ⚠️ Nem todo servidor usa SSH para deploy — alguns usam GitHub App. Ao **criar** uma chave SSH, o próprio hPanel volta a exibir a interface de deploy via SSH para os sites daquela conta.

---

## 🔄 Renovação do Token

O JWT do hPanel usa **sessão deslizante**: enquanto ele ainda não expirou de vez, o
endpoint `/auth/refresh` devolve um token novo válido por mais ~1h — usando apenas o
próprio JWT (nenhum cookie extra é necessário).

### Renovar em 1 clique

Quando faltarem **≤ 10 min** para o token vencer (ou se ele já venceu), aparece um
**banner** no canto inferior direito com o botão **Renovar agora**. A página de
Configurações também tem o botão, junto ao status do token.

### Renovação automática (opcional, via cron)

Na página de Configurações, ative **Renovação automática**. Com isso, um cron mantém o
token da sua sessão sempre fresco, sem precisar colar o JWT de novo.

1. **Defina uma chave secreta** no `config.php`:
```php
'cron_secret' => 'uma-chave-forte-aleatoria',
```

2. **Agende um gatilho** que chame esta URL a cada ~30 minutos:
```
https://SEU-HOST/hpanel/cron/renew-tokens?key=uma-chave-forte-aleatoria
```
(no hPanel: **Avançado → Cron Jobs**, ou qualquer serviço de "ping"/uptime que faça GET periódico)

3. **Ative o toggle** em Configurações para cada sessão que deve ser renovada.

> ⚠️ **Segurança:** com a renovação automática ligada, o servidor mantém sua sessão
> Hostinger viva e guarda o token em `cookies/`. Use apenas em host protegido. Se o token
> ficar sem renovar por muito tempo e expirar de vez, o refresh falha (401) e é preciso
> recolar o JWT via login.

---

## 📁 Estrutura do Projeto

```
Hostinger-Dashboard/
├── index.php           # Página principal (lista servidores + busca global)
├── server.php          # Detalhes do servidor, websites e chave SSH
├── settings.php        # Configurações (token JWT + renovação)
├── bootstrap.php       # Resolve o caminho base (APP_BASE) via config.php
├── config.exemplo.php  # Modelo de configuração local (copiar para config.php)
├── cookies/            # Arquivos de sessão e cache (JSON)
│   └── .htaccess       # Bloqueia acesso web aos arquivos de sessão
├── cron/
│   └── renew-tokens.php # Cron de renovação automática (acionável por URL)
├── assets/
│   ├── style.css        # Estilos do dashboard
│   ├── config.js        # Gerenciamento de sessão
│   ├── ui.js            # Diálogos compartilhados (UI.alert / UI.confirm)
│   └── token-status.js  # Banner de expiração + renovar em 1 clique
└── api/
    ├── config.php          # Funções de configuração/sessão
    ├── save-config.php     # Salvar token
    ├── jwt-status.php      # Status do token (expiração)
    ├── renew-token.php     # Renovar o token da sessão (1 clique)
    ├── set-auto-renew.php  # Ligar/desligar renovação automática
    ├── HostingerClient.php # Cliente da API Hostinger
    ├── websites.php        # Endpoint: listar servidores
    ├── server.php          # Endpoint: detalhes do servidor
    ├── server-usage.php    # Endpoint: uso do servidor (com cache)
    ├── usage-cache.php     # Cache de uso (1h)
    ├── sites-cache.php     # Cache de sites para a busca global (1h)
    ├── databases.php       # Endpoint: listar bancos
    ├── phpmyadmin.php      # Endpoint: link phpMyAdmin
    ├── file-browser.php    # Endpoint: link gerenciador de arquivos
    ├── php-version.php     # Endpoint: versão PHP
    ├── set-php-version.php # Endpoint: alterar versão PHP
    └── ssh-key.php         # Endpoint: chave SSH Git (ver/criar/remover)
```

---

## 🛡️ Segurança

- Token e sessão são armazenados no servidor (pasta `cookies/`)
- A pasta `cookies/` tem um `.htaccess` que bloqueia acesso web aos arquivos de sessão
- Hash único identifica cada sessão (salvo no localStorage do navegador)
- Dados não são enviados para nenhum servidor externo
- O cron de renovação é protegido por `cron_secret` (definido no `config.php`)
- Com a renovação automática ligada, o servidor mantém a sessão viva — use em host protegido
- Recomenda-se usar em ambiente local ou protegido

---

## 📝 Notas Técnicas

### APIs Utilizadas

O dashboard usa APIs internas do hPanel (não oficiais):

- `/api/wh-api/api/hapi/v1/orders/websites` - Lista servidores (paginado)
- `/api/rest-hosting/v3/account` - Detalhes e uso da conta
- `/api/wh-api/api/hapi/v1/accounts/{username}/databases` - Lista bancos
- `/api/wh-api/api/hapi/v1/accounts/{username}/vhosts/{domain}/php/version` - Versão PHP
- `/api/wh-api/api/hapi/v1/accounts/{username}/git-key` - Chave SSH de Git (GET/POST/DELETE)
- `/api/auth/api/external/v1/auth/refresh` - Renovação do JWT (sessão deslizante)

### Limitações

- As APIs podem mudar sem aviso (são internas)
- Tokens JWT expiram após ~1 hora (mitigado pela renovação manual/automática)
- Se o token expirar de vez sem renovar, é preciso recolar o JWT via login
- O DELETE da chave SSH não é documentado pelo hPanel; se a API rejeitar, a mensagem de erro é exibida e a chave permanece intacta

---

## 📄 Licença

MIT License - Veja [LICENSE](LICENSE) para detalhes.

---

## 🤝 Contribuição

Contribuições são bem-vindas! Sinta-se à vontade para enviar um Pull Request.
