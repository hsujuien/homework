<?php
declare(strict_types=1);

$assetPrefix = '../';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    execute_sql('DELETE FROM phones WHERE id = ?', [$id]);
    flash('手機資料已刪除。');
}

redirect('admin/phones.php');

