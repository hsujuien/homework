<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO emails (email) VALUES (?)');
            $stmt->execute([$email]);
            echo "Email 新增成功！";
        } catch (PDOException $e) {
            echo "此 Email 已經存在或發生錯誤。";
        }
    } else {
        echo "Email 格式不正確！";
    }
}
?>