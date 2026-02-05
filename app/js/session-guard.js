fetch('/api/session.php', {
    credentials: 'include'
})
.then(res => res.json())
.then(data => {
    if (!data.authenticated) {
        window.location.href = '/auth/login.php';
    }
})
.catch(() => {
    window.location.href = '/auth/login.php';
});
