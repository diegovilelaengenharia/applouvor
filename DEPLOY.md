# DEPLOY.md — App Louvor PIB Oliveira

Processo de deploy para `vilela.eng.br/applouvor` no Hostinger.

## Estado atual

- **Deploy automático ativo:** webhook do Hostinger conectado ao GitHub
- **Endpoint do webhook:** configurado no painel Hostinger → Git
- **Branch:** `main` → toda push para main dispara `git pull` no servidor
- **Caminho no servidor:** `/public_html/applouvor/`

## Fluxo de deploy padrão

```bash
# 1. Trabalhar localmente
git add <arquivos>
git commit -m "feat(NN): descrição"

# 2. Push — dispara webhook automaticamente
git push origin main

# 3. Aguardar ~10s e verificar
# Acessar https://vilela.eng.br/applouvor — ou checar logs do webhook no painel Hostinger
```

## Versionamento

- `APP_VERSION` em [includes/config.php](includes/config.php) — versão lógica do app
- `CACHE_NAME` em [sw.js](sw.js) — versão do Service Worker (deve seguir APP_VERSION)
- **Ao bumpar APP_VERSION, bumpar CACHE_NAME também** — força refresh do PWA

Formato:
- APP_VERSION: `M.m` (ex: `5.0`)
- CACHE_NAME: `louvor-pib-vM.m.0` (ex: `louvor-pib-v5.0.0`)

## Arquivos NÃO versionados (gitignored — gerenciar via Hostinger File Manager)

- `.htaccess` — contém `SetEnv DB_HOST/DB_NAME/DB_USER/DB_PASS` e configurações de cache
- `includes/vapid_config.php` — chaves VAPID das push notifications
- `.env*` — variáveis de ambiente locais
- `assets/uploads/` — uploads de usuário em produção

## Verificações pós-deploy

Após cada deploy:
1. `vilela.eng.br/applouvor` carrega sem erro 500/DB
2. Login funciona (sessão persiste)
3. Service Worker atualiza (Chrome DevTools → Application → Service Workers → "skipWaiting")
4. `manifest.json` carrega + ícones em iOS/Android
5. Push notification dispara em escala publicada

## Cache offline (Service Worker)

Páginas com cache offline garantido:
- `/` e `/index.php` (login)
- `/admin/index.php` (dashboard)
- `/admin/metronomo.php` (ensaio sem sinal)
- `/admin/escalas.php` (próximas escalas)
- `/admin/repertorio.php` (repertório)
- `/admin/leitura.php` + `/admin/devocionais.php` + `/admin/oracao.php` (vida cristã)
- Logos e CSS principal

Demais páginas usam network-first (carregam do servidor quando online).

## Migrações de banco

Arquivos: `database/migrations/NNN_descricao.sql`

Executar manualmente em produção via phpMyAdmin do painel Hostinger:
1. Painel Hostinger → MySQL Databases → phpMyAdmin
2. Selecionar database `u884436813_applouvor`
3. Aba SQL → colar conteúdo da migration
4. Executar

Migrações já aplicadas:
- 001..003: schemas base
- 004: `schedule_users.status` ENUM expandido para absent/absent_justified + coluna `absence_note`

## Rollback

Se um deploy quebrar produção:
```bash
# Local — reverter o commit
git revert <hash>
git push origin main
# Webhook dispara → rollback aplicado em ~10s
```

**NUNCA** fazer `git push --force` em main (webhook reaplica o último estado).

## Credenciais de produção

Estão no painel Hostinger — não compartilhar nem commitar. Para senha do banco rotar pelo painel Hostinger → MySQL Databases → Reset Password, depois atualizar `.htaccess` via Hostinger File Manager.

## Comandos úteis locais

```bash
# Servidor de desenvolvimento
php -S localhost:8080
# ou via tools/run_server.bat

# Lint todos os PHP modificados antes de commit
& "C:\xampp\php\php.exe" -l admin/*.php
```

---
*Última atualização: 2026-05-21 (v5.0 — Sacred Minimalist + Stitch + Schema Completo)*
