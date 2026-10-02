# 🚀 Hostinger Dashboard

Dashboard personalizado para gerenciar múltiplos websites hospedados na Hostinger de forma centralizada.

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)
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
- **🔄 Renovação do token** - Renove o JWT em 1 clique; o token também é renovado automaticamente durante a navegação (sessão deslizante, sem cron)

---

## 🔐 Autenticação

O dashboard usa o **token JWT** da sua própria sessão no hPanel. Você cola o token na página de **Configurações** e ele passa a ficar só no servidor — o navegador nunca mais o vê.

- O token é **cifrado com AES-256-GCM** e gravado em `storage/sessions/`.
- A chave de cifragem é derivada do cookie `hp_sid` (HttpOnly) do seu navegador: **sem o seu navegador, o arquivo é ilegível**, mesmo para quem tiver acesso à pasta.
- **Sair** apaga o token do servidor de verdade (não apenas do navegador).
- Sessões paradas por **7 dias** são removidas automaticamente.

> ⚠️ O token JWT expira a cada **~1 hora**, mas é **renovado automaticamente pelo servidor** (veja abaixo). Só é preciso colá-lo de novo se ele expirar de vez.

---

## 📦 Instalação

### Requisitos

- PHP 8.1 ou superior, com as extensões `curl` e `openssl`
- Servidor web (Apache, Nginx, Laragon, XAMPP, etc.)

### Passos

1. **Clone os arquivos** no servidor:
```bash
git clone https://github.com/arismarioneves/HPanel.git
cd HPanel
```

2. **Configuração**: o `config.php` é criado sozinho no primeiro acesso (a pasta precisa de permissão de escrita). Se preferir, crie manualmente:
```bash
cp config.exemplo.php config.php
```
   - Ajuste `base` (ex.: `'/hpanel/'`) se o painel estiver em uma subpasta.
   - Recomendado: aponte `storage_dir` para uma pasta **fora** da pasta pública.

3. **Acesse o dashboard** no navegador e **configure o token JWT** na página de Configurações.

> **Atualização:** Ao atualizar de uma versão antiga, apague a pasta cookies/ (sessões antigas em texto puro).

### Nginx

No Apache o `.htaccess` já bloqueia tudo que não é público. No Nginx, use o bloco equivalente (instalação na **raiz** do domínio, `base => '/'`):

```nginx
location ~ ^/(storage|lib|partials|tests|tools|vendor|docs|cookies)(/|$) { deny all; }
location ~ ^/(config(\.exemplo)?\.php|composer\.(json|lock)|package\.json|phpunit\.xml)$ { deny all; }
location ~ /\. { deny all; }
location / { try_files $uri $uri/ $uri.php?$query_string; }
```

Em **subpasta**, prefixe as regras com o mesmo caminho de `base`. Exemplo para `'base' => '/hpanel/'`:

```nginx
location ~ ^/hpanel/(storage|lib|partials|tests|tools|vendor|docs|cookies)(/|$) { deny all; }
location ~ ^/hpanel/(config(\.exemplo)?\.php|composer\.(json|lock)|package\.json|phpunit\.xml)$ { deny all; }
location ~ /\. { deny all; }
location /hpanel/ { try_files $uri $uri/ $uri.php?$query_string; }
```

### Desenvolvimento

```bash
composer install
vendor/bin/phpunit
node --test tests/js/        # requer Node 18+
php -S 127.0.0.1:8099 tools/dev-router.php
```

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

O JWT do hPanel usa **sessão deslizante**: enquanto ainda não expirou de vez, o endpoint `/auth/refresh` devolve um token novo válido por mais ~1h, usando apenas o próprio JWT.

- **Automática**: em qualquer chamada à API do dashboard, se faltam **≤ 15 min** para o token expirar, o **servidor** o renova antes de responder — sem cron e sem nada rodando no navegador.
- **Renovar agora**: botão na página de Configurações, útil depois de muito tempo sem usar.
- **Sair**: também em Configurações; apaga o token do servidor.

> ⚠️ Se o token **expirar de vez**, o refresh falha e é preciso **colar o JWT novamente** em Configurações.

---

## 📁 Estrutura do Projeto

```
HPanel/
├── index.php            # Dashboard (servidores, KPIs, busca global)
├── server.php           # Servidor em abas (sites, bancos, uso, chave SSH)
├── settings.php         # Configurações (token, renovar, sair)
├── config.exemplo.php   # Modelo de configuração local
├── .htaccess            # Bloqueia tudo que não é público
├── lib/                 # Núcleo: sessão cifrada, HTTP, cliente Hostinger, validação
├── api/                 # Endpoints JSON (envelope único, CSRF, sem CORS)
├── partials/            # Layout, cabeçalho e páginas renderizadas no servidor
├── assets/
│   ├── css/             # Estilos (tema claro/escuro)
│   ├── js/              # Módulos ES (sem scripts inline)
│   ├── fonts/           # Fonte Inter local
│   └── icons.svg        # Sprite de ícones
├── storage/             # Sessões cifradas e rate limit (fora da web)
├── tools/               # dev-router.php para o servidor embutido do PHP
└── tests/               # PHPUnit + testes JS (node --test)
```

---

## 🛡️ Segurança

- Cookie de sessão `hp_sid` **HttpOnly** e **SameSite=Strict**
- Token **cifrado** (AES-256-GCM) no servidor, com chave derivada do cookie
- **CSRF**: checagem de origem + header obrigatório nas chamadas à API
- **CSP restrita**, sem scripts ou estilos inline; fonte servida localmente
- **Sem CORS**: a API só atende o próprio painel
- **Rate limit** na conexão (colar token)
- Checagem de **posse** do servidor/site antes de qualquer operação
- **GC de sessões**: sessões paradas por 7 dias são apagadas; "Sair" remove o token
- `storage/`, `lib/`, `config.php` e afins bloqueados na web (`.htaccess` / Nginx)
- Nenhum dado é enviado para serviços externos além da própria Hostinger


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

## ⚖️ Legalidade e Uso Responsável

Este projeto **não burla nem contorna** nenhum controle da Hostinger. Ele apenas reaproveita o **token JWT da sua própria sessão** — o mesmo que o seu navegador já usa ao acessar o hPanel — para consultar e gerenciar **exclusivamente os recursos da sua própria conta**, aos quais você já tem acesso legítimo.

- Não há quebra de autenticação, escalada de privilégios nem acesso a contas ou dados de terceiros.
- As chamadas usam as mesmas APIs e o mesmo token que o próprio hPanel usa no seu navegador.
- O token permanece apenas no seu servidor; nada é enviado para serviços externos.

Na prática, é equivalente a automatizar ações que você mesmo faria manualmente no hPanel, dentro do seu próprio ambiente. Ainda assim, por utilizar **APIs internas não oficiais** (que podem mudar sem aviso), use por sua conta e risco e em conformidade com os Termos de Serviço da Hostinger.

---

## 📄 Licença

MIT License - Veja [LICENSE](LICENSE) para detalhes.

---

## 🤝 Contribuição

Contribuições são bem-vindas! Sinta-se à vontade para enviar um Pull Request.
