---
phase: 02-confirmar-escala
plan: 02C
type: execute
wave: 2
depends_on: []
files_modified:
  - admin/escalas.php
autonomous: true
requirements:
  - ESC-04

must_haves:
  truths:
    - "admin/escalas.php calcula $confirmedCount a partir de $participantsMap com status === 'confirmed'"
    - "Card de cada escala futura exibe o contador 'X/Y confirmados'"
    - "O contador e calculado em PHP sem query adicional (usa participantsMap ja carregado)"
  artifacts:
    - path: "admin/escalas.php"
      provides: "Contador X/Y confirmados nos cards das escalas"
  key_links:
    - from: "escalas.php participantsMap"
      to: "badge X/Y confirmados"
      via: "array_filter status=confirmed"
      pattern: "confirmedCount|confirmados"
---

<objective>
Adicionar contador "X/Y confirmados" nos cards de escalas futuras em `admin/escalas.php`.

O `$participantsMap` ja e carregado com `su.status` por participante (linha ~53). Calcular quantos estao com `status = 'confirmed'` vs total e exibir como badge no card.

Sem query adicional — reutilizar dados ja carregados.

Output: Lider ve de relance quantos musicos confirmaram para cada escala (ESC-04).
</objective>

<context>
@.planning/phases/02-confirmar-escala/02-CONTEXT.md
</context>

<tasks>

<task type="auto">
  <name>Task 1: Calcular e exibir contador X/Y confirmados no card da escala</name>
  <files>
    admin/escalas.php
  </files>
  <read_first>
    Ler `admin/escalas.php` completo.

    Localizar:
    1. O bloco de eager loading (~linha 52-73) que monta $participantsMap com os campos:
       `su.schedule_id, su.user_id, u.name, u.photo, u.avatar_color, su.status`
    2. O loop de renderizacao das escalas futuras (~linha 166) com o card HTML
    3. O trecho que exibe avatares e "X musicas" (~linha 220-238)
    4. O trecho que ja calcula $myStatus para o badge de status do usuario logado (~linha 186-193)

    Confirmar que `$participantsMap[$schedule['id']]` contem o campo 'status' para cada participante.
  </read_first>
  <action>
    MODIFICACAO 1 — Adicionar calculo do contador JUNTO com as variaveis ja existentes no loop
    (~linha 183-194, logo antes de ou logo depois de $myStatus):

    ```php
    // Calcular confirmacoes (usa participantsMap ja carregado, sem query extra)
    $parts = $participantsMap[$schedule['id']] ?? [];
    $confirmedCount = count(array_filter($parts, function($p) { return $p['status'] === 'confirmed'; }));
    $totalParticipants = count($parts);
    ```

    ATENCAO: Se $parts ja e calculado em outro lugar no loop, reutilizar a variavel existente
    em vez de declarar de novo.

    MODIFICACAO 2 — Adicionar o badge do contador no card HTML, JUNTO ao span de "X musicas"
    (~linha 236, no bloco de avatares e song count):

    Localizar:
    ```php
    <span style="font-size: 0.75rem; color: var(--color-text-muted); font-weight: 600;"><?= $songsCount ?> músicas</span>
    ```

    Substituir por:
    ```php
    <span style="font-size: 0.75rem; color: var(--color-text-muted); font-weight: 600;"><?= $songsCount ?> músicas</span>
    <?php if ($totalParticipants > 0): ?>
    <span style="font-size: 0.7rem; font-weight: 700; padding: 2px 7px; border-radius: 999px; <?= ($confirmedCount === $totalParticipants) ? 'background:#DCFCE7; color:#16A34A;' : ($confirmedCount === 0 ? 'background:#FEF3C7; color:#D97706;' : 'background:#EFF6FF; color:#3B82F6;') ?>">
        <?= $confirmedCount ?>/<?= $totalParticipants ?> ✓
    </span>
    <?php endif; ?>
    ```

    A logica de cor:
    - Todos confirmaram: verde (#DCFCE7 / #16A34A)
    - Nenhum confirmou: amarelo (#FEF3C7 / #D97706)
    - Confirmacao parcial: azul (#EFF6FF / #3B82F6)
  </action>
  <acceptance_criteria>
    - `admin/escalas.php` contem `$confirmedCount`
    - `admin/escalas.php` contem `array_filter` com `status === 'confirmed'`
    - `admin/escalas.php` contem `$totalParticipants`
    - `admin/escalas.php` contem `$confirmedCount ?>/<?= $totalParticipants ?>` (o badge X/Y)
    - O badge so aparece quando `$totalParticipants > 0` (sem participantes, nada e exibido)
  </acceptance_criteria>
  <done>Contador X/Y confirmados adicionado nos cards de escalas futuras em escalas.php.</done>
</task>

</tasks>

<verification>
Testar manualmente com php -S localhost:8080:

1. Abrir admin/escalas.php
2. Cards de escalas futuras com participantes devem mostrar badge "X/Y ✓"
3. Escala com todos confirmados: badge verde
4. Escala sem nenhum confirmado: badge amarelo
5. Escala com confirmacao parcial: badge azul
6. Escalas sem participantes: nao deve mostrar badge (sem erro PHP)
7. Verificar que nao houve regressao nas outras informacoes do card (avatares, song count, my status badge)
</verification>

<success_criteria>
- ESC-04: Lider ve quantos confirmaram vs pendente na lista de escalas ✓
- Sem query adicional ao banco (performance mantida) ✓
- Badge com cores semanticas por estado ✓
</success_criteria>
