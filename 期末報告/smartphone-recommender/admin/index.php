<?php
declare(strict_types=1);

$assetPrefix = '../';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pageTitle = '管理者控制台';
$stats = [
    '手機資料' => fetch_one('SELECT COUNT(*) AS total FROM phones')['total'] ?? 0,
    '使用者帳號' => fetch_one('SELECT COUNT(*) AS total FROM users')['total'] ?? 0,
    '收藏紀錄' => fetch_one('SELECT COUNT(*) AS total FROM favorites')['total'] ?? 0,
    '推薦紀錄' => fetch_one('SELECT COUNT(*) AS total FROM recommendation_logs')['total'] ?? 0,
];
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <p class="eyebrow">管理者</p>
    <h1>管理員控制台功能</h1>
    <div class="stat-grid">
        <?php foreach ($stats as $label => $value): ?>
            <div class="stat-card">
                <span><?= h($label) ?></span>
                <strong><?= number_format((int)$value) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="admin-grid">
        <a class="admin-card" href="<?= h(url_for('admin/phone_form.php')) ?>">
            <h2>新增手機資料</h2>
            <p>手動填寫手機規格資訊。</p>
        </a>
        <a class="admin-card" href="<?= h(url_for('admin/crawler.php')) ?>">
            <h2>啟動爬蟲自動蒐集資料</h2>
            <p>貼上規格頁網址，擷取手機資料。</p>
        </a>
        <a class="admin-card" href="<?= h(url_for('admin/phones.php')) ?>">
            <h2>編輯規格細節</h2>
            <p>編輯、刪除、查詢手機規格。</p>
        </a>
        <a class="admin-card" href="<?= h(url_for('admin/weights.php')) ?>">
            <h2>修改權重分配</h2>
            <p>調整細項規格在各維度的評分權重。</p>
        </a>
        <a class="admin-card" href="<?= h(url_for('admin/users.php')) ?>">
            <h2>管理使用者帳號</h2>
            <p>檢視帳號與切換權限。</p>
        </a>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

