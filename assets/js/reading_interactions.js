// Reading Tab Interactive Functionality
// admin/leitura.php - Button interactions

document.addEventListener('DOMContentLoaded', function () {

    // 1. Checkbox functionality - Mark passage as read
    document.querySelectorAll('.passage-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const card = this.closest('.passage-card');
            const primaryBtn = card.querySelector('.btn-passage-primary');

            if (this.checked) {
                // Mark as complete
                card.classList.remove('status-unread');
                card.classList.add('status-complete');
                primaryBtn.innerHTML = '<i data-lucide="book-open" width="16"></i> Reler';

                // Show success toast
                showToast('✅ Passagem marcada como lida!');
            } else {
                // Mark as unread
                card.classList.remove('status-complete');
                card.classList.add('status-unread');
                primaryBtn.innerHTML = '<i data-lucide="book-open" width="16"></i> Começar Leitura';
            }

            // Reinitialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });

    // 2. Primary button - Open reading modal
    document.querySelectorAll('.btn-passage-primary').forEach(btn => {
        btn.addEventListener('click', function () {
            const card = this.closest('.passage-card');
            const reference = card.querySelector('.passage-title').textContent;
            openReadingModal(reference);
        });
    });
});

// Helper Functions

function openReadingModal(reference) {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'reading-modal-overlay';
    modal.innerHTML = `
        <div class="reading-modal">
            <div class="reading-modal-header">
                <h2>${reference}</h2>
                <button class="close-modal" onclick="this.closest('.reading-modal-overlay').remove()">
                    <i data-lucide="x" width="24"></i>
                </button>
            </div>
            <div class="reading-modal-content">
                <p style="color: var(--slate-600); text-align: center; padding: 2rem;">
                    Carregando texto bíblico...
                </p>
                <p style="color: var(--slate-500); font-size: 0.875rem; text-align: center;">
                    Em breve: integração com API bíblica
                </p>
            </div>
            <div class="reading-modal-footer">
                <button class="btn-modal btn-modal-secondary" onclick="this.closest('.reading-modal-overlay').remove()">
                    Fechar
                </button>
                <button class="btn-modal btn-modal-primary" onclick="markAsRead('${reference}')">
                    ✓ Marcar como Lido
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Initialize icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Add styles if not exists
    if (!document.getElementById('reading-modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'reading-modal-styles';
        styles.textContent = `
            .reading-modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(4px);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }
            .reading-modal {
                background: white;
                border-radius: 16px;
                max-width: 600px;
                width: 100%;
                max-height: 80vh;
                display: flex;
                flex-direction: column;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .reading-modal-header {
                padding: 1.5rem;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .reading-modal-header h2 {
                margin: 0;
                font-size: 1.25rem;
                font-weight: 700;
                color: var(--slate-800);
            }
            .close-modal {
                background: none;
                border: none;
                cursor: pointer;
                padding: 0.5rem;
                border-radius: 8px;
                transition: background 0.2s;
            }
            .close-modal:hover {
                background: var(--slate-100);
            }
            .reading-modal-content {
                padding: 1.5rem;
                overflow-y: auto;
                flex: 1;
            }
            .reading-modal-footer {
                padding: 1rem 1.5rem;
                border-top: 1px solid #e5e7eb;
                display: flex;
                gap: 0.75rem;
                justify-content: flex-end;
            }
            .btn-modal {
                padding: 0.625rem 1.25rem;
                border-radius: 8px;
                font-size: 0.875rem;
                font-weight: 600;
                border: none;
                cursor: pointer;
                transition: all 0.2s;
            }
            .btn-modal-primary {
                background: #16a34a;
                color: white;
            }
            .btn-modal-primary:hover {
                background: #15803d;
            }
            .btn-modal-secondary {
                background: var(--slate-100);
                color: var(--slate-700);
            }
            .btn-modal-secondary:hover {
                background: var(--slate-200);
            }
            body.dark-mode .reading-modal {
                background: var(--bg-surface);
            }
        `;
        document.head.appendChild(styles);
    }
}

function openNoteModal(reference) {
    const modal = document.createElement('div');
    modal.className = 'reading-modal-overlay';
    modal.innerHTML = `
        <div class="reading-modal">
            <div class="reading-modal-header">
                <h2>💭 Anotar - ${reference}</h2>
                <button class="close-modal" onclick="this.closest('.reading-modal-overlay').remove()">
                    <i data-lucide="x" width="24"></i>
                </button>
            </div>
            <div class="reading-modal-content">
                <textarea 
                    placeholder="Escreva suas reflexões sobre esta passagem..."
                    style="width: 100%; min-height: 200px; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-family: inherit; resize: vertical;"
                ></textarea>
            </div>
            <div class="reading-modal-footer">
                <button class="btn-modal btn-modal-secondary" onclick="this.closest('.reading-modal-overlay').remove()">
                    Cancelar
                </button>
                <button class="btn-modal btn-modal-primary" onclick="saveNote('${reference}', this)">
                    💾 Salvar Anotação
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function markAsRead(reference) {
    // Find the card and check the checkbox
    const cards = document.querySelectorAll('.passage-card');
    cards.forEach(card => {
        const title = card.querySelector('.passage-title').textContent;
        if (title === reference) {
            const checkbox = card.querySelector('.passage-checkbox');
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change'));
        }
    });

    // Close modal
    document.querySelector('.reading-modal-overlay').remove();
}

function saveNote(reference, btn) {
    const textarea = btn.closest('.reading-modal').querySelector('textarea');
    const note = textarea.value.trim();

    if (!note) {
        alert('Por favor, escreva uma anotação antes de salvar.');
        return;
    }

    // TODO: Save to database
    console.log('Saving note for', reference, ':', note);

    showToast('💾 Anotação salva com sucesso!');
    btn.closest('.reading-modal-overlay').remove();
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'reading-toast';
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 90px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--slate-800);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
        z-index: 10000;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: slideDown 0.3s ease;
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideUp 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

// Add animations
if (!document.getElementById('toast-animations')) {
    const animations = document.createElement('style');
    animations.id = 'toast-animations';
    animations.textContent = `
        @keyframes slideDown {
            from { opacity: 0; transform: translate(-50%, -20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }
        @keyframes slideUp {
            from { opacity: 1; transform: translate(-50%, 0); }
            to { opacity: 0; transform: translate(-50%, -20px); }
        }
    `;
    document.head.appendChild(animations);
}
