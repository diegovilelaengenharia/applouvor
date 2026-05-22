<?php
// admin/sugerir_musica.php
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../scripts/setup/init_db_suggestions.php'; // Garantir tabela

renderAppHeader('Sugerir Música', 'repertorio.php');
?>

<main class="max-w-[800px] mx-auto px-margin-mobile md:px-margin-desktop py-8 font-hanken mb-24 animate-scale-up">

    <!-- Header com Navegação -->
    <div class="flex items-center gap-4 mb-8">
        <a href="repertorio.php" class="w-10 h-10 bg-ghost-gray hover:bg-outline-variant/20 dark:bg-surface-variant/10 active:scale-95 border border-outline-variant/30 rounded-full flex items-center justify-center transition-all duration-200 shadow-sm">
            <i data-lucide="arrow-left" class="w-5 h-5 text-on-background"></i>
        </a>
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-on-background tracking-tight leading-tight">Sugerir Música</h1>
            <p class="text-xs md:text-sm text-secondary mt-1">Envie uma sugestão de nova canção para a liderança.</p>
        </div>
    </div>

    <!-- Card de Formulário Glassmorphic -->
    <div class="bg-white/80 dark:bg-deep-navy/80 backdrop-blur-xl border border-outline-variant/30 rounded-3xl p-6 md:p-8 shadow-2xl relative overflow-hidden">
        
        <form id="suggestForm" onsubmit="submitSuggestion(event)" class="space-y-6">
            
            <!-- Row 1: Nome e Artista -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label text-slate-600 dark:text-slate-400 font-bold mb-2 block text-sm">
                        Nome da Música <span class="text-worship-blue font-black">*</span>
                    </label>
                    <input type="text" name="title" required class="w-full h-12 px-4 bg-ghost-gray/30 border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 dark:bg-surface-variant/5 text-on-background transition-all placeholder:text-secondary/40 font-semibold" placeholder="Ex: Bondade de Deus">
                </div>

                <div class="form-group">
                    <label class="form-label text-slate-600 dark:text-slate-400 font-bold mb-2 block text-sm">
                        Artista / Banda <span class="text-worship-blue font-black">*</span>
                    </label>
                    <input type="text" name="artist" required class="w-full h-12 px-4 bg-ghost-gray/30 border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 dark:bg-surface-variant/5 text-on-background transition-all placeholder:text-secondary/40 font-semibold" placeholder="Ex: Isaías Saad">
                </div>
            </div>

            <!-- Row 2: Tom -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label text-slate-600 dark:text-slate-400 font-bold mb-2 block text-sm">Tom Sugerido (Opcional)</label>
                    <div class="relative">
                        <select name="tone" class="w-full h-12 pl-4 pr-10 bg-ghost-gray/30 border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 dark:bg-surface-variant/5 text-on-background transition-all appearance-none font-semibold">
                            <option value="">Selecione...</option>
                            <?php
                            $tones = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B', 'Cm', 'C#m', 'Dm', 'D#m', 'Em', 'Fm', 'F#m', 'Gm', 'G#m', 'Am', 'A#m', 'Bm'];
                            foreach ($tones as $t) {
                                echo "<option value='$t'>$t</option>";
                            }
                            ?>
                        </select>
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
                <div></div>
            </div>

            <!-- Row 3: Links -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-outline-variant/20 pt-6">
                <div class="form-group">
                    <label class="form-label text-slate-600 dark:text-slate-400 font-bold mb-2 block text-sm">Link de Referência no YouTube (Opcional)</label>
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center text-red-500 pointer-events-none">
                            <i data-lucide="youtube" class="w-4 h-4"></i>
                        </div>
                        <input type="url" name="youtube_link" class="w-full h-12 pl-11 pr-4 bg-ghost-gray/30 border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 dark:bg-surface-variant/5 text-on-background transition-all placeholder:text-secondary/40 font-semibold" placeholder="https://youtube.com/...">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label text-slate-600 dark:text-slate-400 font-bold mb-2 block text-sm">Link de Referência no Spotify (Opcional)</label>
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center text-emerald-500 pointer-events-none">
                            <i data-lucide="music-2" class="w-4 h-4"></i>
                        </div>
                        <input type="url" name="spotify_link" class="w-full h-12 pl-11 pr-4 bg-ghost-gray/30 border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 dark:bg-surface-variant/5 text-on-background transition-all placeholder:text-secondary/40 font-semibold" placeholder="https://spotify.com/...">
                    </div>
                </div>
            </div>

            <!-- Row 4: Motivo -->
            <div class="form-group border-t border-outline-variant/20 pt-6">
                <label class="form-label text-slate-600 dark:text-slate-400 font-bold mb-2 block text-sm">Por que sugere esta música? (Observação)</label>
                <textarea name="reason" rows="4" class="w-full p-4 bg-ghost-gray/30 border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 dark:bg-surface-variant/5 text-on-background transition-all placeholder:text-secondary/40 font-semibold resize-none" placeholder="Conte-nos por que essa música seria edificante para o ministério..."></textarea>
            </div>

            <!-- Botões de Ações -->
            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-outline-variant/20">
                <a href="repertorio.php" class="flex-1 h-12 rounded-xl border border-outline-variant/30 text-on-background font-bold text-sm hover:bg-ghost-gray/50 active:scale-98 transition-all flex items-center justify-center">
                    Cancelar
                </a>
                <button type="submit" class="flex-1 h-12 rounded-xl bg-worship-blue hover:bg-worship-blue-hover text-white font-bold text-sm active:scale-98 transition-all shadow-lg shadow-worship-blue/15 flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Enviar Sugestão</span>
                </button>
            </div>

        </form>
    </div>

</main>

<!-- Modal de Feedback (Sucesso/Erro) -->
<div id="feedbackModal" class="hidden fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white dark:bg-deep-navy border border-outline-variant/30 w-full max-w-sm rounded-3xl p-6 shadow-2xl transform scale-95 opacity-0 transition-all duration-300">
        <div class="text-center">
            <div id="feedbackIconContainer" class="w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4">
                <!-- Ícone via JS -->
            </div>
            <h3 id="feedbackTitle" class="font-extrabold text-lg text-on-background tracking-tight">Status</h3>
            <p id="feedbackText" class="text-xs text-secondary mt-2 leading-relaxed font-semibold">Mensagem descritiva.</p>
            <button onclick="closeFeedback()" class="mt-6 w-full h-12 rounded-xl bg-worship-blue hover:bg-worship-blue-hover text-white font-bold text-sm active:scale-98 transition-all shadow-lg shadow-worship-blue/15">
                Entendido
            </button>
        </div>
    </div>
</div>

<script>
let isSuccess = false;

function showFeedback(success, title, message) {
    isSuccess = success;
    const modal = document.getElementById('feedbackModal');
    const content = modal.querySelector('div');
    const iconContainer = document.getElementById('feedbackIconContainer');
    const fTitle = document.getElementById('feedbackTitle');
    const fText = document.getElementById('feedbackText');

    fTitle.textContent = title;
    fText.textContent = message;

    if (success) {
        iconContainer.className = "w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20";
        iconContainer.innerHTML = '<i data-lucide="check" class="w-8 h-8"></i>';
    } else {
        iconContainer.className = "w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4 bg-rose-500/10 text-rose-500 border border-rose-500/20";
        iconContainer.innerHTML = '<i data-lucide="alert-triangle" class="w-8 h-8"></i>';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    lucide.createIcons();

    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeFeedback() {
    const modal = document.getElementById('feedbackModal');
    const content = modal.querySelector('div');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        if (isSuccess) {
            window.location.href = 'repertorio.php';
        }
    }, 200);
}

async function submitSuggestion(e) {
    e.preventDefault();
    
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    const originalContent = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i><span>Enviando...</span>';
    lucide.createIcons();

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('sugestoes_api.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showFeedback(true, 'Sugestão Enviada!', 'Sua sugestão de música foi enviada com sucesso para a moderação da liderança.');
        } else {
            showFeedback(false, 'Ops!', result.message || 'Houve um problema ao enviar a sugestão.');
            btn.disabled = false;
            btn.innerHTML = originalContent;
            lucide.createIcons();
        }
    } catch (error) {
        console.error(error);
        showFeedback(false, 'Erro de Conexão', 'Não foi possível se conectar ao servidor. Verifique sua conexão e tente novamente.');
        btn.disabled = false;
        btn.innerHTML = originalContent;
        lucide.createIcons();
    }
}

lucide.createIcons();
</script>

<?php renderAppFooter(); ?>
