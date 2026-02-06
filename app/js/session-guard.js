fetch('/app/api/session.php', {
    credentials: 'include'
})
.then(res => res.json())
.then(data => {
    if (!data.authenticated) {
        window.location.href = '/app/auth/login.php';
    } else {
        // Store user info for personalization
        window.currentUser = {
            id: data.user_id,
            name: data.user_name
        };
    }
})
.catch(() => {
    window.location.href = '/app/auth/login.php';
});
