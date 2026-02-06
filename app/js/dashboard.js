// ===============================
// GLOBAL STATE
// ===============================
var lastScrollY = 0;
var SCROLL_THRESHOLD = 40;
var selectedFile = null;
var currentBoardId = null;
var currentRatingIcon = '⭐';
var selectedIcon = '⭐';
var currentViewImage = null;
var viewerOverlay = null;
var isRatingInProgress = false;
var countdownInterval = null;
var selectedDuration = 72;

var ICON_OPTIONS = [
    '⭐','🔥','🍆','💃','🤙','😏',
    '💋','🎯','🏆','👀','🌶️','💎',
    '🍑','😈','🦄','🫦'
];

// ===============================
// ELEMENTS
// ===============================
var storiesBar = document.getElementById('storiesBar');
var appLogo = document.getElementById('appLogo');
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

// Challenges
var navChallenges = document.getElementById('navChallenges');
var challengesPanel = document.getElementById('challengesPanel');
var closeChallengesPanelBtn = document.getElementById('closeChallengesPanel');
var activeChallengeCard = document.getElementById('activeChallengeCard');
var queueList = document.getElementById('queueList');
var queueCount = document.getElementById('queueCount');
var toggleCreateChallenge = document.getElementById('toggleCreateChallenge');
var createChallengeForm = document.getElementById('createChallengeForm');
var newChallengeTitle = document.getElementById('newChallengeTitle');
var newChallengeDesc = document.getElementById('newChallengeDesc');
var createChallengeBtn = document.getElementById('createChallengeBtn');
var cancelCreateChallenge = document.getElementById('cancelCreateChallenge');
var durationOptions = document.getElementById('durationOptions');

// Header challenge elements
var challengeTitle = document.querySelector('.challenge-title');
var challengeDescription = document.querySelector('.challenge-description');
var challengeCountdown = document.querySelector('.challenge-countdown');

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
    initDurationPicker();
    initChallengesPanel();
});

function loadBoard() {
    if (!currentBoardId) return;
    storiesBar.style.display = '';
    loadPhotos();
    loadBoardMembers();
    loadActiveChallenge();
}

// ===============================
// SCROLL — Hide/show header + logo
// ===============================
window.addEventListener('scroll', function() {
    var sy = window.scrollY;
    if (!storiesBar) return;

    // Logo fades immediately on any scroll
    if (appLogo) {
        if (sy > 10) {
            appLogo.classList.add('logo-hidden');
        } else {
            appLogo.classList.remove('logo-hidden');
        }
    }

    // Header hides after threshold
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

                card.querySelector('.board-card-enter').addEventListener('click', function() {
                    selectBoard(parseInt(board.id));
                });
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
// LOAD PHOTOS — Enhanced cards
// ===============================
function loadPhotos() {
    if (!photoGrid || !currentBoardId) return;

    fetch('/app/api/get-photos.php?board_id=' + currentBoardId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            photoGrid.innerHTML = '';

            var icon = '⭐';
            var photos = data;

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

                var commentCount = parseInt(photo.comment_count) || 0;
                var commentBadge = commentCount > 0
                    ? '<div class="grid-item-comments"><i class="ph ph-chat-circle"></i>' + commentCount + '</div>'
                    : '';

                item.innerHTML =
                    '<img src="' + escapeHtml(photo.image_url) + '" alt="Foto">' +
                    '<div class="grid-item-overlay"></div>' +
                    '<div class="grid-item-avatar">' + getInitials(photo.user_name) + '</div>' +
                    '<div class="rating-chip">' +
                        '<span class="star">' + icon + '</span>' +
                        '<span class="rating-value">' + (parseFloat(photo.rating) || 0).toFixed(1) + '</span>' +
                    '</div>' +
                    commentBadge;

                // Click to open viewer
                item.addEventListener('click', function() {
                    openImageViewer({
                        id: photo.id,
                        image_url: photo.image_url,
                        caption: photo.caption,
                        rating: photo.rating,
                        user_name: photo.user_name,
                        user_id: photo.user_id
                    });
                });

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
// IMAGE VIEWER — GAMIFIED
// ===============================
function openImageViewer(photoData) {
    currentViewImage = photoData;

    if (!viewerOverlay) {
        buildViewerOverlay();
    }

    // Set content
    document.getElementById('viewerImage').src = photoData.image_url;
    document.getElementById('viewerUserName').textContent = photoData.user_name || 'Usuario';
    document.getElementById('viewerAvatar').textContent = getInitials(photoData.user_name);
    document.getElementById('viewerCaption').textContent = photoData.caption || '';

    // Update label with current board icon
    document.getElementById('vrLabel').textContent = '¿Qué tan ' + currentRatingIcon + ' es?';

    // Build rating buttons with current icon
    buildRatingButtons();

    // Reset UI
    resetViewerRatingUI();
    document.getElementById('commentsList').innerHTML = '<div class="vc-empty">Cargando...</div>';
    document.getElementById('vcCount').textContent = '0';

    // Show viewer
    viewerOverlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function() {
        viewerOverlay.classList.add('viewer-active');
    });

    // Load details
    loadImageDetail(photoData.id);
}

function closeImageViewer() {
    if (!viewerOverlay) return;
    viewerOverlay.classList.remove('viewer-active');
    setTimeout(function() {
        viewerOverlay.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

function buildViewerOverlay() {
    viewerOverlay = document.createElement('div');
    viewerOverlay.id = 'imageViewer';
    viewerOverlay.className = 'viewer-overlay';

    viewerOverlay.innerHTML =
        '<div class="viewer-container">' +
            '<button class="viewer-close">&times;</button>' +

            '<div class="viewer-image-wrap">' +
                '<img id="viewerImage" src="" alt="">' +
            '</div>' +

            '<div class="viewer-info">' +
                '<span class="viewer-avatar" id="viewerAvatar"></span>' +
                '<div class="viewer-user-info">' +
                    '<span class="viewer-user-name" id="viewerUserName"></span>' +
                    '<span class="viewer-caption" id="viewerCaption"></span>' +
                '</div>' +
            '</div>' +

            '<div class="viewer-rating">' +
                '<div class="vr-label" id="vrLabel">¿Qué tan ⭐ es?</div>' +
                '<div class="vr-buttons" id="ratingButtons"></div>' +
                '<div class="vr-avg" id="avgRatingDisplay">' +
                    '<span class="vr-avg-icon" id="avgIcon">⭐</span>' +
                    '<span class="vr-avg-value" id="avgValue">0.0</span>' +
                    '<span class="vr-avg-count" id="avgCount">(0 votos)</span>' +
                '</div>' +
            '</div>' +

            '<div class="viewer-comments">' +
                '<div class="vc-header">' +
                    '<span class="vc-title">Comentarios</span>' +
                    '<span class="vc-count" id="vcCount">0</span>' +
                '</div>' +
                '<div class="vc-list" id="commentsList"></div>' +
                '<div class="vc-input-wrap">' +
                    '<input type="text" id="commentInput" placeholder="Escribe algo...">' +
                    '<button id="sendComment"><i class="ph ph-paper-plane-tilt"></i></button>' +
                '</div>' +
            '</div>' +

        '</div>';

    document.body.appendChild(viewerOverlay);

    // Close handler
    viewerOverlay.querySelector('.viewer-close').addEventListener('click', closeImageViewer);

    // Comment submit
    document.getElementById('sendComment').addEventListener('click', submitComment);
    document.getElementById('commentInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') submitComment();
    });
}

function buildRatingButtons() {
    var container = document.getElementById('ratingButtons');
    if (!container) return;
    container.innerHTML = '';

    for (var i = 1; i <= 5; i++) {
        var btn = document.createElement('button');
        btn.className = 'vr-btn';
        btn.textContent = currentRatingIcon;
        btn.dataset.rating = i;
        btn.addEventListener('click', handleRatingClick);
        container.appendChild(btn);
    }
}

function resetViewerRatingUI() {
    var btns = document.querySelectorAll('.vr-btn');
    btns.forEach(function(b) {
        b.classList.remove('vr-btn-filled', 'vr-btn-active', 'vr-btn-pop', 'vr-btn-mega', 'vr-btn-wave');
    });
    var avgDisplay = document.getElementById('avgRatingDisplay');
    if (avgDisplay) avgDisplay.classList.remove('vr-avg-visible');
    isRatingInProgress = false;
}

function loadImageDetail(imageId) {
    fetch('/app/api/get-image-detail.php?image_id=' + imageId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;

            // If user already rated, show their rating
            if (data.my_rating > 0) {
                applyRatingUI(data.my_rating, false);
            }

            // Show average
            showAverageRating(data.image.rating, data.rating_count);

            // Load comments
            renderComments(data.comments);
        })
        .catch(function() {});
}

// ===============================
// GAMIFIED RATING INTERACTION
// ===============================
function handleRatingClick(e) {
    if (isRatingInProgress) return;
    isRatingInProgress = true;

    var btn = e.currentTarget;
    var rating = parseInt(btn.dataset.rating);

    // Visual feedback
    applyRatingUI(rating, true);

    // Haptic feedback
    triggerHaptic(rating);

    // Particles!
    var rect = btn.getBoundingClientRect();
    var cx = rect.left + rect.width / 2;
    var cy = rect.top + rect.height / 2;
    spawnParticles(cx, cy, currentRatingIcon, rating * 6);

    // Epic effects for rating 5
    if (rating === 5) {
        viewerOverlay.classList.add('viewer-shake', 'viewer-flash');
        setTimeout(function() {
            viewerOverlay.classList.remove('viewer-shake', 'viewer-flash');
        }, 600);
        // Extra particle burst
        spawnParticles(cx, cy - 50, currentRatingIcon, 20, true);
    }

    // Submit rating to API
    var fd = new FormData();
    fd.append('image_id', currentViewImage.id);
    fd.append('rating', rating);

    fetch('/app/api/rate-image.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                setTimeout(function() {
                    showAverageRating(data.new_avg, data.rating_count);
                    isRatingInProgress = false;
                }, 400);
            } else {
                isRatingInProgress = false;
            }
        })
        .catch(function() {
            isRatingInProgress = false;
        });
}

function applyRatingUI(rating, animate) {
    var btns = document.querySelectorAll('.vr-btn');
    btns.forEach(function(b, idx) {
        var level = idx + 1;
        b.classList.remove('vr-btn-filled', 'vr-btn-active', 'vr-btn-pop', 'vr-btn-mega', 'vr-btn-wave');

        if (level <= rating) {
            b.classList.add('vr-btn-filled');
            if (animate && level < rating) {
                b.style.animationDelay = (level * 0.05) + 's';
                b.classList.add('vr-btn-wave');
            }
        }
        if (level === rating) {
            b.classList.add('vr-btn-active');
            if (animate) {
                b.classList.add(rating === 5 ? 'vr-btn-mega' : 'vr-btn-pop');
            }
        }
    });
}

function showAverageRating(avg, count) {
    var avgDisplay = document.getElementById('avgRatingDisplay');
    var avgIcon = document.getElementById('avgIcon');
    var avgValue = document.getElementById('avgValue');
    var avgCount = document.getElementById('avgCount');

    if (avgIcon) avgIcon.textContent = currentRatingIcon;
    if (avgValue) avgValue.textContent = (parseFloat(avg) || 0).toFixed(1);
    if (avgCount) avgCount.textContent = '(' + (count || 0) + ' voto' + (count !== 1 ? 's' : '') + ')';

    if (avgDisplay) {
        avgDisplay.classList.add('vr-avg-visible');
    }
}

function triggerHaptic(rating) {
    if (!navigator.vibrate) return;
    var patterns = {
        1: [30],
        2: [30, 20, 30],
        3: [40, 20, 40, 20, 40],
        4: [50, 25, 50, 25, 50, 25, 50],
        5: [80, 40, 80, 40, 80, 40, 80, 40, 100]
    };
    navigator.vibrate(patterns[rating] || [30]);
}

// ===============================
// PARTICLE SYSTEM
// ===============================
function spawnParticles(x, y, emoji, count, big) {
    for (var i = 0; i < count; i++) {
        var particle = document.createElement('div');
        particle.className = 'emoji-particle' + (big ? ' emoji-particle-big' : '');
        particle.textContent = emoji;
        particle.style.left = x + 'px';
        particle.style.top = y + 'px';

        var angle = Math.random() * Math.PI * 2;
        var distance = 60 + Math.random() * (big ? 180 : 120);
        var tx = Math.cos(angle) * distance;
        var ty = Math.sin(angle) * distance - (big ? 100 : 60);

        particle.style.setProperty('--tx', tx + 'px');
        particle.style.setProperty('--ty', ty + 'px');
        particle.style.setProperty('--r', (Math.random() * 720 - 360) + 'deg');
        particle.style.animationDelay = (Math.random() * 0.12) + 's';

        document.body.appendChild(particle);

        (function(p) {
            setTimeout(function() {
                if (p.parentNode) p.parentNode.removeChild(p);
            }, big ? 1200 : 900);
        })(particle);
    }
}

// ===============================
// COMMENTS
// ===============================
function renderComments(comments) {
    var list = document.getElementById('commentsList');
    var countEl = document.getElementById('vcCount');
    if (!list) return;

    if (!comments || comments.length === 0) {
        list.innerHTML = '<div class="vc-empty">Sé el primero en comentar</div>';
        if (countEl) countEl.textContent = '0';
        return;
    }

    if (countEl) countEl.textContent = comments.length;
    list.innerHTML = '';

    comments.forEach(function(c) {
        var bubble = document.createElement('div');
        bubble.className = 'vc-bubble';
        bubble.innerHTML =
            '<span class="vc-bubble-avatar">' + getInitials(c.user_name) + '</span>' +
            '<div class="vc-bubble-body">' +
                '<span class="vc-bubble-name">' + escapeHtml(c.user_name) + '</span>' +
                '<span class="vc-bubble-text">' + escapeHtml(c.comment) + '</span>' +
            '</div>';
        list.appendChild(bubble);
    });

    list.scrollTop = list.scrollHeight;
}

function submitComment() {
    var input = document.getElementById('commentInput');
    var comment = input.value.trim();
    if (!comment || !currentViewImage) return;

    input.value = '';

    var fd = new FormData();
    fd.append('image_id', currentViewImage.id);
    fd.append('comment', comment);

    fetch('/app/api/add-comment.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.comment) {
                var list = document.getElementById('commentsList');
                var countEl = document.getElementById('vcCount');

                // Remove empty state if present
                var empty = list.querySelector('.vc-empty');
                if (empty) empty.remove();

                // Add new comment
                var bubble = document.createElement('div');
                bubble.className = 'vc-bubble';
                bubble.innerHTML =
                    '<span class="vc-bubble-avatar">' + getInitials(data.comment.user_name) + '</span>' +
                    '<div class="vc-bubble-body">' +
                        '<span class="vc-bubble-name">' + escapeHtml(data.comment.user_name) + '</span>' +
                        '<span class="vc-bubble-text">' + escapeHtml(data.comment.comment) + '</span>' +
                    '</div>';
                list.appendChild(bubble);
                list.scrollTop = list.scrollHeight;

                // Update count
                if (countEl) {
                    var cur = parseInt(countEl.textContent) || 0;
                    countEl.textContent = cur + 1;
                }
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
// CHALLENGES PANEL
// ===============================
function initChallengesPanel() {
    if (navChallenges) {
        navChallenges.addEventListener('click', function(e) {
            e.stopPropagation();
            openChallengesPanel();
        });
    }
    if (closeChallengesPanelBtn) {
        closeChallengesPanelBtn.addEventListener('click', closeChallengesPanel);
    }
    if (toggleCreateChallenge) {
        toggleCreateChallenge.addEventListener('click', function() {
            toggleCreateChallenge.style.display = 'none';
            createChallengeForm.style.display = '';
            newChallengeTitle.focus();
        });
    }
    if (cancelCreateChallenge) {
        cancelCreateChallenge.addEventListener('click', function() {
            createChallengeForm.style.display = 'none';
            toggleCreateChallenge.style.display = '';
            resetChallengeForm();
        });
    }
    if (createChallengeBtn) {
        createChallengeBtn.addEventListener('click', submitNewChallenge);
    }
}

function openChallengesPanel() {
    if (!currentBoardId) { openBoardsPanel(); return; }
    loadChallenges();
    challengesPanel.style.display = '';
    document.body.style.overflow = 'hidden';
    if (createChallengeForm) createChallengeForm.style.display = 'none';
    if (toggleCreateChallenge) toggleCreateChallenge.style.display = '';
}

function closeChallengesPanel() {
    challengesPanel.style.display = 'none';
    document.body.style.overflow = '';
}

function loadChallenges() {
    if (!currentBoardId) return;

    activeChallengeCard.innerHTML = '<div class="boards-loading-state"><div class="spinner"></div></div>';
    queueList.innerHTML = '<div class="boards-loading-state"><div class="spinner"></div></div>';

    fetch('/app/api/get-challenges.php?board_id=' + currentBoardId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;

            // Render active challenge
            renderActiveChallengeCard(data.active);

            // Update queue count
            if (queueCount) queueCount.textContent = data.queued_count;

            // Render queue
            renderQueueList(data.queued);
        })
        .catch(function() {
            activeChallengeCard.innerHTML = '<div class="ac-empty"><p>Error al cargar</p></div>';
        });
}

function renderActiveChallengeCard(challenge) {
    if (!challenge) {
        activeChallengeCard.innerHTML =
            '<div class="ac-empty">' +
            '<div class="ac-empty-icon">🎯</div>' +
            '<h3>Sin reto activo</h3>' +
            '<p>Propón el primer reto para empezar a jugar con tu grupo.</p>' +
            '</div>';
        return;
    }

    var timeLeft = getTimeRemaining(challenge.ends_at);

    activeChallengeCard.innerHTML =
        '<div class="ac-header">' +
            '<span class="ac-title">' + escapeHtml(challenge.title) + '</span>' +
            '<span class="ac-timer" id="acTimer">' + formatTimeLeft(timeLeft) + '</span>' +
        '</div>' +
        '<p class="ac-description">' + escapeHtml(challenge.description || 'Sin descripción') + '</p>' +
        '<div class="ac-stats">' +
            '<span class="ac-stat"><i class="ph-image"></i> <span class="ac-stat-value">' + (challenge.photo_count || 0) + '</span> fotos</span>' +
            '<span class="ac-stat"><i class="ph-user"></i> por <span class="ac-stat-value">' + escapeHtml(challenge.creator_name || 'Sistema') + '</span></span>' +
        '</div>';
}

function renderQueueList(queued) {
    if (!queued || queued.length === 0) {
        queueList.innerHTML = '<div class="queue-empty">No hay retos en cola</div>';
        return;
    }

    queueList.innerHTML = '';
    queued.forEach(function(ch, idx) {
        var durationText = formatDuration(ch.duration_hours);
        var item = document.createElement('div');
        item.className = 'queue-item';
        item.innerHTML =
            '<span class="queue-position">' + (idx + 1) + '</span>' +
            '<div class="queue-info">' +
                '<span class="queue-title">' + escapeHtml(ch.title) + '</span>' +
                '<span class="queue-meta">por ' + escapeHtml(ch.creator_name || 'Usuario') + '</span>' +
            '</div>' +
            '<span class="queue-duration">' + durationText + '</span>';
        queueList.appendChild(item);
    });
}

// ===============================
// DURATION PICKER
// ===============================
function initDurationPicker() {
    if (!durationOptions) return;
    var btns = durationOptions.querySelectorAll('.cc-duration-btn');
    btns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            btns.forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            selectedDuration = parseInt(btn.dataset.hours) || 72;
        });
    });
}

function resetChallengeForm() {
    if (newChallengeTitle) newChallengeTitle.value = '';
    if (newChallengeDesc) newChallengeDesc.value = '';
    selectedDuration = 72;
    if (durationOptions) {
        var btns = durationOptions.querySelectorAll('.cc-duration-btn');
        btns.forEach(function(b) {
            b.classList.toggle('active', parseInt(b.dataset.hours) === 72);
        });
    }
}

function submitNewChallenge() {
    var title = newChallengeTitle.value.trim();
    if (!title) {
        newChallengeTitle.focus();
        return;
    }
    if (!currentBoardId) return;

    createChallengeBtn.disabled = true;
    createChallengeBtn.innerHTML = '<div class="spinner" style="width:20px;height:20px;border-width:2px;"></div> Creando...';

    var fd = new FormData();
    fd.append('board_id', currentBoardId);
    fd.append('title', title);
    fd.append('description', newChallengeDesc.value.trim());
    fd.append('duration_hours', selectedDuration);

    fetch('/app/api/create-challenge.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            createChallengeBtn.disabled = false;
            createChallengeBtn.innerHTML = '<i class="ph-paper-plane-tilt"></i> Proponer reto';

            if (data.success) {
                resetChallengeForm();
                createChallengeForm.style.display = 'none';
                toggleCreateChallenge.style.display = '';
                loadChallenges();
                loadActiveChallenge(); // Update header
                if (data.status === 'active') {
                    alert('¡Tu reto está activo ahora!');
                } else {
                    alert('Reto agregado a la cola (posición ' + data.queue_position + ')');
                }
            } else {
                alert(data.message || 'Error al crear el reto');
            }
        })
        .catch(function() {
            createChallengeBtn.disabled = false;
            createChallengeBtn.innerHTML = '<i class="ph-paper-plane-tilt"></i> Proponer reto';
            alert('Error de conexión');
        });
}

// ===============================
// ACTIVE CHALLENGE (Header)
// ===============================
function loadActiveChallenge() {
    if (!currentBoardId) return;

    fetch('/app/api/get-active-challenge.php?board_id=' + currentBoardId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;

            if (data.challenge) {
                updateHeaderChallenge(data.challenge);
                startCountdown(data.challenge.ends_at);
            } else {
                // No active challenge
                if (challengeTitle) challengeTitle.textContent = 'Sin reto activo';
                if (challengeDescription) challengeDescription.textContent = 'Abre Retos para proponer uno nuevo';
                if (challengeCountdown) challengeCountdown.innerHTML = '<span class="time">--</span>d<span class="divider">:</span><span class="time">--</span>h<span class="divider">:</span><span class="time">--</span>m';
            }
        })
        .catch(function() {});
}

function updateHeaderChallenge(challenge) {
    if (challengeTitle) challengeTitle.textContent = challenge.title;
    if (challengeDescription) challengeDescription.textContent = challenge.description || '';
}

function startCountdown(endsAt) {
    if (countdownInterval) clearInterval(countdownInterval);

    function updateCountdown() {
        var timeLeft = getTimeRemaining(endsAt);

        if (timeLeft.total <= 0) {
            clearInterval(countdownInterval);
            // Reload to get next challenge
            setTimeout(function() {
                loadActiveChallenge();
                loadPhotos();
            }, 1000);
            return;
        }

        if (challengeCountdown) {
            challengeCountdown.innerHTML =
                '<span class="time">' + pad(timeLeft.days) + '</span>d' +
                '<span class="divider">:</span>' +
                '<span class="time">' + pad(timeLeft.hours) + '</span>h' +
                '<span class="divider">:</span>' +
                '<span class="time">' + pad(timeLeft.minutes) + '</span>m';
        }

        // Also update the panel timer if open
        var acTimer = document.getElementById('acTimer');
        if (acTimer) {
            acTimer.textContent = formatTimeLeft(timeLeft);
        }
    }

    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 60000); // Update every minute
}

function getTimeRemaining(endsAt) {
    if (!endsAt) return { total: 0, days: 0, hours: 0, minutes: 0 };

    var endTime = new Date(endsAt).getTime();
    var now = Date.now();
    var diff = endTime - now;

    if (diff <= 0) return { total: 0, days: 0, hours: 0, minutes: 0 };

    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
    var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    return { total: diff, days: days, hours: hours, minutes: minutes };
}

function formatTimeLeft(t) {
    if (t.total <= 0) return 'Terminado';
    if (t.days > 0) return t.days + 'd ' + t.hours + 'h';
    if (t.hours > 0) return t.hours + 'h ' + t.minutes + 'm';
    return t.minutes + 'm';
}

function formatDuration(hours) {
    if (!hours) return '3 días';
    if (hours <= 24) return '1 día';
    if (hours <= 72) return '3 días';
    if (hours <= 168) return '1 semana';
    return Math.floor(hours / 24) + ' días';
}

function pad(n) {
    return n < 10 ? '0' + n : n;
}

// ===============================
// SERVICE WORKER
// ===============================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/app/sw.js');
    });
}
