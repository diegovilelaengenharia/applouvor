// admin/js/escala_detalhe.js
let editMode = false;

function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function toggleEditMode() {
    console.log('Toggle Edit Mode acionado');
    try {
        editMode = !editMode;
        const editBtn = document.getElementById('editBtn');
        const saveBtn = document.getElementById('saveBtn');
        const viewMode = document.getElementById('view-mode');
        const editModeEl = document.getElementById('edit-mode');

        if (!editBtn || !viewMode || !editModeEl) {
            console.error('Elementos UI críticos não encontrados');
            return;
        }

        if (editMode) {
            // Entrar no modo edição
            viewMode.classList.add('view-mode-hidden');
            editModeEl.classList.remove('edit-mode-hidden');

            // Mostrar botão Salvar
            if (saveBtn) {
                saveBtn.style.display = 'flex';
            }

            // Mudar botão Editar para Cancelar (vermelho)
            editBtn.style.background = '#ef4444';
            editBtn.style.boxShadow = '0 2px 8px rgba(239, 68, 68, 0.3)';
            editBtn.style.color = 'white';
            editBtn.innerHTML = '<i data-lucide="x" style="width: 16px;"></i><span>Cancelar</span>';
        } else {
            // Sair do modo edição
            viewMode.classList.remove('view-mode-hidden');
            editModeEl.classList.add('edit-mode-hidden');

            // Esconder botão Salvar
            if (saveBtn) {
                saveBtn.style.display = 'none';
            }

            // Restaurar botão Editar (amarelo)
            editBtn.style.background = 'linear-gradient(135deg, #fbbf24, #f59e0b)';
            editBtn.style.boxShadow = '0 2px 8px rgba(251, 191, 36, 0.3)';
            editBtn.style.color = 'white';
            editBtn.innerHTML = '<i data-lucide="edit-2" style="width: 16px;"></i><span>Editar</span>';

            setTimeout(() => window.location.reload(), 50);
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (err) {
        console.error('Erro no toggleEditMode:', err);
        alert('Erro ao ativar edição. Verifique o console.');
    }
}

function filterMembers() {
    const search = document.getElementById('searchMembers').value.toLowerCase();
    const items = document.querySelectorAll('.member-filter-item');
    items.forEach(item => {
        const name = item.getAttribute('data-name');
        item.style.display = name.includes(search) ? 'flex' : 'none';
    });
}

function filterSongs() {
    const search = document.getElementById('searchSongs').value.toLowerCase();
    const items = document.querySelectorAll('.song-filter-item');
    items.forEach(item => {
        const title = item.getAttribute('data-title');
        const artist = item.getAttribute('data-artist');
        item.style.display = (title.includes(search) || artist.includes(search)) ? 'flex' : 'none';
    });
}

function toggleMember(userId, checkbox) {
    if (checkbox) {
        const label = checkbox.closest('.member-filter-item');
        const userName = label.querySelector('div > div').textContent;
        const userInitial = userName.charAt(0).toUpperCase();
        const avatarColor = label.querySelector('div[style*="border-radius: 50%"]').style.background;

        fetchAction('toggle_member', userId, () => {
            if (checkbox.checked) {
                label.style.borderColor = 'var(--primary)';
                // Adicionar chip na lista de participantes
                addMemberChip(userId, userName, userInitial, avatarColor);
            } else {
                label.style.borderColor = 'var(--border-color)';
                // Remover chip da lista de participantes
                removeMemberChip(userId);
            }
            updateMemberCount();
        });
    } else {
        // Modo resumo (clique no X do chip)
        fetchAction('toggle_member', userId, null);
    }
}

function addMemberChip(userId, userName, userInitial, avatarColor) {
    const container = document.querySelector('#edit-mode .edit-mode-hidden + div[style*="flex-wrap"]');
    if (!container) return;

    // Verificar se já existe
    if (document.getElementById(`member-chip-${userId}`)) return;

    const chip = document.createElement('div');
    chip.id = `member-chip-${userId}`;
    chip.style.cssText = 'display: flex; align-items: center; gap: 8px; padding: 6px 12px; background: var(--bg-body); border-radius: 10px; border: 1px solid var(--border-color);';
    chip.innerHTML = `
        <div style="width: 24px; height: 24px; border-radius: 50%; background: ${avatarColor}; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;">
            ${userInitial}
        </div>
        <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);">${userName}</span>
        <button onclick="toggleMember(${userId}, null); this.parentElement.remove(); updateMemberCount();" style="border: none; background: none; color: #ef4444; cursor: pointer; padding: 0 0 0 4px; display: flex;"><i data-lucide="x" style="width: 14px;"></i></button>
    `;
    container.appendChild(chip);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function removeMemberChip(userId) {
    const chip = document.getElementById(`member-chip-${userId}`);
    if (chip) chip.remove();
}

function updateMemberCount() {
    const count = document.querySelectorAll('[id^="member-chip-"]').length;
    const badge = document.querySelector('#edit-mode h3 + span');
    if (badge) badge.textContent = `${count} selecionados`;
}

function toggleSong(songId, checkbox) {
    if (checkbox) {
        const label = checkbox.closest('.song-filter-item');
        const songTitle = label.querySelector('div > div:first-child').textContent;
        const songArtist = label.querySelector('div > div:last-child').textContent;

        fetchAction('toggle_song', songId, () => {
            if (checkbox.checked) {
                label.style.borderColor = 'var(--primary)';
                // Adicionar música na lista
                addSongChip(songId, songTitle, songArtist);
            } else {
                label.style.borderColor = 'var(--border-color)';
                // Remover música da lista
                removeSongChip(songId);
            }
            updateSongCount();
        });
    } else {
        // Modo resumo (clique no X)
        fetchAction('toggle_song', songId, null);
    }
}

function addSongChip(songId, songTitle, songArtist) {
    const container = document.querySelector('#edit-mode > div:last-child > div[style*="flex-direction: column"]');
    if (!container) return;

    // Verificar se já existe
    if (document.getElementById(`song-chip-${songId}`)) return;

    const currentCount = document.querySelectorAll('[id^="song-chip-"]').length;
    const chip = document.createElement('div');
    chip.id = `song-chip-${songId}`;
    chip.style.cssText = 'display: flex; align-items: center; gap: 12px; padding: 10px; background: var(--bg-body); border-radius: 10px; border: 1px solid var(--border-color);';
    chip.innerHTML = `
        <div style="width: 24px; height: 24px; background: #ddd; color: #555; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem;">${currentCount + 1}</div>
        <div style="flex: 1;">
            <div style="font-weight: 600; color: var(--text-main); font-size: 0.95rem;">${songTitle}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">${songArtist}</div>
        </div>
        <button onclick="toggleSong(${songId}, null); this.parentElement.remove(); updateSongCount();" style="border: none; background: none; color: #ef4444; cursor: pointer; padding: 4px; display: flex;"><i data-lucide="trash-2" style="width: 16px;"></i></button>
    `;
    container.appendChild(chip);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function removeSongChip(songId) {
    const chip = document.getElementById(`song-chip-${songId}`);
    if (chip) {
        chip.remove();
        // Renumerar os chips restantes
        document.querySelectorAll('[id^="song-chip-"]').forEach((c, idx) => {
            const numberDiv = c.querySelector('div:first-child');
            if (numberDiv) numberDiv.textContent = idx + 1;
        });
    }
}

function updateSongCount() {
    const count = document.querySelectorAll('[id^="song-chip-"]').length;
    const badge = document.querySelector('#edit-mode > div:last-child h3 + span');
    if (badge) badge.textContent = `${count} selecionadas`;
}

function fetchAction(action, id, callback) {
    const param = action === 'toggle_member' ? 'user_id' : 'song_id';

    const formData = new URLSearchParams();
    formData.append('ajax', '1');
    formData.append('action', action);
    formData.append(param, id);

    fetch('escala_detalhe.php?id=' + window.SCHEDULE_ID, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
        .then(r => r.json())
        .then(data => {
            if (callback) callback();
        })
        .catch(err => console.error('Erro na requisição AJAX:', err));
}
