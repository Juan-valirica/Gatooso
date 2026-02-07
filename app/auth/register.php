<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gatooso · Crear cuenta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/auth.css">
    <!-- Adobe Fonts - RL Folklor (Gatooso Branding) -->
    <link rel="stylesheet" href="https://use.typekit.net/yje8guk.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <img src="../assets/brand/logo.svg" class="auth-logo" alt="Gatooso">

    <h1>Crea tu cuenta</h1>
    <p>Un nombre. Un correo. Una contraseña. Empiezas.</p>

    <form id="registerForm">
        <input type="text" name="name" placeholder="Tu nombre" required>
        <input type="email" name="email" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>

        <button type="submit">Crear cuenta</button>
    </form>

    <span class="auth-link">
        ¿Ya tienes cuenta? <a href="login.php" id="switchAuthLink">Iniciar sesión</a>
    </span>
</div>

<script>
// Preserve query params when switching between login/register
const params = window.location.search;
if (params) {
    document.getElementById('switchAuthLink').href = 'login.php' + params;
}
</script>
<script src="../js/auth.js"></script>
</body>
</html>
