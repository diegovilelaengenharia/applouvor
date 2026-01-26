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
        const viewMode = document.getElementById('view-mode');
        const editModeEl = document.getElementById('edit-mode');
        const saveBar = document.getElementById('save-changes-bar');

        if (!editBtn || !viewMode || !editModeEl) {
            console.error('Elementos UI críticos não encontrados');
            return;
        }

        if (editMode) {
            viewMode.classList.add('view-mode-hidden');
            editModeEl.classList.remove('edit-mode-hidden');
            if (saveBar) {
                saveBar.classList.remove('edit-mode-hidden');
                saveBar.style.display = 'block';
            }

            editBtn.style.background = '#ef4444';
            editBtn.style.borderColor = '#ef4444';
            editBtn.style.color = 'white';
            editBtn.innerHTML = '<i data-lucide="x" style="width: 16px;"></i><span>Cancelar</span>';
        } else {
            viewMode.classList.remove('view-mode-hidden');
            editModeEl.classList.add('edit-mode-hidden');
            if (saveBar) {
                saveBar.classList.add('edit-mode-hidden');
                saveBar.style.display = 'none';
            }

            editBtn.style.background = 'var(--bg-body)';
            editBtn.style.borderColor = 'var(--border-color)';
            editBtn.style.color = 'var(--text-main)';
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
