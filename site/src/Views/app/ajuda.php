<?php
$title = "Ajuda";
$activeNav = "perfil";

$faqs = [
    'Escalas e Presença' => [
        ['Como confirmo presença numa escala?', 'Abra a escala em <b>Escalas</b>, toque no card do culto e use os botões <b>Confirmar</b> ou <b>Recusar</b>. O líder vê sua resposta na hora.'],
        ['Como marco que não posso servir?', 'Vá em <b>Perfil → Indisponibilidades</b> e adicione o período (data de início e fim). A liderança considera isso ao montar as escalas.'],
    ],
    'Repertório e Cifras' => [
        ['Como mudo o tom de uma música?', 'Abra a música em <b>Repertório</b>, toque em <b>Cifra</b> e use os botões de transpor (+ / −) para subir ou descer o tom.'],
        ['O que é o modo palco?', 'É a visualização da cifra com letra grande, rolagem automática e fundo escuro — ideal para ler durante o culto.'],
    ],
    'Conta' => [
        ['Como altero minha senha?', 'Vá em <b>Perfil → Alterar senha</b>, informe a senha atual e a nova (mínimo 8 caracteres).'],
        ['Como atualizo meus dados ou foto?', 'Em <b>Perfil → Editar perfil</b> você atualiza nome, contato, instrumento e bio.'],
    ],
];

require __DIR__ . '/../layouts/head.php';
require __DIR__ . '/../layouts/top-app-bar.php';
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="mb-6">
        <a href="/configuracoes" class="text-primary text-sm font-bold flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Voltar
        </a>
        <h1 class="text-2xl font-bold text-on-surface">Ajuda &amp; FAQ</h1>
        <p class="text-sm text-on-surface-variant mt-1">Dúvidas frequentes sobre o uso do app.</p>
    </div>

    <?php foreach ($faqs as $group => $items): ?>
        <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2 ml-1"><?= htmlspecialchars($group) ?></p>
        <div class="space-y-2 mb-6">
            <?php foreach ($items as $item): ?>
                <details class="pib-card overflow-hidden group">
                    <summary class="flex items-center justify-between px-4 py-3.5 cursor-pointer list-none">
                        <span class="text-sm font-semibold text-on-surface pr-3"><?= htmlspecialchars($item[0]) ?></span>
                        <span class="material-symbols-outlined text-[20px] text-on-surface-variant transition-transform group-open:rotate-180">expand_more</span>
                    </summary>
                    <div class="px-4 pb-4 -mt-1 text-sm text-on-surface-variant leading-relaxed"><?= $item[1] ?></div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <!-- Contato -->
    <div class="pib-card p-6 text-center">
        <span class="material-symbols-outlined text-[32px] text-primary">support_agent</span>
        <h3 class="text-lg font-bold text-on-surface mt-2">Ainda precisa de ajuda?</h3>
        <p class="text-sm text-on-surface-variant mt-1 mb-4">Fale com a equipe de suporte do ministério.</p>
        <a href="https://wa.me/5535984529577" target="_blank" rel="noopener"
           class="btn-primary inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-bold">
            <span class="material-symbols-outlined text-[18px]">chat</span> Falar no WhatsApp
        </a>
    </div>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
