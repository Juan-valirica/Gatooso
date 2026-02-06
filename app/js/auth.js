const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');

if (loginForm) {
    loginForm.addEventListener('submit', e => {
        e.preventDefault();
        submitAuth('/app/api/login.php', loginForm);
    });
}

if (registerForm) {
    registerForm.addEventListener('submit', e => {
        e.preventDefault();
        submitAuth('/app/api/register.php', registerForm);
    });
}

function submitAuth(endpoint, form) {
    const data = new FormData(form);

    // Check for board invite in URL
    const urlParams = new URLSearchParams(window.location.search);
    const boardId = urlParams.get('board');
    if (boardId) {
        data.append('board_id', boardId);
    }

    fetch(endpoint, {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            // Redirect to app (with board if invited)
            const redirect = boardId ? `/app/?board=${boardId}` : '/app/';
            window.location.href = redirect;
        } else {
            alert(response.message || 'Algo no salió bien');
        }
    })
    .catch(() => {
        alert('Error de conexión. Intenta de nuevo.');
    });
}
