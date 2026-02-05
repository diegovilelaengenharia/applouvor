# App Louvor - PIB Oliveira

Aplicativo para gestão do Ministério de Louvor da Primeira Igreja Batista em Oliveira/MG.

## 🎯 Funcionalidades

### Painel Administrativo (Líder)
- **Gestão de Escalas:** Criação e edição de escalas trimestrais
- **Gestão de Repertórios:** Montagem de listas de músicas vinculadas aos cultos
- **Gestão de Equipe:** Controle de membros e instrumentos
- **Relatórios:** Estatísticas e indicadores do ministério

### App do Músico (Participante)
- **Minhas Escalas:** Visualização de datas e confirmação/recusa de presença
- **Repertório:** Acesso às músicas da semana com cifras e links
- **Sugestões:** Espaço para sugerir novas canções
- **Devocionais:** Leitura bíblica diária

## 🛠️ Tecnologias

- **Backend:** PHP 7.4+ (Arquitetura moderna com classes organizadas)
- **Frontend:** HTML5, CSS3 (Design System "Vilela Premium")
- **Banco de Dados:** MySQL com PDO
- **Infra:** Hospedagem Hostinger
- **PWA:** Progressive Web App instalável

## 🏗️ Arquitetura

### Estrutura de Pastas
```
app-louvor/
├── admin/              # Páginas administrativas
├── app/                # Páginas do músico
├── api/                # Endpoints da API
├── assets/             # CSS, JS, imagens
├── includes/           # Arquivos compartilhados
│   ├── classes/        # Classes organizadas (PSR-4)
│   │   ├── Validator.php      # Validação de formulários
│   │   ├── DB.php             # Query Builder
│   │   ├── AuthMiddleware.php # Autenticação
│   │   └── DotEnv.php         # Variáveis de ambiente
│   ├── autoload.php    # Autoloader PSR-4
│   ├── config.php      # Configurações
│   ├── db.php          # Conexão com banco
│   └── auth.php        # Funções de autenticação
├── .env                # Variáveis de ambiente (não versionado)
└── .env.example        # Template de configuração
```

### Novas Melhorias (v4.1)

#### 1. Autoloading PSR-4
Classes são carregadas automaticamente sem `require_once`:
```php
// Antes
require_once 'includes/validator.php';

// Agora
// Automático! Apenas use:
$validator = new App\Validator();
```

#### 2. Variáveis de Ambiente
Credenciais e configurações no arquivo `.env`:
```env
DB_HOST=localhost
DB_NAME=louvor_pib
DB_USER=root
DB_PASS=
```

#### 3. Validação Centralizada
```php
$validator = new App\Validator();
$validator->required($_POST['name'], 'Nome');
$validator->email($_POST['email'], 'E-mail');

if ($validator->hasErrors()) {
    $errors = $validator->getErrors();
}
```

#### 4. Query Builder
```php
// Buscar músicas aprovadas
$songs = App\DB::table('songs')
    ->where('status', '=', 'approved')
    ->orderBy('title', 'ASC')
    ->get();

// Inserir nova música
App\DB::table('songs')->insert([
    'title' => 'Amazing Grace',
    'artist' => 'John Newton'
]);
```

#### 5. Middleware de Autenticação
```php
// No topo de páginas admin
App\AuthMiddleware::requireAdmin();

// Verificar se está logado
if (App\AuthMiddleware::check()) {
    // ...
}
```

## 📦 Como Rodar Localmente

### 1. Configurar Ambiente
```bash
# Copiar template de configuração
copy .env.example .env

# Editar .env com suas credenciais locais
```

### 2. Configurar Banco de Dados
```bash
# Importar schema no MySQL
mysql -u root -p louvor_pib < schema.sql
```

### 3. Iniciar Servidor
```bash
# Usando XAMPP
# 1. Inicie Apache e MySQL no XAMPP Control Panel
# 2. Acesse: http://localhost/app-louvor

# OU usando servidor embutido do PHP
php -S localhost:8000
```

## 🚀 Deploy

### Hostinger
1. Fazer upload via FTP ou Git
2. Configurar `.env` com credenciais de produção
3. Importar banco de dados no phpMyAdmin
4. Acessar URL do site

**Importante:** Nunca versione o arquivo `.env` com credenciais reais!

## 📝 Exemplos de Uso

### Validar Formulário
```php
$validator = new App\Validator();
$validator->required($_POST['title'], 'Título');
$validator->min($_POST['title'], 3, 'Título');

if ($validator->hasErrors()) {
    foreach ($validator->getErrors() as $error) {
        echo "<p class='error'>$error</p>";
    }
}
```

### Buscar Dados
```php
// Query Builder (queries simples)
$members = App\DB::table('members')
    ->where('active', '=', 1)
    ->orderBy('name')
    ->get();

// PDO direto (queries complexas)
$stmt = $pdo->prepare("SELECT * FROM songs WHERE ...");
$stmt->execute();
```

### Proteger Página
```php
<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Exigir login de admin
App\AuthMiddleware::requireAdmin();

// Resto do código...
?>
```

## 🔒 Segurança

- ✅ Credenciais em variáveis de ambiente
- ✅ Proteção CSRF em formulários
- ✅ Prepared statements (PDO)
- ✅ Validação de entrada
- ✅ Controle de acesso por roles

## 📄 Licença

Propriedade de PIB Oliveira - Uso interno

---

**Desenvolvido por Diego T. N. Vilela**  
WhatsApp: (35) 98452-9577
