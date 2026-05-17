---
phase: 02-confirmar-escala
plan: 02A
type: execute
wave: 1
depends_on: []
files_modified:
  - admin/escala_detalhe.php
  - assets/css/pages/detail_v3.css
autonomous: true
requirements:
  - ESC-01
  - ESC-02

must_haves:
  truths:
    - "admin/escala_detalhe.php contem div id='confirm-footer' com botoes Confirmar e Recusar"
    - "O footer so aparece quando $myMemberData['status'] === 'pending'"
    - "O JS chama fetch('../api/confirm_scale.php') com schedule_id e status via POST JSON"
    - "Apos sucesso AJAX o footer transita para estado 'Confirmado'/'Recusado' sem reload"
    - "detail_v3.css contem .confirm-footer com position: fixed e padding-bottom: env(safe-area-inset-bottom, 16px)"
  artifacts:
    - path: "admin/escala_detalhe.php"
      provides: "Footer sticky de confirmacao com AJAX"
  key_links:
    - from: "admin/escala_detalhe.php id=confirm-footer"
      to: "api/confirm_scale.php"
      via: "fetch() POST JSON"
      pattern: "confirmarPresenca\\('confirmed'\\)|confirmarPresenca\\('declined'\\)"
---

<objective>
Adicionar footer sticky de confirmacao de presenca em `admin/escala_detalhe.php`.

O footer aparece apenas quando o usuario logado e participante da escala com status `pending`. Botoes "Confirmar" e "Recusar" chamam `api/confirm_scale.php` via AJAX (sem reload). Apos sucesso, o footer transita para estado de status ("Confirmado" / "Recusado") com botao "Alterar" que reexibe os botoes originais.

Output: Musico consegue confirmar/recusar presenca diretamente na tela da escala sem reload de pagina (ESC-01 + ESC-02).
</objective>

<context>
@.planning/ROADMAP.md
@.planning/phases/02-confirmar-escala/02-CONTEXT.md
</context>

<tasks>

<task type="auto">
  <name>Task 1: Adicionar CSS do footer sticky em detail_v3.css</name>
  <files>
    assets/css/pages/detail_v3.css
  </files>
  <read_first>
    Ler `assets/css/pages/detail_v3.css` completo para encontrar o ponto de insercao correto
    (adicionar ao final do arquivo, apos o ultimo seletor existente).
    Verificar se ja existe qualquer `.confirm-footer` ou `#confirm-footer` — se existir, NAO duplicar.
  </read_first>
  <action>
    Adicionar ao FINAL de `assets/css/pages/detail_v3.css`:

    ```css
    /* ===== CONFIRM FOOTER (Phase 2) ===== */
    .confirm-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 100;
        background: var(--bg-surface);
        border-top: 1px solid var(--border-subtle);
        box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.08);
        padding: 12px 20px;
        padding-bottom: env(safe-area-inset-bottom, 16px);
    }

    .confirm-footer-inner {
        max-width: 600px;
        margin: 0 auto;
    }

    .confirm-prompt {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin: 0 0 10px 0;
        text-align: center;
    }

    .confirm-btns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .btn-confirm-action {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 12px;
        background: var(--color-primary, #3B82F6);
        color: white;
        border: none;
        border-radius: var(--radius-md, 10px);
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        min-height: 44px;
        transition: opacity 0.15s;
    }

    .btn-confirm-action:disabled { opacity: 0.5; }

    .btn-refuse-action {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 12px;
        background: transparent;
        color: var(--color-danger, #EF4444);
        border: 2px solid var(--color-danger, #EF4444);
        border-radius: var(--radius-md, 10px);
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        min-height: 44px;
        transition: opacity 0.15s;
    }

    .btn-refuse-action:disabled { opacity: 0.5; }

    .confirm-done-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .confirm-done-label {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .btn-alterar-resposta {
        font-size: 0.8rem;
        color: var(--color-primary, #3B82F6);
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px 8px;
        text-decoration: underline;
    }

    .has-confirm-footer .scale-detail-wrapper {
        padding-bottom: 100px;
    }
    ```
  </action>
  <acceptance_criteria>
    - `detail_v3.css` contem `.confirm-footer` com `position: fixed`
    - `detail_v3.css` contem `padding-bottom: env(safe-area-inset-bottom, 16px)`
    - `detail_v3.css` contem `.btn-confirm-action` com `min-height: 44px`
    - `detail_v3.css` contem `.btn-refuse-action` com `min-height: 44px`
    - `detail_v3.css` contem `.has-confirm-footer .scale-detail-wrapper`
  </acceptance_criteria>
  <done>CSS do footer sticky adicionado em detail_v3.css.</done>
</task>

<task type="auto">
  <name>Task 2: Adicionar HTML do footer em escala_detalhe.php (VIEW MODE)</name>
  <files>
    admin/escala_detalhe.php
  </files>
  <read_first>
    Ler `admin/escala_detalhe.php` completo.
    Localizar:
    - Linha ~484: `<?php endif; ?>` que fecha o bloco VIEW MODE (o else do $isEditable)
    - Linha ~486: `</div>` que fecha `.scale-detail-wrapper`
    - Linha ~803: `<?php renderAppFooter(); ?>`
    Confirmar que $myMemberData esta disponivel neste escopo (declarado em linha ~142).
    Confirmar que $id esta disponivel (linha ~18).
    Verificar se ja existe qualquer `confirm-footer` no arquivo — se existir, NAO duplicar.
  </read_first>
  <action>
    Inserir o bloco abaixo logo APOS o `</div>` que fecha `.scale-detail-wrapper`
    (linha ~486, antes dos modais de edit mode), fora do bloco if/else do $isEditable:

    ```php
    <?php if (!$isEditable && $myMemberData && $myMemberData['status'] === 'pending'): ?>
    <script>document.body.classList.add('has-confirm-footer');</script>
    <div id="confirm-footer" class="confirm-footer">
        <div class="confirm-footer-inner">
            <div id="confirm-pending-state">
                <p class="confirm-prompt">Confirme sua presença nesta escala</p>
                <div class="confirm-btns">
                    <button onclick="confirmarPresenca('confirmed')" class="btn-confirm-action">
                        <i data-lucide="check-circle" width="18"></i> Confirmar
                    </button>
                    <button onclick="confirmarPresenca('declined')" class="btn-refuse-action">
                        <i data-lucide="x-circle" width="18"></i> Recusar
                    </button>
                </div>
            </div>
            <div id="confirm-done-state" style="display:none;" class="confirm-footer-inner">
                <div class="confirm-done-row">
                    <span id="confirm-done-label" class="confirm-done-label"></span>
                    <button onclick="document.getElementById('confirm-done-state').style.display='none'; document.getElementById('confirm-pending-state').style.display='block';" class="btn-alterar-resposta">Alterar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function confirmarPresenca(status) {
        var btnC = document.querySelector('.btn-confirm-action');
        var btnR = document.querySelector('.btn-refuse-action');
        if (btnC) btnC.disabled = true;
        if (btnR) btnR.disabled = true;

        fetch('../api/confirm_scale.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({schedule_id: <?= (int)$id ?>, status: status})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('confirm-pending-state').style.display = 'none';
                var label = status === 'confirmed' ? '✅ Confirmado' : '❌ Recusado';
                document.getElementById('confirm-done-label').textContent = label;
                document.getElementById('confirm-done-state').style.display = 'block';
            } else {
                if (btnC) btnC.disabled = false;
                if (btnR) btnR.disabled = false;
                alert('Erro: ' + (data.message || 'Tente novamente.'));
            }
        })
        .catch(function() {
            if (btnC) btnC.disabled = false;
            if (btnR) btnR.disabled = false;
            alert('Erro de conexão. Verifique sua internet.');
        });
    }
    </script>
    <?php endif; ?>
    ```
  </action>
  <acceptance_criteria>
    - `admin/escala_detalhe.php` contem `id="confirm-footer"`
    - `admin/escala_detalhe.php` contem `$myMemberData['status'] === 'pending'`
    - `admin/escala_detalhe.php` contem `confirmarPresenca('confirmed')`
    - `admin/escala_detalhe.php` contem `confirmarPresenca('declined')`
    - `admin/escala_detalhe.php` contem `fetch('../api/confirm_scale.php'`
    - `admin/escala_detalhe.php` contem `schedule_id: <?= (int)$id ?>`
    - `admin/escala_detalhe.php` contem `document.body.classList.add('has-confirm-footer')`
    - `admin/escala_detalhe.php` contem `id="confirm-pending-state"`
    - `admin/escala_detalhe.php` contem `id="confirm-done-state"`
    - O bloco esta FORA do if ($isEditable) / else — nao dentro do edit form
  </acceptance_criteria>
  <done>Footer sticky de confirmacao adicionado em escala_detalhe.php com AJAX e transicao de estado.</done>
</task>

</tasks>

<verification>
Testar manualmente com php -S localhost:8080:

1. Logar como usuario que esta em uma escala com status 'pending'
2. Abrir admin/escala_detalhe.php?id=X
3. Footer sticky deve aparecer na parte inferior com botoes "Confirmar" e "Recusar"
4. Clicar "Confirmar" — footer deve trocar para "✅ Confirmado" + link "Alterar" sem reload
5. Clicar "Alterar" — botoes devem reaparecer
6. Clicar "Recusar" — footer deve trocar para "❌ Recusado"
7. Abrir como admin (ou usuario sem status pending) — footer NAO deve aparecer
8. Verificar no banco: `SELECT status FROM schedule_users WHERE user_id=X AND schedule_id=Y` deve mostrar 'confirmed' ou 'declined'
</verification>

<success_criteria>
- ESC-01: Musico ve botoes "Confirmar" e "Recusar" na tela de detalhe ✓
- ESC-02: Musico confirma/recusa via AJAX com feedback visual imediato, sem reload ✓
- Footer nao aparece para admin em modo edicao ✓
- Footer nao aparece quando status ja e 'confirmed' ou 'declined' (PHP nao renderiza) ✓
- Compativel com iOS Safari (padding-bottom com env(safe-area-inset-bottom)) ✓
</success_criteria>

<output>
Apos conclusao, nao e necessario criar SUMMARY separado — o plano 02A e verificado pelo 02B.
</output>
