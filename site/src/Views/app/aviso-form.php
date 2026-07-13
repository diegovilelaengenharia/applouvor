<?php
$title = "Novo Aviso";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';
?>

<!-- Top bar -->
<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/avisos" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Novo Aviso</h1>
    <span class="material-symbols-outlined text-[22px] text-primary">campaign</span>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <form method="POST" action="/avisos/novo" class="space-y-5">
        <?= csrf_field() ?>

        <!-- Título -->
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Título *</label>
            <input type="text" name="titulo" required
                   placeholder="Ex: Ensaio geral neste sábado"
                   value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>"
                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-4 pr-4 py-3.5 text-on-surface placeholder:text-on-surface-variant/40 input-glow transition-all dark:bg-slate-800 dark:border-slate-700">
        </div>

        <!-- Prioridade -->
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Prioridade</label>
            <select name="prioridade"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-on-surface input-glow transition-all dark:bg-slate-800 dark:border-slate-700">
                <option value="media" <?= ($_POST['prioridade'] ?? 'media') === 'media' ? 'selected' : '' ?>>Info (normal)</option>
                <option value="alta"  <?= ($_POST['prioridade'] ?? '') === 'alta' ? 'selected' : '' ?>>Importante</option>
                <option value="urgente" <?= ($_POST['prioridade'] ?? '') === 'urgente' ? 'selected' : '' ?>>Urgente</option>
                <option value="baixa" <?= ($_POST['prioridade'] ?? '') === 'baixa' ? 'selected' : '' ?>>Baixa</option>
            </select>
        </div>

        <!-- Conteúdo -->
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Mensagem</label>
            <textarea name="conteudo" rows="5"
                      placeholder="Escreva os detalhes do aviso..."
                      class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-on-surface placeholder:text-on-surface-variant/40 input-glow transition-all resize-none dark:bg-slate-800 dark:border-slate-700"><?= htmlspecialchars($_POST['conteudo'] ?? '') ?></textarea>
        </div>

        <!-- Data de expiração -->
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Expira em (opcional)</label>
            <input type="date" name="data_expiracao"
                   value="<?= htmlspecialchars($_POST['data_expiracao'] ?? '') ?>"
                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-on-surface input-glow transition-all dark:bg-slate-800 dark:border-slate-700">
        </div>

        <!-- Toggle: Fixar -->
        <label class="flex items-center gap-3 pib-card p-4 cursor-pointer">
            <input type="checkbox" name="fixado" value="1"
                   <?= !empty($_POST['fixado']) ? 'checked' : '' ?>
                   class="w-4 h-4 rounded accent-primary">
            <div>
                <p class="text-sm font-semibold text-on-surface flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-amber-500">push_pin</span>
                    Fixar aviso
                </p>
                <p class="text-xs text-on-surface-variant">Aparece sempre no topo da lista</p>
            </div>
        </label>

        <!-- Ações -->
        <div class="flex gap-3 pt-2">
            <a href="/avisos" class="btn-outline flex-1 py-3.5 text-sm font-bold flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">close</span> Cancelar
            </a>
            <button type="submit" class="btn-primary flex-1 py-3.5 text-sm font-bold flex items-center justify-center gap-2 transform active:scale-95">
                <span class="material-symbols-outlined text-[18px]">send</span> Publicar
            </button>
        </div>
    </form>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
