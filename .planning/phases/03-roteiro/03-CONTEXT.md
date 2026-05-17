# Phase 3 Context — Roteiro de Culto

## Goal

Líder monta um roteiro ordenado dentro de cada escala (músicas + outros itens de culto). Músico visualiza o roteiro completo antes do culto.

## Requirements

- **ROT-01**: Roteiro de culto como seção dentro da escala (itens ordenáveis)
- **ROT-02**: Tipos de item no roteiro: Música, Oração, Palavra, Anúncio, Intervalo, Livre
- **ROT-03**: Líder cria e edita itens do roteiro (setas ▲/▼ para reordenar)
- **ROT-04**: Músico visualiza roteiro completo na sua view (read-only)
- **ROT-05**: Tom customizado por música na escala (pode diferir do tom padrão do repertório)

## Distinção Roteiro vs Repertório

- **`schedule_songs`** (já existe): pool de músicas selecionadas para a escala — para referência rápida, cifras, letras
- **`schedule_roteiro`** (novo): fluxo ordenado do culto — o que acontece em cada momento, em que ordem, com notas internas do líder

Os dois coexistem. O roteiro pode referenciar músicas do repertório (via `song_id`) ou conter itens sem música (oração, palavra, etc.).

## Decisões Arquiteturais (ROADMAP)

- Setas ▲/▼ para reordenação mobile — drag-and-drop é frágil no toque
- Campo `nota_interna` por item — visível só para admin (líder vê, músico não vê)
- `custom_tone` por item de música — sobrescreve o tom padrão da tabela `songs`
- Drag-and-drop é enhancement desktop opcional (fora do escopo desta fase)

## Schema da Nova Tabela

```sql
CREATE TABLE schedule_roteiro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    order_position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    item_type ENUM('musica','oracao','palavra','anuncio','intervalo','livre') NOT NULL DEFAULT 'musica',
    title VARCHAR(255) NULL,
    song_id INT NULL,
    custom_tone VARCHAR(10) NULL,
    nota_interna TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_schedule_order (schedule_id, order_position),
    CONSTRAINT fk_roteiro_schedule FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    CONSTRAINT fk_roteiro_song FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## API: api/roteiro.php

Endpoints necessários:
- `GET ?schedule_id=N` — lista itens do roteiro em order_position ASC
- `POST {"action":"add", "schedule_id":N, "item_type":"musica", "song_id":5, "custom_tone":"D", "title":"", "nota_interna":""}` — adiciona item ao final
- `POST {"action":"delete", "id":N, "schedule_id":N}` — remove item (valida que schedule pertence ao admin)
- `POST {"action":"reorder", "schedule_id":N, "items":[{"id":1,"pos":0},{"id":2,"pos":1}]}` — atualiza order_position de múltiplos itens

## Arquivos Modificados

| Arquivo | Operação | Plano |
|---------|----------|-------|
| `database/migrations/003_schedule_roteiro.sql` | CREATE | 03-01 |
| `api/roteiro.php` | CREATE | 03-01 |
| `admin/escala_detalhe.php` | MODIFY (edit + view mode) | 03-02, 03-03 |
| `assets/css/pages/detail_v3.css` | MODIFY (estilos roteiro) | 03-02, 03-03 |

## Interfaces de Dependência

De `admin/escala_detalhe.php` (variáveis disponíveis na view):
- `$id` — int, schedule_id da escala atual
- `$isEditable` — bool, true quando admin está em modo edição
- `$songs` — array, músicas do repertório desta escala (song_id, title, artist, tone)
- `$_SESSION['user_role']` — 'admin' ou 'user'
- `$myMemberData` — array|null, dados do usuário logado nesta escala

De `assets/css/core/variables.css`:
- `--primary`: #3B82F6 (azul)
- `--bg-surface`: fundo dos cards
- `--border-subtle`: bordas
- `--text-muted`: cinza para labels secundários

## Tipos de Item — Ícones Lucide

| Tipo | Ícone Lucide | Label PT |
|------|-------------|----------|
| musica | music | Música |
| oracao | hands | Oração |
| palavra | book-open | Palavra |
| anuncio | megaphone | Anúncio |
| intervalo | coffee | Intervalo |
| livre | more-horizontal | Livre |

---
*Criado: 2026-05-17 | Phase 3 — Roteiro de Culto*
