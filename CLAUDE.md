# CLAUDE.md — App Louvor PIB Oliveira

## Projeto

PWA em PHP/MySQL para o Ministério de Louvor da PIB Oliveira. Gerencia escalas, repertório, membros, avisos e devocionais da equipe de louvor.

**Stack:** PHP 8+ / MySQL / PDO / Vanilla JS / CSS custom / Hostinger
**URL Produção:** `vilela.eng.br/applouvor`
**Dev Local:** `php -S localhost:8080` via `run_server.bat`
**Banco local:** `pibo_louvor` | **Banco prod:** `u884436813_applouvor`

## GSD Workflow

Este projeto usa GSD (Get Shit Done) para desenvolvimento estruturado.

**Modo:** YOLO (auto-aprovar, executar sem confirmações desnecessárias)
**Granularidade:** Fina (fases focadas, 5-10 planos cada)
**Commits:** Sempre semânticos (feat/fix/refactor/docs/chore)

### Para iniciar uma fase:
```
/gsd-plan-phase N
```

### Para executar:
```
/gsd-execute-phase N
```

### Para verificar progresso:
```
/gsd-progress
```

## Estado Atual (2026-05-16)

- **Fase atual:** Phase 1 — Git Cleanup
- **Roadmap:** `.planning/ROADMAP.md` (9 fases)
- **Requisitos:** `.planning/REQUIREMENTS.md` (29 requisitos)

## Arquitetura

```
admin/          # Páginas do líder (admin)
app/            # (futuro) Páginas do músico separadas
api/            # REST API endpoints (JSON)
assets/css/     # Design system (60+ arquivos CSS)
assets/js/      # JavaScript vanilla
includes/       # PHP shared (auth, config, db, layout)
includes/classes/ # PSR-4 autoload (DB, Validator, AuthMiddleware)
maintenance/    # Scripts de manutenção (não deploy)
.planning/      # GSD planning docs
```

## Regras de Desenvolvimento

1. **Mobile-first sempre** — Testar em viewport 375px
2. **Sem frameworks PHP** — PHP puro com PDO, sem Laravel/Symfony
3. **Sem build step** — CSS e JS puros, sem npm/webpack no servidor
4. **Commits atômicos** — Um commit por feature/fix lógico
5. **Preservar páginas existentes** — Nunca quebrar o que já funciona
6. **Design system existente** — Usar variáveis CSS de `assets/css/core/variables.css`

## Design System

- **Primary:** `#3B82F6` (azul)
- **CTA:** `#F97316` (laranja)
- **Componente base:** `.pib-card` (12px radius, shadow-sm → shadow-md hover)
- **Dark mode:** classe `.dark-mode` no body
- **Fontes:** Inter Tight 800 (títulos), Inter (corpo)
- **Toque mínimo:** 44×44px

## Auth

- `includes/auth.php` — `checkLogin()`, `checkAdmin()`
- `$_SESSION['user_id']`, `['user_name']`, `['user_role']` (admin|user)
- Bcrypt com auto-migração de senhas legacy

## Banco de Dados

Tabelas principais: `users`, `songs`, `tags`, `song_tags`, `schedules`, `schedule_users`, `schedule_songs`, `avisos`, `aviso_reactions`, `devocionais`, `push_subscriptions`

Para adicionar colunas/tabelas: criar migration em `database/migrations/` com nome `NNN_descricao.sql`
