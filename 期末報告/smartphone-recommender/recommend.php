<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$needs = $_POST['needs'] ?? $_SESSION['last_needs'] ?? [];
foreach (DIMENSIONS as $key => $label) {
    $needs[$key] = max(0, min(4, (int)($needs[$key] ?? 2)));
}
$_SESSION['last_needs'] = $needs;

$phones = recommend_phones($needs, 12);
$user = current_user();
save_recommendation_log((int)$user['id'], $needs, $phones);
$favoriteIds = favorite_phone_ids((int)$user['id']);
$chartSeries = array_map('phone_payload_for_chart', array_slice($phones, 0, 3));

$pageTitle = '推薦結果';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow">獲取推薦</p>
            <h1>最適合你的手機清單</h1>
        </div>
        <a class="button ghost" href="<?= h(url_for('questionnaire.php')) ?>">重新填答</a>
    </div>
    <div class="result-layout">
        <div class="chart-panel">
            <canvas
                data-radar
                width="520"
                height="420"
                data-labels='<?= h(json_encode(array_values(DIMENSIONS), JSON_UNESCAPED_UNICODE)) ?>'
                data-series='<?= h(json_encode($chartSeries, JSON_UNESCAPED_UNICODE)) ?>'
            ></canvas>
        </div>
        <div class="need-summary">
            <h2>你的需求</h2>
            <?php foreach (DIMENSIONS as $key => $label): ?>
                <div class="summary-line">
                    <span><?= h($label) ?></span>
                    <strong><?= h(NEED_LEVELS[$needs[$key]]) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="phone-grid">
        <?php foreach ($phones as $index => $phone): ?>
            <article class="phone-card">
                <img src="<?= h($phone['image_url'] ?: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=80') ?>" alt="<?= h($phone['brand'] . ' ' . $phone['model']) ?>">
                <div class="phone-card-body">
                    <div class="rank">#<?= $index + 1 ?>　匹配度 <?= h((string)$phone['match_score']) ?></div>
                    <p class="muted"><?= h($phone['brand']) ?></p>
                    <h3><?= h($phone['model']) ?></h3>
                    <p>NT$ <?= number_format((int)$phone['price']) ?></p>
                    <p class="muted">上市日期：<?= h(format_release_date($phone['release_date'] ?? '')) ?></p>
                    <div class="score-row">
                        <?php foreach (DIMENSIONS as $key => $label): ?>
                            <span><?= h($label) ?> <?= h((string)round($phone['dimension_scores'][$key])) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <form method="post" action="<?= h(url_for('toggle_favorite.php')) ?>" class="inline-form">
                        <input type="hidden" name="phone_id" value="<?= (int)$phone['id'] ?>">
                        <input type="hidden" name="back" value="recommend.php">
                        <button class="button <?= in_array((int)$phone['id'], $favoriteIds, true) ? 'saved' : 'ghost' ?> block" type="submit">
                            <?= in_array((int)$phone['id'], $favoriteIds, true) ? '已收藏' : '收藏喜愛手機' ?>
                        </button>
                    </form>
                    <a class="button ghost block" href="<?= h(url_for('phone.php?id=' . (int)$phone['id'])) ?>">查看完整規格</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
