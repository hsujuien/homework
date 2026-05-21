<?php
require 'db.php';
header('Content-Type: application/json');

$mode = $_GET['mode'] ?? 'all';
$count = (int)($_GET['count'] ?? 0);

if ($mode === 'random' && $count > 0) {
    // 隨機撈取 N 筆
    $stmt = $pdo->prepare('SELECT email FROM emails ORDER BY RAND() LIMIT ?');
    $stmt->bindValue(1, $count, PDO::PARAM_INT);
    $stmt->execute();
} else {
    // 撈取全部
    $stmt = $pdo->query('SELECT email FROM emails');
}

$emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($emails);
?>