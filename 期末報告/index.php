<?php
declare(strict_types=1);

require_once __DIR__ . '/smartphone-recommender/includes/functions.php';

$pageTitle = '首頁';
$homePhoneInitialLimit = 8;
$homePhonePageSize = 8;
$allPhones = fetch_all('SELECT * FROM phones ORDER BY updated_at DESC, brand, model');
$phoneTotal = count($allPhones);
require __DIR__ . '/smartphone-recommender/includes/header.php';
?>

<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">依需求精準推薦</p>
        <h1>找出最適合你的手機型號</h1>
        <p>依照七大維度權重計算，將規格資料轉換成直觀分數，推薦符合你需求的手機。</p>
        <div class="hero-actions">
            <a class="button lg" href="<?= h(url_for('questionnaire.php')) ?>">開始選擇需求偏好</a>
            <a class="button ghost lg" href="<?= h(url_for('compare.php')) ?>">進行規格比較</a>
        </div>
    </div>
    <div class="hero-panel">
        <canvas
            data-radar
            width="460"
            height="360"
            data-labels='<?= h(json_encode(array_values(DIMENSIONS), JSON_UNESCAPED_UNICODE)) ?>'
            data-series='<?= h(json_encode([
                ['name' => '理想旗艦', 'scores' => ['display' => 92, 'performance' => 95, 'storage' => 88, 'camera' => 90, 'battery' => 84, 'communication' => 96, 'features' => 89]],
                ['name' => '均衡機型', 'scores' => ['display' => 78, 'performance' => 74, 'storage' => 72, 'camera' => 70, 'battery' => 86, 'communication' => 78, 'features' => 76]],
            ], JSON_UNESCAPED_UNICODE)) ?>'
        ></canvas>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow">七維度分析圖</p>
            <h2>視覺化比較手機各項能力表現</h2>
        </div>
        <a class="link" href="<?= h(url_for('questionnaire.php')) ?>">填答需求問卷</a>
    </div>
    <div class="feature-grid">
        <?php foreach (DIMENSIONS as $key => $label): ?>
            <article class="feature-card">
                <span class="feature-icon"><?= h(first_char($label)) ?></span>
                <h3><?= h($label) ?></h3>
                <p><?= h(match ($key) {
                    'display' => '螢幕大小、解析度、PPI、更新率、觸控採樣率、亮度',
                    'features' => '指紋/臉部辨識、防水、散熱板',
                    'communication' => '5G支援、Wi-Fi、藍牙、eSIM',
                    'performance' => 'CPU型號、安兔兔跑分',
                    'storage' => 'RAM、ROM容量',
                    'battery' => '電池容量、有線/無線充電瓦數',
                    'camera' => '主鏡頭、超廣角、長焦、微距、前鏡頭、錄影規格',
                }) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow">手機資料庫</p>
            <h2>可推薦與比較的手機</h2>
            <p class="muted section-note">目前收錄 <?= (int)$phoneTotal ?> 款手機</p>
        </div>
        <?php if (is_admin()): ?>
            <a class="button sm" href="<?= h(url_for('admin/phones.php')) ?>">管理手機資料</a>
        <?php endif; ?>
    </div>
    <?php if (!$allPhones): ?>
        <div class="empty-state">尚未建立手機資料。</div>
    <?php else: ?>
    <div class="phone-grid" id="home-phone-list" data-phone-list>
        <?php foreach ($allPhones as $index => $phone): ?>
            <?php $scores = calculate_dimension_scores($phone); ?>
            <?php $isExtraPhone = $index >= $homePhoneInitialLimit; ?>
            <article class="phone-card<?= $isExtraPhone ? ' is-hidden' : '' ?>" <?= $isExtraPhone ? 'hidden data-extra-phone' : '' ?>>
                <img src="<?= h($phone['image_url'] ?: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=80') ?>" alt="<?= h($phone['brand'] . ' ' . $phone['model']) ?>">
                <div class="phone-card-body">
                    <p class="muted"><?= h($phone['brand']) ?></p>
                    <h3><?= h($phone['model']) ?></h3>
                    <p>NT$ <?= number_format((int)$phone['price']) ?></p>
                    <p class="muted">上市日期：<?= h(format_release_date($phone['release_date'] ?? '')) ?></p>
                    <div class="score-row">
                        <?php foreach (array_slice(DIMENSIONS, 0, 4, true) as $key => $label): ?>
                            <span><?= h($label) ?> <?= h((string)round($scores[$key])) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <a class="button ghost block" href="<?= h(url_for('phone.php?id=' . (int)$phone['id'])) ?>">查看規格</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
        <?php if ($phoneTotal > $homePhoneInitialLimit): ?>
            <div class="load-more-wrap" data-show-more-wrap>
                <button class="button ghost" type="button" data-show-more-phones data-phone-list-target="#home-phone-list" data-page-size="<?= (int)$homePhonePageSize ?>">查看更多手機</button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/smartphone-recommender/includes/footer.php'; ?>
