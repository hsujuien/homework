<?php
declare(strict_types=1);

$assetPrefix = '../';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$keyword = trim($_GET['q'] ?? '');
$params = [];
$where = '';
if ($keyword !== '') {
    $where = 'WHERE brand LIKE ? OR model LIKE ? OR cpu LIKE ?';
    $params = ["%$keyword%", "%$keyword%", "%$keyword%"];
}
$phones = fetch_all("SELECT * FROM phones $where ORDER BY updated_at DESC, brand, model", $params);

$pageTitle = '手機資料管理';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow">編輯規格細節</p>
            <h1>手機資料管理</h1>
        </div>
        <a class="button" href="<?= h(url_for('admin/phone_form.php')) ?>">新增手機資料</a>
    </div>
    <form class="toolbar-form" method="get">
        <input type="search" name="q" value="<?= h($keyword) ?>" placeholder="查詢品牌、型號、CPU">
        <button class="button ghost" type="submit">查詢</button>
    </form>
    <div class="table-wrap">
        <table class="spec-table">
            <thead>
                <tr>
                    <th>品牌</th>
                    <th>型號</th>
                    <th>價格</th>
                    <th>CPU</th>
                    <th>RAM/ROM</th>
                    <th>更新時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($phones as $phone): ?>
                    <tr>
                        <td><?= h($phone['brand']) ?></td>
                        <td><?= h($phone['model']) ?></td>
                        <td>NT$ <?= number_format((int)$phone['price']) ?></td>
                        <td><?= h($phone['cpu']) ?></td>
                        <td><?= (int)$phone['ram_gb'] ?> / <?= (int)$phone['rom_gb'] ?>GB</td>
                        <td><?= h($phone['updated_at']) ?></td>
                        <td class="action-cell">
                            <a class="button ghost sm" href="<?= h(url_for('admin/phone_form.php?id=' . (int)$phone['id'])) ?>">編輯</a>
                            <form method="post" action="<?= h(url_for('admin/delete_phone.php')) ?>" onsubmit="return confirm('確定刪除這支手機？')">
                                <input type="hidden" name="id" value="<?= (int)$phone['id'] ?>">
                                <button class="button danger sm" type="submit">刪除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

