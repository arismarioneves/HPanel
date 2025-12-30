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
- **🔄 Renovação automática de token** - Mantém a sessão ativa automaticamente

---

## 🔐 Autenticação

O dashboard utiliza cookies de sessão do hPanel da Hostinger para autenticação. Não é possível usar login/senha diretamente, então os cookies precisam ser extraídos manualmente.

### Como funciona

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Seu Navegador │────▶│  hPanel Hostinger│────▶│    Dashboard    │
│   (Logado)      │     │  (Cookies+JWT)   │     │   (Usa cookies) │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

### Sobre os Cookies e JWT

| Cookie | Tipo | Descrição |
|--------|------|-----------|
| `jwt` | HttpOnly | Token JWT de autenticação principal (1h de validade) |
| `auth_info` | Normal | Informações do cliente |
| `language` | Normal | Preferência de idioma |
| `hostingerDeviceId` | Normal | ID do dispositivo |
| Outros | Normal | Analytics, sessão, etc. |

> ⚠️ O cookie `jwt` é marcado como **HttpOnly**, o que significa que JavaScript não pode acessá-lo. Por isso, é necessário usar o método cURL para copiar todos os cookies.

### Renovação Automática de Token

O token JWT expira após ~1 hora. O dashboard implementa **renovação automática**:

1. Quando uma requisição retorna `401 (Unauthorized)`
2. O sistema chama o endpoint de renovação de token
3. Salva o novo token no `config.json`
4. Repete a requisição original

```php
// Fluxo de renovação automática
if ($httpCode === 401 && !$this->tokenRefreshed) {
    if ($this->refreshToken()) {
        return $this->request($endpoint, $headers); // Retry
    }
}
```

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

2. **Copie o arquivo de configuração**:
```bash
cp config-exemplo.json config.json
```

3. **Acesse o dashboard** no navegador:
```
http://localhost/Hostinger-Dashboard/
```

4. **Configure os cookies** na página de Configurações.

---

## ⚙️ Configuração dos Cookies

### Passo a passo

1. Acesse [hpanel.hostinger.com](https://hpanel.hostinger.com) e faça login

2. Abra o DevTools (`F12`) → Aba **Network**

3. Recarregue a página (`F5`)

4. Clique em qualquer requisição da lista

5. Botão direito → **Copy** → **Copy as cURL (bash)**

6. Cole no campo de cookies do Dashboard e salve

### Exemplo de cURL

```bash
curl 'https://hpanel.hostinger.com/api/...' \
  -H 'accept: application/json' \
  -b 'language=pt_BR; jwt=eyJ0eXAiOiJKV1Q...; auth_info=...'
```

O sistema extrai automaticamente os cookies do comando cURL.

---

## 📁 Estrutura do Projeto

```
Hostinger-Dashboard/
├── index.php           # Página principal (lista servidores)
├── server.php          # Detalhes do servidor e websites
├── settings.php        # Configurações e cookies
├── config.json         # Configurações (cookies, GAID) - NÃO COMMITTAR
├── config-exemplo.json # Exemplo de configuração
├── assets/
│   └── style.css       # Estilos do dashboard
└── api/
    ├── config.php      # Funções de configuração
    ├── HostingerClient.php  # Cliente da API Hostinger
    ├── databases.php   # Endpoint: listar bancos
    ├── phpmyadmin.php  # Endpoint: link phpMyAdmin
    ├── filebrowser.php # Endpoint: link gerenciador de arquivos
    ├── php-version.php # Endpoint: versão PHP
    └── set-php-version.php # Endpoint: alterar versão PHP
```

---

## 🔧 Arquivos de Configuração

### config.json

```json
{
    "cookies": "language=pt_BR; jwt=eyJ...; ...",
    "gaid": "GA1.1.000000000.0000000000",
    "lastUpdated": "2024-12-30 14:00:00",
    "tokenRefreshedAt": "2024-12-30 14:30:00"
}
```

| Campo | Descrição |
|-------|-----------|
| `cookies` | String de cookies extraída do cURL |
| `gaid` | Google Analytics ID (extraído dos cookies) |
| `lastUpdated` | Última vez que as configurações foram salvas |
| `tokenRefreshedAt` | Última vez que o token JWT foi renovado |

---

## 🛡️ Segurança

- **Não commite o `config.json`** - Contém seus cookies de autenticação
- O arquivo `config.json` já está no `.gitignore`
- Os cookies expiram periodicamente, mas o sistema tenta renová-los automaticamente
- Recomenda-se usar em ambiente local ou protegido por autenticação adicional

---

## 📝 Notas Técnicas

### APIs Utilizadas

O dashboard usa APIs internas do hPanel (não oficiais):

- `/api/wh-api/api/hapi/v1/accounts/{username}/websites` - Lista websites
- `/api/wh-api/api/hapi/v1/accounts/{username}/databases` - Lista bancos
- `/api/wh-api/api/hapi/v1/accounts/{username}/vhosts/{domain}/php/version` - Versão PHP
- `/api/communication/api/external/v1/auth/token/generate` - Renovar token

### Limitações

- As APIs podem mudar sem aviso (são internas)
- Tokens JWT expiram após ~1 hora
- Algumas ações podem falhar se os cookies forem antigos

---

## 📄 Licença

MIT License - Veja [LICENSE](LICENSE) para detalhes.

---

## 🤝 Contribuição

Contribuições são bem-vindas! Sinta-se à vontade para enviar um Pull Request.
