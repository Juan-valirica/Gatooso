fetch('/app/api/session.php', {
    credentials: 'include'
})
.then(res => res.json())
.then(data => {
    if (!data.authenticated) {
        window.location.href = '/app/auth/login';
    }
})
.catch(() => {
    window.location.href = '/app/auth/login';
});
