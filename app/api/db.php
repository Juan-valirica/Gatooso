<?php
$host = "localhost";
$db   = "gatooso_db";
$user = "gatooso_user";
$pass = "Rakwu4-wemjog-cajsit";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "DB error"]);
    exit;
}
