<?php
$title = "Perfil";
$activeNav = "perfil";

// Iniciais para o avatar
$nome = $user['name'] ?? 'Músico';
$parts = preg_split('/\s+/', trim($nome));
$initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
$avatarColor = $user['avatar_color'] ?? '#2E7EED';
$isAdmin = ($user['role'] ?? 'user') === 'admin';

require __DIR__ . '/../layouts/head.php';
require __DIR__ . '/../layouts/top-app-bar.php';
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <!-- Cartão de identidade -->
    <div class="pib-card p-6 text-center reveal-item mb-6">
        <div class="w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4 text-white text-3xl font-bold shadow-sm"
             style="background-color: <?= htmlspecialchars($avatarColor) ?>;">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="w-full h-full object-cover rounded-full">
            <?php else: ?>
                <?= htmlspecialchars($initials) ?>
            <?php endif; ?>
        </div>

        <h1 class="text-2xl font-bold text-on-surface"><?= htmlspecialchars($nome) ?></h1>
        <span class="inline-flex items-center gap-1 mt-2 px-3 py-1 rounded-full text-xs font-bold <?= $isAdmin ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container text-on-surface-variant' ?>">
            <span class="material-symbols-outlined text-[14px]"><?= $isAdmin ? 'shield_person' : 'music_note' ?></span>
            <?= $isAdmin ? 'Líder / Administrador' : 'Músico' ?>
        </span>

        <?php if (!empty($user['instrument'])): ?>
            <p class="text-sm text-on-surface-variant mt-3 flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">piano</span>
                <?= htmlspecialchars($user['instrument']) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($user['bio'])): ?>
            <p class="text-sm text-on-surface-variant mt-3 leading-relaxed"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
        <?php endif; ?>
    </div>

    <!-- Dados de contato -->
    <div class="pib-card p-2 reveal-item reveal-stagger-1 mb-6">
        <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
            <span class="material-symbols-outlined text-[20px] text-on-surface-variant">mail</span>
            <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-wider text-on-surface-variant font-bold">E-mail</p>
                <p class="text-sm text-on-surface truncate"><?= htmlspecialchars($user['email'] ?? '—') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
            <span class="material-symbols-outlined text-[20px] text-on-surface-variant">call</span>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-on-surface-variant font-bold">Telefone</p>
                <p class="text-sm text-on-surface"><?= htmlspecialchars($user['phone'] ?? '—') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3 px-4 py-3">
            <span class="material-symbols-outlined text-[20px] text-on-surface-variant">cake</span>
            <div>
                <p class="text-[11px] uppercase tracking-wider text-on-surface-variant font-bold">Nascimento</p>
                <p class="text-sm text-on-surface"><?= !empty($user['birth_date']) ? date('d/m/Y', strtotime($user['birth_date'])) : '—' ?></p>
            </div>
        </div>
    </div>

    <!-- Atalhos -->
    <div class="space-y-2 reveal-item reveal-stagger-2">
        <a href="/perfil/editar" class="flex items-center justify-between pib-card px-4 py-3.5 hover:shadow-md transition-shadow">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-primary">edit</span> Editar perfil
            </span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
        <a href="/perfil/senha" class="flex items-center justify-between pib-card px-4 py-3.5 hover:shadow-md transition-shadow">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-primary">lock</span> Alterar senha
            </span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
        <a href="/indisponibilidades" class="flex items-center justify-between pib-card px-4 py-3.5 hover:shadow-md transition-shadow">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-primary">event_busy</span> Indisponibilidades
            </span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
        <a href="/configuracoes" class="flex items-center justify-between pib-card px-4 py-3.5 hover:shadow-md transition-shadow">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-primary">settings</span> Configurações
            </span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
        <a href="/logout" class="flex items-center justify-between pib-card px-4 py-3.5 hover:shadow-md transition-shadow">
            <span class="flex items-center gap-3 text-sm font-semibold text-error">
                <span class="material-symbols-outlined text-[20px]">logout</span> Sair da conta
            </span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
    </div>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
