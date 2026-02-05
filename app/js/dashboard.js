// ===============================
// GLOBAL STATE
// ===============================
let lastScrollY = 0;
const SCROLL_THRESHOLD = 40;

// ===============================
// ELEMENTS
// ===============================
const storiesBar = document.getElementById('storiesBar');
const fab = document.querySelector('.fab');
const modal = document.getElementById('addModal');
const closeBtn = modal ? modal.querySelector('.close') : null;
const bottomNavButtons = document.querySelectorAll('.nav-btn');
const photoGrid = document.getElementById('photoGrid');

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
// FAB (ADD MEDIA) MODAL
// ===============================
if (fab && modal) {
    fab.addEventListener('click', () => {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });
}

if (closeBtn && modal) {
    closeBtn.addEventListener('click', closeModal);
}

if (modal) {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
}

function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = '';
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
document.addEventListener('DOMContentLoaded', loadPhotos);

function loadPhotos() {
    if (!photoGrid) return;

    fetch('/api/get-photos.php')
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
                        <span class="star">⭐️</span>
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
        navigator.serviceWorker.register('/sw.js');
    });
}
