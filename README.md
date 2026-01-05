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

O dashboard utiliza cookies de sessão do hPanel da Hostinger para autenticação. Os cookies são armazenados localmente no **localStorage** do navegador.

### Como funciona

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Seu Navegador │────▶│  hPanel Hostinger│────▶│    Dashboard    │
│   (Logado)      │     │  (Cookies+JWT)   │     │  (localStorage) │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

### Armazenamento de Credenciais

As credenciais são armazenadas no **localStorage** do navegador:

- ✅ **Privado** - Dados ficam apenas no seu navegador
- ✅ **Sem arquivos** - Não precisa criar arquivos de configuração
- ✅ **Por usuário** - Cada usuário pode ter suas próprias credenciais

### Sobre os Cookies e JWT

| Cookie | Tipo | Descrição |
|--------|------|-----------|
| `jwt` | HttpOnly | Token JWT de autenticação principal (1h de validade) |
| `auth_info` | Normal | Informações do cliente |
| `language` | Normal | Preferência de idioma |
| `hostingerDeviceId` | Normal | ID do dispositivo |

> ⚠️ O cookie `jwt` é marcado como **HttpOnly**, o que significa que JavaScript não pode acessá-lo. Por isso, é necessário usar o método cURL para copiar todos os cookies.

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

2. **Acesse o dashboard** no navegador:
```
http://localhost/Hostinger-Dashboard/
```

3. **Configure os cookies** na página de Configurações.

> 💡 Não é necessário criar nenhum arquivo de configuração! Os dados são salvos automaticamente no navegador.

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
├── assets/
│   ├── style.css       # Estilos do dashboard
│   └── config.js       # Gerenciamento de localStorage
└── api/
    ├── config.php      # Funções de configuração
    ├── HostingerClient.php  # Cliente da API Hostinger
    ├── websites.php    # Endpoint: listar servidores
    ├── server.php      # Endpoint: detalhes do servidor
    ├── databases.php   # Endpoint: listar bancos
    ├── phpmyadmin.php  # Endpoint: link phpMyAdmin
    ├── file-browser.php # Endpoint: link gerenciador de arquivos
    ├── php-version.php # Endpoint: versão PHP
    └── set-php-version.php # Endpoint: alterar versão PHP
```

---

## 🛡️ Segurança

- Credenciais são armazenadas apenas no localStorage do seu navegador
- Dados não são enviados para nenhum servidor externo
- Os cookies expiram periodicamente (~1 hora)
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
- Algumas ações podem falhar se os cookies forem antigos

---

## 📄 Licença

MIT License - Veja [LICENSE](LICENSE) para detalhes.

---

## 🤝 Contribuição

Contribuições são bem-vindas! Sinta-se à vontade para enviar um Pull Request.
