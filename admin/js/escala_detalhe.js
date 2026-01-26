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
        fetchAction('toggle_member', userId, () => {
            if (checkbox.checked) label.style.borderColor = 'var(--primary)';
            else label.style.borderColor = 'var(--border-color)';
        });
    } else {
        fetchAction('toggle_member', userId, null);
    }
}

function toggleSong(songId, checkbox) {
    if (checkbox) {
        const label = checkbox.closest('.song-filter-item');
        fetchAction('toggle_song', songId, () => {
            if (checkbox.checked) label.style.borderColor = 'var(--primary)';
            else label.style.borderColor = 'var(--border-color)';
        });
    } else {
        fetchAction('toggle_song', songId, null);
    }
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
