# ExpertSeg Drive

Sistema completo de gerenciamento de arquivos com autenticação, logs e lixeira.

## 🚀 **Como usar:**

```bash
# Subir a aplicação
docker compose up -d

# Acessar
http://localhost:8000  # Aplicação (Laravel + Inertia.js + Vue.js)
# Frontend Vite serve assets em http://localhost:3000 (automático)

# Credenciais padrão
Email: admin@test.com
Senha: password

# Banco de dados
Conecta ao banco remoto: 148.72.155.7

# Ver logs
docker compose logs expertseg-backend   # Backend Laravel
docker compose logs expertseg-frontend  # Frontend Vite

# Parar
docker compose down
```

## 🏗️ **Estrutura do Projeto:**

```
expertseg-drive/
├── app/                    # Controllers, Models, etc.
├── resources/
│   ├── js/
│   │   ├── Pages/          # Componentes Vue.js
│   │   │   ├── Auth/       # Páginas de login/registro
│   │   │   ├── Users/      # Gerenciamento de usuários
│   │   │   ├── ActivityLogs/ # Logs de atividade
│   │   │   └── Trash/      # Lixeira
│   │   └── Layouts/        # Layouts da aplicação
│   └── views/              # Templates Blade
├── routes/                 # Rotas Laravel
├── storage/                # Arquivos e cache
├── Dockerfile              # Build do container
├── docker-compose.yml      # Orquestração
└── README.md               # Documentação
```

## 🎯 **Funcionalidades:**

### 📁 **Gerenciamento de Arquivos**
- ✅ Upload de arquivos
- ✅ Criação de pastas
- ✅ Renomear arquivos/pastas
- ✅ Navegação por pastas
- ✅ Download de arquivos
- ✅ Visualização de imagens

### 👥 **Sistema de Autenticação**
- ✅ Login/Registro de usuários
- ✅ Gerenciamento de usuários
- ✅ Controle de acesso

### 📊 **Logs e Auditoria**
- ✅ Logs de atividade
- ✅ Rastreamento de ações
- ✅ Histórico de operações

### 🗑️ **Lixeira**
- ✅ Restauração de arquivos
- ✅ Exclusão permanente
- ✅ Interface de gerenciamento

### 🎨 **Interface**
- ✅ Design responsivo
- ✅ Navegação intuitiva
- ✅ Feedback visual
- ✅ Flash messages

## 🛠️ **Tecnologias:**

- **Backend**: Laravel 12 + Inertia.js
- **Frontend**: Vue.js 3 + Vuetify 3 + Vite (Hot Reload)
- **Banco**: MySQL 8.0 (Remoto: 148.72.155.7)
- **Containerização**: Docker Compose
- **UI Framework**: Vuetify 3 + Material Design Icons

## 🏗️ **Arquitetura:**

### **Container Backend (expertseg-backend)**
- Laravel 12 + Inertia.js
- Porta: 8000
- Conecta ao banco remoto (148.72.155.7)
- Serve as rotas da API

### **Container Frontend (expertseg-frontend)**
- Node.js 18 + Vite + Vue.js 3 + Vuetify 3
- Porta: 3000
- Hot reload ativo
- Material Design Icons
- Compilação em tempo real

## 📱 **Navegação:**

- **/** - Gerenciador de arquivos principal
- **/login** - Página de login
- **/register** - Página de registro
- **/users** - Gerenciamento de usuários
- **/activity-logs** - Logs de atividade
- **/trash** - Lixeira

## 🔧 **Desenvolvimento:**

```bash
# Rebuild do container
docker compose down && docker compose build && docker compose up -d

# Executar comandos no container
docker exec expertseg_app php artisan [comando]

# Acessar container
docker exec -it expertseg_app bash
```

## 🎉 **Status:**

✅ **Sistema Completo Implementado**
🚀 **Todas as Funcionalidades Funcionando**
📊 **Logs e Auditoria Ativos**
🗑️ **Lixeira Operacional**
👥 **Sistema de Usuários Funcional**