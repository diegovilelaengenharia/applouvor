/**
 * Devotional Enhancements JavaScript
 * Handles reactions, reading mode, WhatsApp sharing, and advanced filters
 */

// ==========================================
// SISTEMA DE REAÇÕES
// ==========================================

async function toggleReaction(devotionalId, reactionType) {
    const btn = event.currentTarget;

    try {
        const response = await fetch('../api/devotional_reactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                devotional_id: devotionalId,
                reaction_type: reactionType
            })
        });

        const data = await response.json();

        if (data.success) {
            // Update counters
            updateReactionUI(devotionalId, data.counts, data.user_reactions);

            // Visual feedback
            if (data.action === 'added') {
                btn.classList.add('reacted');
                btn.style.transform = 'scale(1.2)';
                setTimeout(() => btn.style.transform = 'scale(1)', 200);
            } else {
                btn.classList.remove('reacted');
            }
        }
    } catch (error) {
        console.error('Error toggling reaction:', error);
    }
}

function updateReactionUI(devotionalId, counts, userReactions) {
    const container = document.querySelector(`#reactions-${devotionalId}`);
    if (!container) return;

    // Update each reaction count
    ['amen', 'prayer', 'inspired'].forEach(type => {
        const countEl = container.querySelector(`.count-${type}`);
        const btnEl = container.querySelector(`.btn-${type}`);

        if (countEl) {
            const count = counts[`${type}_count`] || 0;
            countEl.textContent = count > 0 ? count : '';
        }

        if (btnEl) {
            if (userReactions.includes(type)) {
                btnEl.classList.add('reacted');
            } else {
                btnEl.classList.remove('reacted');
            }
        }
    });
}

// Init reactions on page load
document.addEventListener('DOMContentLoaded', async function () {
    const devotionalCards = document.querySelectorAll('[data-devotional-id]');

    for (const card of devotionalCards) {
        const devotionalId = card.dataset.devotionalId;

        try {
            const response = await fetch(`../api/devotional_reactions.php?devotional_id=${devotionalId}`);
            const data = await response.json();

            if (data.success) {
                updateReactionUI(devotionalId, data.counts, data.user_reactions);
            }
        } catch (error) {
            console.error('Error loading reactions:', error);
        }
    }
});

// ==========================================
// COMPARTILHAMENTO WHATSAPP
// ==========================================

function shareWhatsApp(devotionalId, title) {
    // Pegar o conteúdo do devocional
    const devotionalCard = document.querySelector(`#dev-${devotionalId}`);
    if (!devotionalCard) return;

    const contentEl = devotionalCard.querySelector('.dev-text');
    const authorEl = devotionalCard.querySelector('.dev-author-name');

    // Extrair preview do conteúdo (primeiros 150 caracteres)
    let preview = '';
    if (contentEl) {
        const textContent = contentEl.textContent || contentEl.innerText;
        preview = textContent.substring(0, 150).trim();
        if (textContent.length > 150) {
            preview += '...';
        }
    }

    const author = authorEl ? authorEl.textContent : 'Louvor PIB Oliveira';
    const url = window.location.origin + window.location.pathname + '?id=' + devotionalId;

    // Mensagem formatada e bonita
    const message = `✨ *Devocional da Comunidade* ✨

📖 *${title}*
👤 _Por ${author}_

${preview}

━━━━━━━━━━━━━━━━━━
🔗 Leia completo aqui:
${url}

🙏 _Compartilhe a Palavra!_
_Louvor PIB Oliveira_ 💙`;

    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;

    window.open(whatsappUrl, '_blank');
}

// ==========================================
// MODO LEITURA FOCADO
// ==========================================

let currentFontSize = 16; // px

function openReadingMode(devotionalId) {
    const devotional = document.querySelector(`#dev-${devotionalId}`);
    if (!devotional) return;

    const title = devotional.querySelector('.dev-title').textContent;
    const content = devotional.querySelector('.dev-text').innerHTML;
    const author = devotional.querySelector('.dev-author-name').textContent;

    // Load saved font size preference
    const savedSize = localStorage.getItem('devotional_font_size');
    if (savedSize) {
        currentFontSize = parseInt(savedSize);
    }

    // Create modal
    const modal = document.createElement('div');
    modal.id = 'reading-mode-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #fff;
        z-index: 9999;
        overflow-y: auto;
        animation: fadeIn 0.3s;
    `;

    modal.innerHTML = `
        <div style="max-width: 700px; margin: 0 auto; padding: 20px;">
            <!-- Header Controls -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb;">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button onclick="adjustFontSize(-2)" style="
                        background: #f3f4f6;
                        border: 1px solid #d1d5db;
                        border-radius: 8px;
                        padding: 8px 12px;
                        cursor: pointer;
                        font-weight: 600;
                        font-size: 14px;
                    ">A-</button>
                    <span id="font-size-display" style="font-size: 14px; color: #6b7280; font-weight: 600;">
                        ${currentFontSize}px
                    </span>
                    <button onclick="adjustFontSize(2)" style="
                        background: #f3f4f6;
                        border: 1px solid #d1d5db;
                        border-radius: 8px;
                        padding: 8px 12px;
                        cursor: pointer;
                        font-weight: 600;
                        font-size: 14px;
                    ">A+</button>
                </div>
                
                <button onclick="closeReadingMode()" style="
                    background: none;
                    border: none;
                    color: #6b7280;
                    cursor: pointer;
                    padding: 8px;
                ">
                    <i data-lucide="x" style="width: 24px; height: 24px;"></i>
                </button>
            </div>
            
            <!-- Content -->
            <div id="reading-content">
                <h1 style="font-size: 2rem; font-weight: 800; color: #111827; margin-bottom: 8px; line-height: 1.2;">
                    ${title}
                </h1>
                <p style="color: #6b7280; font-size: 0.95rem; margin-bottom: 30px;">
                    Por ${author}
                </p>
                <div id="reading-text" style="
                    font-size: ${currentFontSize}px;
                    line-height: 1.8;
                    color: #374151;
                    font-family: Georgia, serif;
                ">
                    ${content}
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Re-render Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function adjustFontSize(delta) {
    currentFontSize = Math.max(12, Math.min(24, currentFontSize + delta));

    const textEl = document.getElementById('reading-text');
    const displayEl = document.getElementById('font-size-display');

    if (textEl) {
        textEl.style.fontSize = currentFontSize + 'px';
    }

    if (displayEl) {
        displayEl.textContent = currentFontSize + 'px';
    }

    // Save preference
    localStorage.setItem('devotional_font_size', currentFontSize);
}

function closeReadingMode() {
    const modal = document.getElementById('reading-mode-modal');
    if (modal) {
        modal.style.animation = 'fadeOut 0.2s';
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = 'auto';
        }, 200);
    }
}

// ==========================================
// FILTROS AVANÇADOS
// ==========================================

function toggleAdvancedFilters() {
    const panel = document.getElementById('advanced-filters-panel');
    if (panel) {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }
}

function clearAllFilters() {
    window.location.href = 'devocionais.php';
}

function hasActiveFilters() {
    const params = new URLSearchParams(window.location.search);
    return params.has('author') || params.has('date_from') || params.has('date_to') ||
        params.has('verse') || params.has('series') || params.has('search');
}

// Show active filters indicator
document.addEventListener('DOMContentLoaded', function () {
    if (hasActiveFilters()) {
        const indicator = document.getElementById('active-filters-indicator');
        if (indicator) {
            indicator.style.display = 'inline-block';
        }

        // Auto-open advanced filters if any advanced filter is active
        const params = new URLSearchParams(window.location.search);
        if (params.has('author') || params.has('date_from') || params.has('verse')) {
            toggleAdvancedFilters();
        }
    }
});

// ==========================================
// ANIMATIONS
// ==========================================

const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(style);
