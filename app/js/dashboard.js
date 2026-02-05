// ===============================
// GLOBAL STATE
// ===============================
var lastScrollY = 0;
var SCROLL_THRESHOLD = 40;
var selectedFile = null;
var currentBoardId = null;
var currentRatingIcon = '⭐';
var selectedIcon = '⭐';

var ICON_OPTIONS = [
    '⭐','🔥','🍆','💃','🤙','😏',
    '💋','🎯','🏆','👀','🌶️','💎',
    '🍑','😈','🦄','🫦'
];

// ===============================
// ELEMENTS
// ===============================
var storiesBar = document.getElementById('storiesBar');
var fab = document.querySelector('.fab');
var modal = document.getElementById('addModal');
var bottomNavButtons = document.querySelectorAll('.nav-btn');
var photoGrid = document.getElementById('photoGrid');
var membersBar = document.getElementById('membersBar');

// Upload
var uploadStep1 = document.getElementById('uploadStep1');
var uploadStep2 = document.getElementById('uploadStep2');
var uploadStep3 = document.getElementById('uploadStep3');
var photoInput = document.getElementById('photoInput');
var photoPreview = document.getElementById('photoPreview');
var captionInput = document.getElementById('captionInput');
var confirmUpload = document.getElementById('confirmUpload');
var cancelUpload = document.getElementById('cancelUpload');

// Boards
var navHome = document.getElementById('navHome');
var boardsPanel = document.getElementById('boardsPanel');
var boardsList = document.getElementById('boardsList');
var closeBoardsPanelBtn = document.getElementById('closeBoardsPanel');
var toggleCreateBoard = document.getElementById('toggleCreateBoard');
var createBoardForm = document.getElementById('createBoardForm');
var newBoardTitle = document.getElementById('newBoardTitle');
var newBoardDesc = document.getElementById('newBoardDesc');
var createBoardBtn = document.getElementById('createBoardBtn');
var cancelCreateBoard = document.getElementById('cancelCreateBoard');
var iconGrid = document.getElementById('iconGrid');
var customIcon = document.getElementById('customIcon');
var iconPreview = document.getElementById('iconPreview');

// ===============================
// INIT
// ===============================
document.addEventListener('DOMContentLoaded', function() {
    currentBoardId = sessionStorage.getItem('currentBoardId');
    if (currentBoardId) {
        currentBoardId = parseInt(currentBoardId);
        loadBoard();
    } else {
        openBoardsPanel();
    }
    buildIconGrid();
});

function loadBoard() {
    if (!currentBoardId) return;
    storiesBar.style.display = '';
    loadPhotos();
    loadBoardMembers();
}

// ===============================
// SCROLL — Hide/show header
// ===============================
window.addEventListener('scroll', function() {
    var sy = window.scrollY;
    if (!storiesBar) return;
    if (sy > lastScrollY && sy > SCROLL_THRESHOLD) {
        storiesBar.style.transform = 'translateY(-110%)';
        storiesBar.style.opacity = '0';
    } else {
        storiesBar.style.transform = 'translateY(0)';
        storiesBar.style.opacity = '1';
    }
    lastScrollY = sy;
});

// ===============================
// BOARDS PANEL
// ===============================
if (navHome) {
    navHome.addEventListener('click', function(e) {
        e.stopPropagation();
        openBoardsPanel();
    });
}
if (closeBoardsPanelBtn) {
    closeBoardsPanelBtn.addEventListener('click', function() {
        closeBoardsPanel();
    });
}

function openBoardsPanel() {
    loadBoardsList();
    boardsPanel.style.display = '';
    document.body.style.overflow = 'hidden';
    // Reset create form
    if (createBoardForm) createBoardForm.style.display = 'none';
    if (toggleCreateBoard) toggleCreateBoard.style.display = '';
}

function closeBoardsPanel() {
    boardsPanel.style.display = 'none';
    document.body.style.overflow = '';
}

// ===============================
// BOARDS LIST
// ===============================
function loadBoardsList() {
    boardsList.innerHTML = '<div class="boards-loading-state"><div class="spinner"></div></div>';

    fetch('/app/api/get-boards.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            boardsList.innerHTML = '';

            if (!data.boards || data.boards.length === 0) {
                boardsList.innerHTML =
                    '<div class="boards-empty-state">' +
                    '<div class="boards-empty-icon">📋</div>' +
                    '<h3>Sin tableros aún</h3>' +
                    '<p>Crea tu primer tablero para empezar a jugar con tus amigos.</p>' +
                    '</div>';
                return;
            }

            data.boards.forEach(function(board) {
                var isActive = currentBoardId && parseInt(board.id) === currentBoardId;
                var icon = board.rating_icon || '⭐';
                var desc = board.description || 'Sin descripción';
                var members = parseInt(board.member_count);
                var role = board.role === 'owner' ? 'Creador' : (board.role === 'admin' ? 'Admin' : 'Miembro');

                var card = document.createElement('div');
                card.className = 'board-card' + (isActive ? ' board-card-active' : '');
                card.innerHTML =
                    '<div class="board-card-top">' +
                        '<span class="board-card-icon">' + icon + '</span>' +
                        '<div class="board-card-info">' +
                            '<span class="board-card-title">' + escapeHtml(board.title) + '</span>' +
                            '<span class="board-card-desc">' + escapeHtml(desc) + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="board-card-bottom">' +
                        '<span class="board-card-members">' + members + ' miembro' + (members !== 1 ? 's' : '') + '</span>' +
                        '<span class="board-card-role">' + role + '</span>' +
                    '</div>' +
                    '<div class="board-card-actions">' +
                        '<button class="board-card-enter">Entrar</button>' +
                        '<button class="board-card-invite">Invitar</button>' +
                    '</div>';

                // Enter board
                card.querySelector('.board-card-enter').addEventListener('click', function() {
                    selectBoard(parseInt(board.id));
                });

                // Invite
                card.querySelector('.board-card-invite').addEventListener('click', function(e) {
                    e.stopPropagation();
                    shareInviteLink(parseInt(board.id));
                });

                boardsList.appendChild(card);
            });
        })
        .catch(function() {
            boardsList.innerHTML =
                '<div class="boards-empty-state">' +
                '<p>No se pudieron cargar los tableros.</p>' +
                '</div>';
        });
}

function selectBoard(boardId) {
    currentBoardId = boardId;
    sessionStorage.setItem('currentBoardId', boardId);
    closeBoardsPanel();
    loadBoard();
}

// ===============================
// CREATE BOARD
// ===============================
if (toggleCreateBoard) {
    toggleCreateBoard.addEventListener('click', function() {
        toggleCreateBoard.style.display = 'none';
        createBoardForm.style.display = '';
        newBoardTitle.focus();
    });
}
if (cancelCreateBoard) {
    cancelCreateBoard.addEventListener('click', function() {
        createBoardForm.style.display = 'none';
        toggleCreateBoard.style.display = '';
        resetCreateForm();
    });
}

function resetCreateForm() {
    if (newBoardTitle) newBoardTitle.value = '';
    if (newBoardDesc) newBoardDesc.value = '';
    if (customIcon) customIcon.value = '';
    selectedIcon = '⭐';
    updateIconSelection();
}

if (createBoardBtn) {
    createBoardBtn.addEventListener('click', function() {
        var title = newBoardTitle.value.trim();
        if (!title) {
            newBoardTitle.focus();
            return;
        }

        createBoardBtn.disabled = true;
        createBoardBtn.textContent = 'Creando...';

        var fd = new FormData();
        fd.append('title', title);
        fd.append('description', newBoardDesc.value.trim());
        fd.append('rating_icon', selectedIcon);

        fetch('/app/api/create-board.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                createBoardBtn.disabled = false;
                createBoardBtn.textContent = 'Crear tablero';
                if (data.success) {
                    resetCreateForm();
                    selectBoard(data.board_id);
                } else {
                    alert(data.message || 'Error al crear el tablero');
                }
            })
            .catch(function() {
                createBoardBtn.disabled = false;
                createBoardBtn.textContent = 'Crear tablero';
                alert('Error de conexión. Verifica tu internet.');
            });
    });
}

// ===============================
// ICON PICKER
// ===============================
function buildIconGrid() {
    if (!iconGrid) return;
    iconGrid.innerHTML = '';
    ICON_OPTIONS.forEach(function(icon) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'icon-option' + (icon === selectedIcon ? ' icon-selected' : '');
        btn.textContent = icon;
        btn.addEventListener('click', function() {
            selectedIcon = icon;
            if (customIcon) customIcon.value = '';
            updateIconSelection();
        });
        iconGrid.appendChild(btn);
    });
}

function updateIconSelection() {
    if (iconPreview) iconPreview.textContent = selectedIcon;
    var btns = iconGrid ? iconGrid.querySelectorAll('.icon-option') : [];
    btns.forEach(function(b) {
        b.classList.toggle('icon-selected', b.textContent === selectedIcon);
    });
}

if (customIcon) {
    customIcon.addEventListener('input', function() {
        var val = customIcon.value.trim();
        if (val) {
            selectedIcon = val;
            updateIconSelection();
        }
    });
}

// ===============================
// UPLOAD MODAL
// ===============================
if (fab && modal) {
    fab.addEventListener('click', function() {
        if (!currentBoardId) { openBoardsPanel(); return; }
        openModal();
    });
}
if (modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
    document.querySelectorAll('#addModal .close').forEach(function(btn) {
        btn.addEventListener('click', closeModal);
    });
}

function openModal() {
    resetUploadState();
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = '';
    resetUploadState();
}
function resetUploadState() {
    selectedFile = null;
    if (photoInput) photoInput.value = '';
    if (captionInput) captionInput.value = '';
    if (uploadStep1) uploadStep1.style.display = '';
    if (uploadStep2) uploadStep2.style.display = 'none';
    if (uploadStep3) uploadStep3.style.display = 'none';
}

// File selected
if (photoInput) {
    photoInput.addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        selectedFile = file;
        var reader = new FileReader();
        reader.onload = function(ev) {
            photoPreview.src = ev.target.result;
            uploadStep1.style.display = 'none';
            uploadStep2.style.display = '';
        };
        reader.readAsDataURL(file);
    });
}
if (cancelUpload) {
    cancelUpload.addEventListener('click', function(e) {
        e.stopPropagation();
        resetUploadState();
    });
}

// Confirm upload
if (confirmUpload) {
    confirmUpload.addEventListener('click', function() {
        if (!selectedFile || !currentBoardId) return;
        uploadStep2.style.display = 'none';
        uploadStep3.style.display = '';

        var fd = new FormData();
        fd.append('photo', selectedFile);
        fd.append('caption', captionInput.value.trim());
        fd.append('board_id', currentBoardId);

        fetch('/app/api/upload-photo.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) { closeModal(); loadPhotos(); loadBoardMembers(); }
                else { alert(data.message || 'Error al subir la foto'); resetUploadState(); }
            })
            .catch(function() { alert('Error de conexión.'); resetUploadState(); });
    });
}

// ===============================
// NAV ACTIVE STATE
// ===============================
bottomNavButtons.forEach(function(button) {
    button.addEventListener('click', function() {
        bottomNavButtons.forEach(function(b) { b.classList.remove('active'); });
        button.classList.add('active');
    });
});

// ===============================
// LOAD PHOTOS
// ===============================
function loadPhotos() {
    if (!photoGrid || !currentBoardId) return;

    fetch('/app/api/get-photos.php?board_id=' + currentBoardId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            photoGrid.innerHTML = '';

            var icon = '⭐';
            var photos = data;

            // New API format returns { rating_icon, photos }
            if (data && data.photos) {
                icon = data.rating_icon || '⭐';
                photos = data.photos;
                currentRatingIcon = icon;
            }

            if (!photos || photos.length === 0) {
                renderFounderState();
                return;
            }

            photos.forEach(function(photo) {
                var item = document.createElement('div');
                item.className = 'grid-item';
                item.innerHTML =
                    '<img src="' + escapeHtml(photo.image_url) + '" alt="Foto">' +
                    '<div class="rating-chip">' +
                    '<span class="star">' + icon + '</span>' +
                    '<span class="rating-value">' + photo.rating + '</span>' +
                    '</div>';
                photoGrid.appendChild(item);
            });
        })
        .catch(function() { renderTechnicalError(); });
}

// ===============================
// LOAD BOARD MEMBERS
// ===============================
function loadBoardMembers() {
    if (!membersBar || !currentBoardId) return;

    fetch('/app/api/get-board-members.php?board_id=' + currentBoardId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            membersBar.innerHTML = '';

            var inviteEl = document.createElement('div');
            inviteEl.className = 'story story-invite';
            inviteEl.innerHTML = '<span class="invite-icon">+</span>';
            inviteEl.addEventListener('click', function() {
                shareInviteLink(data.board_id || currentBoardId);
            });
            membersBar.appendChild(inviteEl);

            if (data.members && data.members.length > 0) {
                data.members.forEach(function(member) {
                    var el = document.createElement('div');
                    el.className = 'story';
                    el.innerHTML = '<span class="member-avatar">' + getInitials(member.name) + '</span>';
                    el.title = member.name;
                    membersBar.appendChild(el);
                });
            }
        })
        .catch(function() {});
}

// ===============================
// INVITE
// ===============================
function shareInviteLink(boardId) {
    var link = window.location.origin + '/app/auth/register.php?board=' + (boardId || '');
    if (navigator.share) {
        navigator.share({ title: 'Gatooso', text: 'Unite a mi tablero en Gatooso', url: link }).catch(function() {});
    } else {
        navigator.clipboard.writeText(link).then(function() {
            alert('Link de invitación copiado');
        }).catch(function() {
            prompt('Copia este link de invitación:', link);
        });
    }
}

// ===============================
// EMPTY / ERROR STATES
// ===============================
function renderFounderState() {
    photoGrid.innerHTML =
        '<div class="empty-state"><div class="empty-inner">' +
        '<h2>Este reto aún no tiene historia</h2>' +
        '<p>Atrévete a ser la primera persona que lo inicia. Los retos no empiezan solos.</p>' +
        '<button class="empty-cta">Subir foto</button>' +
        '</div></div>';
    var cta = document.querySelector('.empty-cta');
    if (cta && fab) cta.addEventListener('click', function() { fab.click(); });
}

function renderTechnicalError() {
    photoGrid.innerHTML =
        '<div class="empty-state"><div class="empty-inner">' +
        '<h2>Algo se nos cruzó</h2><p>No es tu culpa. Intenta de nuevo.</p>' +
        '</div></div>';
}

// ===============================
// UTILS
// ===============================
function getInitials(name) {
    if (!name) return '?';
    var p = name.trim().split(' ');
    return p.length >= 2 ? (p[0][0] + p[1][0]).toUpperCase() : p[0].substring(0, 2).toUpperCase();
}
function escapeHtml(s) {
    if (!s) return '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ===============================
// SERVICE WORKER
// ===============================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/app/sw.js');
    });
}
