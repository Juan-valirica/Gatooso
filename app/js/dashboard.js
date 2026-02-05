// ===============================
// GLOBAL STATE
// ===============================
let lastScrollY = 0;
const SCROLL_THRESHOLD = 40;
let selectedFile = null;

// ===============================
// ELEMENTS
// ===============================
const storiesBar = document.getElementById('storiesBar');
const fab = document.querySelector('.fab');
const modal = document.getElementById('addModal');
const bottomNavButtons = document.querySelectorAll('.nav-btn');
const photoGrid = document.getElementById('photoGrid');
const membersBar = document.getElementById('membersBar');

// Upload elements
const uploadStep1 = document.getElementById('uploadStep1');
const uploadStep2 = document.getElementById('uploadStep2');
const uploadStep3 = document.getElementById('uploadStep3');
const photoInput = document.getElementById('photoInput');
const photoPreview = document.getElementById('photoPreview');
const captionInput = document.getElementById('captionInput');
const confirmUpload = document.getElementById('confirmUpload');
const cancelUpload = document.getElementById('cancelUpload');

// ===============================
// STORIES BAR HIDE / SHOW ON SCROLL
// ===============================
window.addEventListener('scroll', () => {
    const currentScrollY = window.scrollY;

    if (!storiesBar) return;

    if (currentScrollY > lastScrollY && currentScrollY > SCROLL_THRESHOLD) {
        storiesBar.style.transform = 'translateY(-110%)';
        storiesBar.style.opacity = '0';
    } else {
        storiesBar.style.transform = 'translateY(0)';
        storiesBar.style.opacity = '1';
    }

    lastScrollY = currentScrollY;
});

// ===============================
// MODAL OPEN / CLOSE
// ===============================
if (fab && modal) {
    fab.addEventListener('click', () => {
        openModal();
    });
}

if (modal) {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.querySelectorAll('#addModal .close').forEach(btn => {
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

// ===============================
// PHOTO UPLOAD FLOW
// ===============================

// Step 1 -> Step 2: File selected
if (photoInput) {
    photoInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        selectedFile = file;

        const reader = new FileReader();
        reader.onload = (ev) => {
            photoPreview.src = ev.target.result;
            uploadStep1.style.display = 'none';
            uploadStep2.style.display = '';
        };
        reader.readAsDataURL(file);
    });
}

// Step 2 -> Back to Step 1
if (cancelUpload) {
    cancelUpload.addEventListener('click', (e) => {
        e.stopPropagation();
        resetUploadState();
    });
}

// Step 2 -> Step 3: Confirm upload
if (confirmUpload) {
    confirmUpload.addEventListener('click', () => {
        if (!selectedFile) return;

        uploadStep2.style.display = 'none';
        uploadStep3.style.display = '';

        const formData = new FormData();
        formData.append('photo', selectedFile);
        formData.append('caption', captionInput.value.trim());

        fetch('/app/api/upload-photo.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeModal();
                loadPhotos();
            } else {
                alert(data.message || 'Error al subir la foto');
                resetUploadState();
            }
        })
        .catch(() => {
            alert('Error de conexión. Intenta de nuevo.');
            resetUploadState();
        });
    });
}

// ===============================
// BOTTOM NAV ACTIVE STATE
// ===============================
bottomNavButtons.forEach(button => {
    button.addEventListener('click', () => {
        bottomNavButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
    });
});

// ===============================
// LOAD PHOTOS FROM DATABASE
// ===============================
document.addEventListener('DOMContentLoaded', () => {
    loadPhotos();
    loadBoardMembers();
});

function loadPhotos() {
    if (!photoGrid) return;

    fetch('/app/api/get-photos.php')
        .then(res => res.json())
        .then(data => {
            photoGrid.innerHTML = '';

            if (!data || data.length === 0) {
                renderFounderState();
                return;
            }

            data.forEach(photo => {
                const item = document.createElement('div');
                item.className = 'grid-item';

                item.innerHTML = `
                    <img src="${photo.image_url}" alt="Foto del reto">
                    <div class="rating-chip">
                        <span class="star">&#11088;</span>
                        <span class="rating-value">${photo.rating}</span>
                    </div>
                `;

                photoGrid.appendChild(item);
            });
        })
        .catch(() => {
            renderTechnicalError();
        });
}

// ===============================
// LOAD BOARD MEMBERS
// ===============================
function loadBoardMembers() {
    if (!membersBar) return;

    fetch('/app/api/get-board-members.php')
        .then(res => res.json())
        .then(data => {
            membersBar.innerHTML = '';

            // Invite button (always first, far left)
            const inviteEl = document.createElement('div');
            inviteEl.className = 'story story-invite';
            inviteEl.innerHTML = '<span class="invite-icon">+</span>';
            inviteEl.addEventListener('click', () => {
                shareInviteLink(data.board_id);
            });
            membersBar.appendChild(inviteEl);

            // Members
            if (data.members && data.members.length > 0) {
                data.members.forEach(member => {
                    const el = document.createElement('div');
                    el.className = 'story';
                    const initials = getInitials(member.name);
                    el.innerHTML = '<span class="member-avatar">' + initials + '</span>';
                    el.title = member.name;
                    membersBar.appendChild(el);
                });
            }
        })
        .catch(() => {
            // Silently fail
        });
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return parts[0].substring(0, 2).toUpperCase();
}

function shareInviteLink(boardId) {
    var link = window.location.origin + '/app/auth/register.php?board=' + (boardId || '');

    if (navigator.share) {
        navigator.share({
            title: 'Gatooso',
            text: 'Unite a mi tablero en Gatooso',
            url: link
        }).catch(function() {});
    } else {
        navigator.clipboard.writeText(link).then(function() {
            alert('Link de invitación copiado');
        }).catch(function() {
            prompt('Copia este link de invitación:', link);
        });
    }
}

// ===============================
// FOUNDER STATE (NO PHOTOS YET)
// ===============================
function renderFounderState() {
    photoGrid.innerHTML = `
        <div class="empty-state">
            <div class="empty-inner">
                <h2>
                    Este reto aún no tiene historia
                </h2>

                <p>
                    Atrévete a ser la primera persona que lo inicia.
                    Los retos no empiezan solos. Empiezan con alguien como tú.
                </p>

                <button class="empty-cta">
                    Iniciar el reto
                </button>
            </div>
        </div>
    `;

    const cta = document.querySelector('.empty-cta');
    if (cta && fab) {
        cta.addEventListener('click', () => fab.click());
    }
}

// ===============================
// TECHNICAL ERROR STATE
// ===============================
function renderTechnicalError() {
    photoGrid.innerHTML = `
        <div class="empty-state">
            <div class="empty-inner">
                <h2>
                    Algo se nos cruzó
                </h2>

                <p>
                    No es tu culpa. Intenta de nuevo en un momento.
                </p>
            </div>
        </div>
    `;
}

// ===============================
// SERVICE WORKER (PWA)
// ===============================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/app/sw.js');
    });
}
