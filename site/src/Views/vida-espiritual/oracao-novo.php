<?php
$title = "Novo Pedido de Oração";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$categories = [
    'saude'    => ['label' => 'Saúde',    'icon' => 'favorite'],
    'familia'  => ['label' => 'Família',  'icon' => 'family_restroom'],
    'gratidao' => ['label' => 'Gratidão', 'icon' => 'volunteer_activism'],
    'trabalho' => ['label' => 'Trabalho', 'icon' => 'work'],
    'other'    => ['label' => 'Geral',    'icon' => 'circle'],
];
$selectedCat = $_POST['category'] ?? '';
?>

<!-- Top bar -->
<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/oracao" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Novo Pedido</h1>
    <span class="material-symbols-outlined text-[22px] text-primary">self_improvement</span>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <form method="POST" action="/oracao/novo" class="space-y-5">
        <?= csrf_field() ?>

        <!-- Categoria -->
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3 ml-1">Categoria</label>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($categories as $key => $cat): ?>
                <label class="cursor-pointer">
                    <input type="radio" name="category" value="<?= $key ?>"
                           <?= $selectedCat === $key ? 'checked' : ($selectedCat === '' && $key === 'other' ? '' : '') ?>
                           class="sr-only peer">
                    <span class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700
                                 peer-checked:border-primary peer-checked:text-white transition-all cursor-pointer"
                          style="<?= $selectedCat === $key ? 'background-color: var(--primary);' : '' ?>">
                        <span class="material-symbols-outlined text-[13px]"><?= $cat['icon'] ?></span>
                        <?= $cat['label'] ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pedido -->
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Seu pedido *</label>
            <textarea name="title" rows="5" required
                      placeholder="Espaço sagrado — compartilhe com o ministério..."
                      class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-on-surface placeholder:text-on-surface-variant/40 input-glow transition-all resize-none dark:bg-slate-800 dark:border-slate-700"><?= htmlspecialchars($_POST['title'] ?? '') ?></textarea>
        </div>

        <!-- Detalhes adicionais (opcional) -->
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Detalhes (opcional)</label>
            <textarea name="description" rows="3"
                      placeholder="Contexto adicional, se quiser..."
                      class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3.5 text-on-surface placeholder:text-on-surface-variant/40 input-glow transition-all resize-none dark:bg-slate-800 dark:border-slate-700"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <!-- Anônimo -->
        <label class="flex items-center gap-3 pib-card p-4 cursor-pointer">
            <input type="checkbox" name="is_anonymous" value="1"
                   <?= !empty($_POST['is_anonymous']) ? 'checked' : '' ?>
                   class="w-4 h-4 rounded accent-primary">
            <div class="flex-1">
                <p class="text-sm font-semibold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant">visibility_off</span>
                    Publicar como anônimo
                </p>
                <p class="text-xs text-on-surface-variant mt-0.5">Sua identidade será preservada</p>
            </div>
        </label>

        <!-- Urgente -->
        <label class="flex items-center gap-3 pib-card p-4 cursor-pointer">
            <input type="checkbox" name="is_urgent" value="1"
                   <?= !empty($_POST['is_urgent']) ? 'checked' : '' ?>
                   class="w-4 h-4 rounded accent-primary">
            <div class="flex-1">
                <p class="text-sm font-semibold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-red-500">priority_high</span>
                    Marcar como urgente
                </p>
                <p class="text-xs text-on-surface-variant mt-0.5">Prioridade máxima de intercessão</p>
            </div>
        </label>

        <!-- Ações -->
        <div class="flex gap-3 pt-2">
            <a href="/oracao" class="btn-outline flex-1 py-3.5 text-sm font-bold flex items-center justify-center gap-2">
                Cancelar
            </a>
            <button type="submit" class="btn-primary flex-1 py-3.5 text-sm font-bold flex items-center justify-center gap-2 transform active:scale-95">
                <span class="material-symbols-outlined text-[18px]">send</span> Publicar pedido
            </button>
        </div>
    </form>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
