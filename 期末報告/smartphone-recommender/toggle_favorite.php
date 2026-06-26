<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$phoneId = (int)($_POST['phone_id'] ?? 0);
$back = $_POST['back'] ?? 'favorites.php';

if ($phoneId > 0 && phone_by_id($phoneId)) {
    $exists = fetch_one('SELECT 1 FROM favorites WHERE user_id = ? AND phone_id = ?', [$user['id'], $phoneId]);
    if ($exists) {
        execute_sql('DELETE FROM favorites WHERE user_id = ? AND phone_id = ?', [$user['id'], $phoneId]);
        flash('已從收藏移除。');
    } else {
        execute_sql('INSERT INTO favorites (user_id, phone_id, created_at) VALUES (?, ?, NOW())', [$user['id'], $phoneId]);
        flash('已加入收藏。');
    }
}

redirect($back);

