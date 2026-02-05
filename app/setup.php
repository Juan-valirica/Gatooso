<?php
/**
 * GATOOSO — Setup Script
 *
 * Visit this file once from your browser to create the database and tables.
 * Example: https://gatooso.com/app/setup.php
 *
 * DELETE this file after setup is complete.
 */

header('Content-Type: text/html; charset=utf-8');

$host = "localhost";
$db   = "gatooso_db";
$user = "gatooso_user";
$pass = "Rakwu4-wemjog-cajsit";

echo "<h1>Gatooso — Setup</h1>";

// Step 1: Connect to MySQL
try {
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p>✓ Conexión a MySQL exitosa</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>✗ No se pudo conectar a MySQL: " . htmlspecialchars($e->getMessage()) . "</p>
    <p>Verifica que el usuario <code>$user</code> exista y la contraseña sea correcta.</p>");
}

// Step 2: Create database
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✓ Base de datos <code>$db</code> lista</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>✗ No se pudo crear la base de datos: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// Step 3: Use the database
$pdo->exec("USE `$db`");

// Step 4: Create tables
$tables = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(100) NOT NULL,
        email       VARCHAR(255) NOT NULL UNIQUE,
        password    VARCHAR(255) NOT NULL,
        rating      DECIMAL(3,2) DEFAULT 0.00,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    'boards' => "CREATE TABLE IF NOT EXISTS boards (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        title       VARCHAR(255) NOT NULL,
        description TEXT NULL,
        created_by  INT NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    'board_users' => "CREATE TABLE IF NOT EXISTS board_users (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        board_id    INT NOT NULL,
        user_id     INT NOT NULL,
        role        ENUM('owner','admin','member') DEFAULT 'member',
        joined_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_board_user (board_id, user_id),
        FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
    ) ENGINE=InnoDB",

    'challenges' => "CREATE TABLE IF NOT EXISTS challenges (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        board_id    INT NOT NULL,
        title       VARCHAR(255) NOT NULL,
        description TEXT NULL,
        created_by  INT NULL,
        is_library  TINYINT(1) DEFAULT 0,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (board_id)   REFERENCES boards(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id)  ON DELETE SET NULL
    ) ENGINE=InnoDB",

    'images' => "CREATE TABLE IF NOT EXISTS images (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        challenge_id  INT NOT NULL,
        user_id       INT NOT NULL,
        image_url     VARCHAR(500) NOT NULL,
        caption       TEXT NULL,
        rating        DECIMAL(3,2) DEFAULT 0.00,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE
    ) ENGINE=InnoDB",

    'image_ratings' => "CREATE TABLE IF NOT EXISTS image_ratings (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        image_id    INT NOT NULL,
        user_id     INT NOT NULL,
        rating      TINYINT NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_image_user (image_id, user_id),
        FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
    ) ENGINE=InnoDB",

    'challenge_library' => "CREATE TABLE IF NOT EXISTS challenge_library (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        title       VARCHAR(255) NOT NULL,
        description TEXT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    'activity_log' => "CREATE TABLE IF NOT EXISTS activity_log (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        user_id       INT NOT NULL,
        board_id      INT NULL,
        action        VARCHAR(50) NOT NULL,
        reference_id  INT NULL,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
        FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE SET NULL
    ) ENGINE=InnoDB"
];

$success = true;
foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        echo "<p>✓ Tabla <code>$name</code> lista</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>✗ Error en tabla <code>$name</code>: " . htmlspecialchars($e->getMessage()) . "</p>";
        $success = false;
    }
}

if ($success) {
    echo "<hr>";
    echo "<h2 style='color:green'>Setup completado</h2>";
    echo "<p>Todo está listo. Ahora puedes <a href='/app/auth/register.php'>crear tu cuenta</a>.</p>";
    echo "<p><strong>Importante:</strong> Elimina este archivo (<code>setup.php</code>) de tu servidor por seguridad.</p>";
} else {
    echo "<hr>";
    echo "<p style='color:red'>Hubo errores. Revisa los mensajes arriba.</p>";
}
