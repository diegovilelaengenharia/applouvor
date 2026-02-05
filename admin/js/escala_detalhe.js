// admin/js/escala_detalhe.js
let editMode = false;
let pendingMembers = new Set(); // IDs dos membros selecionados
let pendingSongs = new Set();   // IDs das músicas selecionadas

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
        const manageInfoBtn = document.getElementById('btn-manage-info');

        if (!editBtn || !viewMode || !editModeEl) {
            console.error('Elementos UI críticos não encontrados');
            console.log('editBtn:', editBtn, 'viewMode:', viewMode, 'editModeEl:', editModeEl);
            return;
        }

        if (editMode) {
            // Entrar no modo edição
            viewMode.classList.add('view-mode-hidden');
            editModeEl.classList.remove('edit-mode-hidden');

            // Garantir styles manuais para override
            viewMode.style.display = 'none';
            editModeEl.style.display = 'block';

            // Inicializar sets com membros e músicas atuais
            initializePendingData();

            // Mostrar botão Salvar e Gerenciar
            if (saveBtn) saveBtn.style.display = 'flex';
            if (manageInfoBtn) manageInfoBtn.style.display = 'flex';

            // Mudar botão Editar para Cancelar (vermelho)
            editBtn.style.background = '#ef4444';
            editBtn.style.boxShadow = '0 2px 8px rgba(239, 68, 68, 0.3)';
            editBtn.style.color = 'white';
            editBtn.innerHTML = '<i data-lucide="x" style="width: 16px;"></i><span>Cancelar</span>';
        } else {
            // Sair do modo edição (cancelar)
            viewMode.classList.remove('view-mode-hidden');
            editModeEl.classList.add('edit-mode-hidden');

            // Garantir styles manuais
            viewMode.style.display = 'block';
            editModeEl.style.display = 'none';

            // Esconder botão Salvar e Gerenciar
            if (saveBtn) saveBtn.style.display = 'none';
            if (manageInfoBtn) manageInfoBtn.style.display = 'none';

            // Restaurar botão Editar (amarelo)
            editBtn.style.background = '#fbbf24';
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

function initializePendingData() {
    // Inicializar com membros atuais
    pendingMembers.clear();
    document.querySelectorAll('[id^="member-chip-"]').forEach(chip => {
        const userId = chip.id.replace('member-chip-', '');
        pendingMembers.add(parseInt(userId));
    });

    // Inicializar com músicas atuais
    pendingSongs.clear();
    document.querySelectorAll('[id^="song-chip-"]').forEach(chip => {
        const songId = chip.id.replace('song-chip-', '');
        pendingSongs.add(parseInt(songId));
    });

    console.log('Dados inicializados:', { members: Array.from(pendingMembers), songs: Array.from(pendingSongs) });
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
        // Modo modal
        const label = checkbox.closest('.member-filter-item');
        const userName = label.querySelector('div > div').textContent;
        const userInitial = userName.charAt(0).toUpperCase();
        const avatarColor = label.querySelector('div[style*="border-radius: 50%"]').style.background;

        if (checkbox.checked) {
            label.style.borderColor = 'var(--primary)';
            pendingMembers.add(userId);
            addMemberChip(userId, userName, userInitial, avatarColor);
        } else {
            label.style.borderColor = 'var(--border-color)';
            pendingMembers.delete(userId);
            removeMemberChip(userId);
        }
        updateMemberCount();
    } else {
        // Modo resumo (clique no X do chip)
        pendingMembers.delete(userId);
        updateMemberCount();
    }
}

function addMemberChip(userId, userName, userInitial, avatarColor) {
    const editModeDiv = document.getElementById('edit-mode');
    if (!editModeDiv) return;

    const participantesCard = editModeDiv.querySelector('div[style*="background: var(--bg-surface)"]');
    if (!participantesCard) return;

    const container = participantesCard.querySelector('div[style*="flex-wrap"]');
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
    const count = pendingMembers.size;
    const editModeDiv = document.getElementById('edit-mode');
    if (!editModeDiv) return;

    const participantesCard = editModeDiv.querySelector('div[style*="background: var(--bg-surface)"]');
    if (!participantesCard) return;

    const badge = participantesCard.querySelector('span[style*="border-radius: 20px"]');
    if (badge) badge.textContent = `${count} selecionados`;
}

function toggleSong(songId, checkbox) {
    if (checkbox) {
        // Modo modal
        const label = checkbox.closest('.song-filter-item');
        const songTitle = label.querySelector('div > div:first-child').textContent;
        const songArtist = label.querySelector('div > div:last-child').textContent;

        if (checkbox.checked) {
            label.style.borderColor = 'var(--primary)';
            pendingSongs.add(songId);
            addSongChip(songId, songTitle, songArtist);
        } else {
            label.style.borderColor = 'var(--border-color)';
            pendingSongs.delete(songId);
            removeSongChip(songId);
        }
        updateSongCount();
    } else {
        // Modo resumo (clique no X)
        pendingSongs.delete(songId);
        updateSongCount();
    }
}

function addSongChip(songId, songTitle, songArtist) {
    const editModeDiv = document.getElementById('edit-mode');
    if (!editModeDiv) return;

    const cards = editModeDiv.querySelectorAll('div[style*="background: var(--bg-surface)"]');
    const repertorioCard = cards[cards.length - 1];
    if (!repertorioCard) return;

    const container = repertorioCard.querySelector('div[style*="flex-direction: column"]');
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
    const count = pendingSongs.size;
    const editModeDiv = document.getElementById('edit-mode');
    if (!editModeDiv) return;

    const cards = editModeDiv.querySelectorAll('div[style*="background: var(--bg-surface)"]');
    const repertorioCard = cards[cards.length - 1];
    if (!repertorioCard) return;

    const badge = repertorioCard.querySelector('span[style*="border-radius: 20px"]');
    if (badge) badge.textContent = `${count} selecionadas`;
}

function saveAllChanges() {
    console.log('Salvando mudanças:', { members: Array.from(pendingMembers), songs: Array.from(pendingSongs) });

    // Feedback visual de carregamento
    const saveBtn = document.getElementById('saveBtn');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.style.opacity = '0.7';
        saveBtn.style.cursor = 'wait';
        saveBtn.innerHTML = '<i data-lucide="loader-2" class="spin-icon"></i><span>Salvando...</span>';

        // Adicionar estilo da animação se não existir
        if (!document.getElementById('spinner-style')) {
            const style = document.createElement('style');
            style.id = 'spinner-style';
            style.textContent = `
                @keyframes spin { to { transform: rotate(360deg); } }
                .spin-icon { animation: spin 1s linear infinite; }
            `;
            document.head.appendChild(style);
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // Criar formulário e enviar
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `escala_detalhe.php?id=${window.SCHEDULE_ID}`;

    // Adicionar membros
    pendingMembers.forEach(userId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'members[]';
        input.value = userId;
        form.appendChild(input);
    });

    // Adicionar músicas
    pendingSongs.forEach(songId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'songs[]';
        input.value = songId;
        form.appendChild(input);
    });

    // Adicionar dados da escala (Do Modal)
    // Se o usuário não abriu o modal, precisamos pegar os valores originais do display ou hidden inputs se existissem.
    // Mas como removemos os hidden inputs do PHP, precisamos confiar que os inputs do modal estão lá (eles são criados no HTML do PHP).
    const eventName = document.getElementById('modal-event-name').value;
    const eventDate = document.getElementById('modal-event-date').value;
    const eventTime = document.getElementById('modal-event-time').value;
    const eventNotes = document.getElementById('modal-event-notes').value;

    const inputName = document.createElement('input');
    inputName.type = 'hidden';
    inputName.name = 'event_type';
    inputName.value = eventName;
    form.appendChild(inputName);

    const inputDate = document.createElement('input');
    inputDate.type = 'hidden';
    inputDate.name = 'event_date';
    inputDate.value = eventDate;
    form.appendChild(inputDate);

    const inputTime = document.createElement('input');
    inputTime.type = 'hidden';
    inputTime.name = 'event_time';
    inputTime.value = eventTime;
    form.appendChild(inputTime);

    const inputNotes = document.createElement('input');
    inputNotes.type = 'hidden';
    inputNotes.name = 'notes';
    inputNotes.value = eventNotes;
    form.appendChild(inputNotes);

    // Adicionar flag de salvamento
    const saveFlag = document.createElement('input');
    saveFlag.type = 'hidden';
    saveFlag.name = 'save_changes';
    saveFlag.value = '1';
    form.appendChild(saveFlag);

    document.body.appendChild(form);
    form.submit();
}

function updateEventInfoFromModal() {
    // Pegar valores do modal
    const name = document.getElementById('modal-event-name').value;
    const date = document.getElementById('modal-event-date').value;
    const time = document.getElementById('modal-event-time').value;
    const notes = document.getElementById('modal-event-notes').value;

    // Atualizar UI
    document.getElementById('display-event-name').textContent = name;

    // Formatar data para exibição (Simples dia/mês/ano)
    if (date) {
        const parts = date.split('-');
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        const diaSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][d.getDay()];
        document.getElementById('display-event-date').textContent = `${diaSemana}, ${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    // Horário
    document.getElementById('display-event-time').textContent = time;

    // Notas
    const notesContainer = document.getElementById('display-notes-container');
    const notesText = document.getElementById('display-notes-text');
    if (notes) {
        notesContainer.style.display = 'block';
        notesText.innerHTML = notes.replace(/\n/g, '<br>'); // Simples nl2br
    } else {
        notesContainer.style.display = 'none';
        notesText.textContent = '';
    }

    closeModal('modal-event');
}
