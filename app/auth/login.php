<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gatooso · Iniciar sesión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <img src="../assets/brand/logo.svg" class="auth-logo" alt="Gatooso">

    <h1>Bienvenido de vuelta</h1>
    <p>Entra y sigue jugando el reto</p>

    <form id="loginForm">
        <input type="email" name="email" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>

        <button type="submit">Iniciar sesión</button>
    </form>

    <span class="auth-link">
        ¿Primera vez aquí? <a href="register.html">Crear cuenta</a>
    </span>
</div>

<script src="../js/auth.js"></script>
</body>
</html>
