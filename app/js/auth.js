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

    fetch(endpoint, {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            window.location.href = '/app/';
        } else {
            alert(response.message || 'Algo no salió bien');
        }
    })
    .catch(() => {
        alert('Error de conexión. Intenta de nuevo.');
    });
}
