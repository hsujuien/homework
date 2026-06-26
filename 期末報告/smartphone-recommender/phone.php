<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$phone = phone_by_id((int)($_GET['id'] ?? 0));
if (!$phone) {
    http_response_code(404);
    exit('找不到手機資料。');
}

$scores = calculate_dimension_scores($phone);
$phone['dimension_scores'] = $scores;
$user = current_user();
$favoriteIds = favorite_phone_ids((int)$user['id']);

$pageTitle = $phone['brand'] . ' ' . $phone['model'];
require __DIR__ . '/includes/header.php';
?>

<section class="phone-detail">
    <div class="phone-detail-media">
        <img src="<?= h($phone['image_url'] ?: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=80') ?>" alt="<?= h($phone['brand'] . ' ' . $phone['model']) ?>">
    </div>
    <div class="phone-detail-copy">
        <p class="eyebrow"><?= h($phone['brand']) ?></p>
        <h1><?= h($phone['model']) ?></h1>
        <p class="lead">NT$ <?= number_format((int)$phone['price']) ?></p>
        <p class="muted">上市日期：<?= h(format_release_date($phone['release_date'] ?? '')) ?></p>
        <form method="post" action="<?= h(url_for('toggle_favorite.php')) ?>" class="inline-form">
            <input type="hidden" name="phone_id" value="<?= (int)$phone['id'] ?>">
            <input type="hidden" name="back" value="phone.php?id=<?= (int)$phone['id'] ?>">
            <button class="button <?= in_array((int)$phone['id'], $favoriteIds, true) ? 'saved' : '' ?>" type="submit">
                <?= in_array((int)$phone['id'], $favoriteIds, true) ? '已收藏' : '收藏喜愛手機' ?>
            </button>
        </form>
        <a class="button ghost" href="<?= h(url_for('compare.php?ids[]=' . (int)$phone['id'])) ?>">加入對比</a>
    </div>
</section>

<section class="section">
    <div class="result-layout">
        <div class="chart-panel">
            <canvas
                data-radar
                width="520"
                height="420"
                data-labels='<?= h(json_encode(array_values(DIMENSIONS), JSON_UNESCAPED_UNICODE)) ?>'
                data-series='<?= h(json_encode([phone_payload_for_chart($phone)], JSON_UNESCAPED_UNICODE)) ?>'
            ></canvas>
        </div>
        <div class="need-summary">
            <h2>七維度分數</h2>
            <?php foreach (DIMENSIONS as $key => $label): ?>
                <div class="summary-line">
                    <span><?= h($label) ?></span>
                    <strong><?= h((string)round($scores[$key])) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="table-wrap">
        <table class="spec-table">
            <tbody>
                <tr><th>上市日期</th><td><?= h(format_release_date($phone['release_date'] ?? '')) ?></td></tr>
                <tr><th>螢幕大小</th><td><?= h($phone['panel_type']) ?></td></tr>
                <tr><th>解析度 / PPI / 更新率</th><td><?= h($phone['resolution']) ?> / <?= (int)$phone['ppi'] ?> PPI / <?= (int)$phone['refresh_rate'] ?>Hz</td></tr>
                <tr><th>觸控採樣率 / 亮度</th><td><?= (int)$phone['touch_sampling_rate'] ?>Hz / <?= (int)$phone['brightness'] ?> nits</td></tr>
                <tr><th>CPU / 安兔兔</th><td><?= h($phone['cpu']) ?> / <?= number_format((int)$phone['antutu_score']) ?></td></tr>
                <tr><th>RAM / ROM</th><td><?= (int)$phone['ram_gb'] ?>GB / <?= (int)$phone['rom_gb'] ?>GB</td></tr>
                <tr><th>電池 / 充電</th><td><?= (int)$phone['battery_mah'] ?>mAh / 有線 <?= (int)$phone['wired_charging_w'] ?>W / 無線 <?= (int)$phone['wireless_charging_w'] ?>W</td></tr>
                <tr><th>相機</th><td>主 <?= h((string)$phone['main_camera_mp']) ?>MP、超廣角 <?= h((string)$phone['ultrawide_camera_mp']) ?>MP、長焦 <?= h((string)$phone['telephoto_camera_mp']) ?>MP、微距 <?= h((string)$phone['macro_camera_mp']) ?>MP、前鏡頭 <?= h((string)$phone['front_camera_mp']) ?>MP</td></tr>
                <tr><th>錄影規格</th><td><?= h($phone['video_spec']) ?></td></tr>
                <tr><th>通訊</th><td><?= h($phone['wifi']) ?> / 藍牙 <?= h($phone['bluetooth']) ?> / eSIM <?= ((int)$phone['esim']) ? '有' : '無' ?></td></tr>
                <tr><th>5G 支援</th><td><?= h(fiveg_support_label($phone['fiveg_bands'] ?? '')) ?></td></tr>
                <tr><th>功能</th><td>指紋 <?= ((int)$phone['fingerprint']) ? '有' : '無' ?>、臉部辨識 <?= ((int)$phone['face_unlock']) ? '有' : '無' ?>、防水 <?= h($phone['waterproof_rating']) ?>、散熱板 <?= ((int)$phone['cooling']) ? '有' : '無' ?></td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
