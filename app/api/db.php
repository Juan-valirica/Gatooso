<?php
$host = "localhost";
$db   = "u163890745_gatooso_db";
$user = "u163890745_gatooso_user";
$pass = "Rakwu4-wemjog-cajsit";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "No se pudo conectar a la base de datos. Ejecuta setup.php primero."
    ]);
    exit;
}
