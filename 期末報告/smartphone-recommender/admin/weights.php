<?php
declare(strict_types=1);

$assetPrefix = '../';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['weights'] ?? [] as $id => $weight) {
        execute_sql('UPDATE dimension_weights SET weight = ? WHERE id = ?', [(float)$weight, (int)$id]);
    }
    set_setting('match_tolerance', (string)max(0, min(100, (float)($_POST['match_tolerance'] ?? 28))));
    flash('權重與精準度設定已更新。');
    redirect('admin/weights.php');
}

$rows = fetch_all('SELECT * FROM dimension_weights ORDER BY dimension, id');
$groups = [];
foreach ($rows as $row) {
    $groups[$row['dimension']][] = $row;
}
$metricLabelOverrides = [
    'panel_type' => '螢幕大小',
    'fiveg_bands' => '5G支援',
    'cooling' => '散熱板',
];

$pageTitle = '權重分配';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow">修改權重分配</p>
            <h1>評分權重與匹配精準度</h1>
        </div>
        <a class="button ghost" href="<?= h(url_for('admin/index.php')) ?>">返回控制台</a>
    </div>
    <form class="admin-form" method="post">
        <label class="tolerance-control">最大容忍誤差
            <input type="number" name="match_tolerance" min="0" max="100" step="1" value="<?= h(get_setting('match_tolerance', '28')) ?>">
            <span class="muted">數值越小，推薦越嚴格；數值越大，推薦越寬鬆。</span>
        </label>
        <div class="weights-grid">
            <?php foreach ($groups as $dimension => $items): ?>
                <section class="weight-card">
                    <h2><?= h(DIMENSIONS[$dimension] ?? $dimension) ?></h2>
                    <?php foreach ($items as $item): ?>
                        <label class="weight-line">
                            <span><?= h($metricLabelOverrides[$item['metric_key']] ?? $item['label']) ?></span>
                            <input type="number" name="weights[<?= (int)$item['id'] ?>]" min="0" step="0.1" value="<?= h((string)$item['weight']) ?>">
                        </label>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
        <button class="button lg" type="submit">儲存權重分配</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
