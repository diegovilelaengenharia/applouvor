# SPEC: Phase 5 — Repertório & Cifras

## 1. Visão Geral
Gerenciamento central do ministério de louvor: banco de dados de músicas com informações estruturadas (Tom, BPM, duração, links externos e cifra). O módulo permite que músicos estudem as músicas e o líder associe essas músicas ao roteiro da escala (Phase 4).

## 2. Requisitos Base (Discussão & Decisões)
- **SONG-01**: Líderes cadastram músicas. *Decisão: A tabela `songs` armazena título, artista, BPM, tom e os links (letra, cifra, áudio, vídeo).*
- **SONG-02**: Líderes vinculam à escala. *Decisão: A tabela `schedule_songs` junta `schedules` com `songs`.*
- **SONG-03**: Músicos visualizam detalhes. *Decisão: A Rota `/musicas/{id}` trará os detalhes. Um sub-recurso `/musicas/{id}/cifra` existirá para o "modo palco" (Tela 10).*
- **SONG-04**: Sugestões de músicas. *Decisão: A tabela `song_suggestions` lidará com esse fluxo (pode ficar para uma subfase posterior caso priorizemos apenas o core agora).*

## 3. Telas do Stitch Incorporadas
Com base no `NAV-MAP.md`, faremos a conversão do HTML (que já existe na pasta `.stitch/`) das seguintes telas geradas via Claude Code:
- **07: Repertório (lista)** (`/repertorio`): Lista de músicas e artistas.
- **08: Música — Detalhe** (`/musicas/{id}`): Resumo de tom, BPM e botões de atalho para YouTube/CifraClub.
- **09: Música — Criar/Editar** (`/musicas/nova`): Formulário para alimentar o banco de dados.
- **10: Cifra (palco)** (`/musicas/{id}/cifra`): View limpa focada em leitura e auto-scroll.

## 4. Acceptance Criteria (DoD)
- [ ] O Controller `SongController` gerencia o CRUD e a busca.
- [ ] A inserção de dados protege os campos usando PDO Parameterized Queries.
- [ ] Os formulários aplicam o helper de segurança CSRF (Phase 2).
- [ ] O design incorpora exatamente o HTML exportado das telas do Stitch correspondentes aos IDs mapeados.
- [ ] `router.php` é atualizado para as rotas do Repertório.
