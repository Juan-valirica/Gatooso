<?php
header("Content-Type: application/json");
require "db.php";

$stmt = $pdo->query("
    SELECT image_url, rating
    FROM images
    ORDER BY created_at DESC
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
