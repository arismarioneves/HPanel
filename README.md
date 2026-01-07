# 🚀 Hostinger Dashboard

Dashboard personalizado para gerenciar múltiplos websites hospedados na Hostinger de forma centralizada.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

## 📋 Visão Geral

O Hostinger Dashboard oferece uma interface simplificada para gerenciar todos os seus websites em um só lugar, sem precisar navegar pelo hPanel da Hostinger para cada operação.

### ✨ Funcionalidades

- **📊 Visão geral de servidores** - Lista todos os planos de hospedagem com estatísticas de uso
- **🌐 Gerenciamento de websites** - Visualize todos os domínios, subdomínios e addons
- **📁 Acesso rápido ao Gerenciador de Arquivos** - Link direto para cada domínio
- **🗄️ Bancos de dados** - Lista bancos de dados com acesso ao phpMyAdmin
- **🐘 Versão PHP** - Altere a versão PHP de qualquer domínio
- **📈 Estatísticas de uso** - Disco, inodes, RAM, CPU em tempo real

---

## 🔐 Autenticação

O dashboard utiliza o **token JWT** do hPanel da Hostinger para autenticação. O token é armazenado de forma segura no servidor em arquivos de sessão.

### Como funciona

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Seu Navegador │────▶│  hPanel Hostinger│────▶│    Dashboard    │
│   (Logado)      │     │  (Token JWT)     │     │  (Servidor)     │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

### Formato de Sessão

As sessões são armazenadas em arquivos JSON na pasta `cookies/`:

```json
{
    "token": "eyJ0eXAiOiJKV1Qi...",
    "gaid": "GA1.1.000000000.0000000000",
    "update": "2026-01-07 15:00:00"
}
```

### Sobre o Token JWT

| Campo | Descrição |
|-------|-----------|
| `token` | Token JWT de autenticação (~1h de validade) |
| `gaid` | ID do Google Analytics (opcional) |
| `update` | Data/hora da última atualização |

> ⚠️ O token JWT expira a cada **~1 hora**. Será necessário atualizá-lo periodicamente.

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

2. **Crie a pasta de cookies** (se não existir):
```bash
mkdir cookies
```

3. **Acesse o dashboard** no navegador:
```
http://localhost/Hostinger-Dashboard/
```

4. **Configure o token JWT** na página de Configurações.

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

## 📁 Estrutura do Projeto

```
Hostinger-Dashboard/
├── index.php           # Página principal (lista servidores)
├── server.php          # Detalhes do servidor e websites
├── settings.php        # Configurações (token JWT)
├── cookies/            # Arquivos de sessão (JSON)
├── assets/
│   ├── style.css       # Estilos do dashboard
│   └── config.js       # Gerenciamento de sessão
└── api/
    ├── config.php          # Funções de configuração
    ├── save-config.php     # Salvar token
    ├── jwt-status.php      # Status do token
    ├── HostingerClient.php # Cliente da API Hostinger
    ├── websites.php        # Endpoint: listar servidores
    ├── server.php          # Endpoint: detalhes do servidor
    ├── databases.php       # Endpoint: listar bancos
    ├── phpmyadmin.php      # Endpoint: link phpMyAdmin
    ├── file-browser.php    # Endpoint: link gerenciador de arquivos
    ├── php-version.php     # Endpoint: versão PHP
    └── set-php-version.php # Endpoint: alterar versão PHP
```

---

## 🛡️ Segurança

- Token e sessão são armazenados no servidor (pasta `cookies/`)
- Hash único identifica cada sessão (salvo no localStorage do navegador)
- Dados não são enviados para nenhum servidor externo
- O token expira automaticamente após ~1 hora
- Recomenda-se usar em ambiente local ou protegido

---

## 📝 Notas Técnicas

### APIs Utilizadas

O dashboard usa APIs internas do hPanel (não oficiais):

- `/api/wh-api/api/hapi/v1/orders/websites` - Lista servidores
- `/api/wh-api/api/hapi/v1/accounts/{username}/databases` - Lista bancos
- `/api/wh-api/api/hapi/v1/accounts/{username}/vhosts/{domain}/php/version` - Versão PHP

### Limitações

- As APIs podem mudar sem aviso (são internas)
- Tokens JWT expiram após ~1 hora
- Algumas ações podem falhar se o token for antigo

---

## 📄 Licença

MIT License - Veja [LICENSE](LICENSE) para detalhes.

---

## 🤝 Contribuição

Contribuições são bem-vindas! Sinta-se à vontade para enviar um Pull Request.
