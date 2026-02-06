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
            name: data.user_name,
            avatar_url: data.avatar_url
        };
        // Dispatch event so other scripts know user data is ready
        window.dispatchEvent(new CustomEvent('userReady', { detail: window.currentUser }));
    }
})
.catch(() => {
    window.location.href = '/app/auth/login.php';
});
