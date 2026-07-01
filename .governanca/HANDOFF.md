# HANDOFF — Guia de Continuação do Projeto
> Válido para Claude Code e Gemini. Cole este arquivo no início de cada nova sessão.

---

## O que é este projeto

**APP Louvor Novíssimo** — PWA para o ministério de louvor da PIB Oliveira.
- Gestão de escalas de culto, repertório de músicas, perfil dos músicos
- Vida espiritual: mural de oração, devocionais diários
- Comunidade: avisos, notificações, mensagens
- Admin: membros, relatórios, ministério

**Stack:** PHP 8 MVC manual | MySQL/PDO | Tailwind CDN | Vanilla JS | Hostinger shared hosting

---

## Arquitetura (nunca mude sem entender)

```
router.php          ← Front controller. Registra todas as rotas.
src/
  config/db.php     ← Conexão PDO ($pdo global via require)
  classes/
    Router.php      ← Dispatch GET/POST com regex params
    AuthMiddleware.php ← requireLogin() / requireAdmin()
    DB.php          ← Query builder fluente (App\DB::table())
  helpers/
    auth.php        ← login(), logout(), session setup (30 dias)
    csrf.php        ← csrf_field(), csrf_verify()
    rate_limit.php  ← Limite de tentativas de login
  Controllers/      ← Herdam Controller.php (render/json/redirect + PDO)
  Models/           ← Herdam Model.php (all/find/where + PDO)
  Views/
    layouts/        ← head.php · top-app-bar.php · bottom-nav.php · flash.php
    app/            ← Telas gerais (dashboard, avisos, notificações, etc.)
    auth/           ← login, recuperar-senha
    escalas/        ← index, show, form, faltas
    repertorio/     ← index, show, form, cifra
    perfil/         ← index, editar, senha, indisponibilidades
    vida-espiritual/← oracao, oracao-novo, oracao-detalhe, devocionais, devocional
    ministerio/     ← (vazio — próximas waves)
assets/
  css/stitch-theme.css  ← Design system: --primary #2E7EED, dark mode
  js/theme.js           ← Dark mode toggle (localStorage)
  js/app.js             ← Service worker + reveal animations
database/schema.sql     ← 22 tabelas (nunca editar — já existe no banco)
```

---

## Design System (Sacred Minimalist)

**Referência visual:** Stitch MCP, projeto `7244459960065792477`, design system `assets/18053454826462421656`.

| Token | Valor |
|-------|-------|
| Cor primária | `#2E7EED` (var: `--primary`) |
| Fonte display | Hanken Grotesk |
| Fonte corpo | Open Sans |
| Border radius padrão | `8px` / `rounded-xl` |
| Card | `.pib-card` (borda + sombra sutil) |
| Botão primário | `.btn-primary` |
| Botão outline | `.btn-outline` |
| Input | `.input-glow` |
| Animação entrada | `.reveal-item` |
| Ícones | Material Symbols Outlined |

**Dark mode:** classe `.dark` no `<html>`. Toggle via `window.toggleTheme()` (theme.js + localStorage).

---

## Como buscar telas do Stitch

```
# 1. Listar todas as telas (IDs completos)
mcp__stitch__list_screens(projectId: "7244459960065792477")

# 2. Pegar HTML de uma tela (use ID completo de 32 chars)
mcp__stitch__get_screen(
  name: "projects/7244459960065792477/screens/<ID_COMPLETO>",
  projectId: "7244459960065792477",
  screenId: "<ID_COMPLETO>"
)
# → response.htmlCode.downloadUrl

# 3. Baixar HTML para inspecionar
WebFetch(url: "<downloadUrl>", prompt: "Extraia estrutura HTML, CSS classes, ícones, labels de botões")
```

> **IDs curtos no NAV-MAP.md são prefixos** — a API exige o ID completo (32 chars).
> Exemplo: NAV-MAP diz `b5702812` → API precisa `b5702812bef941ba92137a33d8a4233d`.
> Use `list_screens` para obter o ID completo.

---

## Como construir uma nova tela (passo a passo)

### 1. Buscar o design no Stitch
```
get_screen → WebFetch HTML → anotar: ícones, badges, campos do form, botões, filtros
```

### 2. Criar o Model (se precisar de nova tabela)
```php
// src/Models/MinhaTabela.php
namespace App\Models;
class MinhaTabela extends Model {
    protected string $table = 'nome_tabela';
    // Métodos: getAll(), getById(), create(), delete()
}
```

### 3. Criar o Controller
```php
// src/Controllers/MeuController.php
namespace App\Controllers;
use App\AuthMiddleware;
use App\Models\MinhaTabela;

class MeuController extends Controller {
    public function index() {
        AuthMiddleware::requireLogin();
        $model = new MinhaTabela($this->pdo);
        $items = $model->getAll();
        $this->render('pasta/view', ['items' => $items]);
    }
}
```

### 4. Criar a View
```php
<?php
$title = "Título da Página";
$bodyClass = ""; // ou "justify-center p-4" para tela centralizada
require __DIR__ . '/../layouts/head.php';
// Para telas raiz: require '../layouts/top-app-bar.php';
// Para telas secundárias: header inline com arrow_back_ios_new
?>
<!-- conteúdo -->
<?php
// Para telas raiz: require '../layouts/bottom-nav.php';
// Para telas secundárias: fechar manualmente:
?>
<script src="/assets/js/app.js"></script>
</body>
</html>
```

### 5. Adicionar rota no router.php
```php
$router->get('/minha-rota', [App\Controllers\MeuController::class, 'index']);
$router->post('/minha-rota', [App\Controllers\MeuController::class, 'store']);
```

### 6. Lint
```powershell
C:\xampp\php\php.exe -l "caminho\para\arquivo.php"
```

---

## Segurança (obrigatório em TODO POST)

```php
// 1. Autenticação
AuthMiddleware::requireLogin();   // qualquer usuário logado
AuthMiddleware::requireAdmin();   // somente role = 'admin'

// 2. CSRF (SEMPRE em forms POST)
// Na view: <?= csrf_field() ?>
// No controller: csrf_verify();

// 3. Sanitizar output (sempre)
htmlspecialchars($var)

// 4. Ownership check (delete/update de recursos próprios)
WHERE id = :id AND user_id = :uid
```

---

## Regras de negócio importantes

- **Admin** vê FABs de criação, menus ⋮ editar/excluir, painel de membros/relatórios
- **Músico** vê, confirma/recusa presença, ora, comenta, sugere músicas
- **Instrumento na sessão:** `$_SESSION['user_instrument']` — preenchido no login
- **Senha legado:** login aceita texto puro e migra para bcrypt automaticamente
- **Dark mode:** APENAS localStorage + CSS class, sem persistir no banco

---

## Tabelas principais (schema.sql — não alterar colunas existentes)

| Tabela | Descrição |
|--------|-----------|
| `users` | Usuários (role: admin/user) |
| `schedules` | Escalas de culto |
| `schedule_users` | Participantes + status (pending/confirmed/declined/absent) |
| `schedule_songs` | Músicas de cada escala |
| `songs` | Repertório geral |
| `avisos` | Avisos da liderança (prioridade: baixa/media/alta/urgente) |
| `notifications` | Notificações push/in-app (is_read) |
| `prayer_requests` | Pedidos de oração (is_anonymous, is_urgent) |
| `prayer_interactions` | Orações e comentários nos pedidos |
| `devotionals` | Devocionais diários |
| `devotional_comments` | Comentários nos devocionais |
| `devotional_reads` | Registro de leituras por usuário (streak) |
| `user_settings` | Configurações chave-valor por usuário |
| `user_unavailability` | Períodos de indisponibilidade |

---

## Próximas telas a implementar

Ver [STATE.md](STATE.md) §"O QUE FALTA" para a lista completa.

**Prioridade imediata (Wave 4 restante):**
1. Membros `/membros` — tela `c5df171a` (lista) + `10fc4e0a` (detalhe)
2. Relatórios `/relatorios` — tela `ebea1382`
3. Aniversariantes `/aniversariantes` — tela `3630e54b`
4. Ministério `/ministerio` — tela `53f090ee`
5. Sugestões `/sugestoes` — tela `51e4b03f`

**Wave 5 (funcionalidades avançadas):**
- Auto-escalação `1b6b9230`, Ao Vivo `25bae697`, Ensaio `33203941`, Setlist `0647c160`
- Metrônomo `efcc2c36`, Estatísticas Repertório `57dc9502`

---

## Para Claude Code

Ao iniciar nova sessão: leia este arquivo + [STATE.md](STATE.md) + [NAV-MAP.md](NAV-MAP.md).
Use `get_screen` + `WebFetch` para buscar o design antes de escrever qualquer view.
Escreva models/controllers/views/rotas na ordem, rode lint ao final.

## Para Gemini

Ao iniciar nova sessão: leia este arquivo e [STATE.md](STATE.md).
Para buscar telas: use a ferramenta MCP Stitch disponível (mesmas instruções acima).
Siga o mesmo padrão de código dos arquivos existentes em `src/Controllers/` e `src/Views/`.
Nunca commite `src/config/db.php` com credenciais reais — use `.env` na raiz.
