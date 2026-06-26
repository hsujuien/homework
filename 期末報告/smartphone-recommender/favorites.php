<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$phones = fetch_all(
    'SELECT p.* FROM phones p INNER JOIN favorites f ON f.phone_id = p.id WHERE f.user_id = ? ORDER BY f.created_at DESC',
    [$user['id']]
);

$pageTitle = '收藏喜愛手機';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow">收藏喜愛手機</p>
            <h1>追蹤心儀手機</h1>
        </div>
        <a class="button ghost" href="<?= h(url_for('questionnaire.php')) ?>">取得更多推薦</a>
    </div>
    <?php if (!$phones): ?>
        <div class="empty-state">尚未收藏手機。完成需求問卷後，可以把推薦結果加入收藏。</div>
    <?php else: ?>
        <form method="get" action="<?= h(url_for('compare.php')) ?>">
            <div class="phone-grid">
                <?php foreach ($phones as $phone): ?>
                    <?php $scores = calculate_dimension_scores($phone); ?>
                    <article class="phone-card">
                        <img src="<?= h($phone['image_url'] ?: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=80') ?>" alt="<?= h($phone['brand'] . ' ' . $phone['model']) ?>">
                        <div class="phone-card-body">
                            <label class="compare-check">
                                <input type="checkbox" name="ids[]" value="<?= (int)$phone['id'] ?>">
                                加入對比
                            </label>
                            <p class="muted"><?= h($phone['brand']) ?></p>
                            <h3><?= h($phone['model']) ?></h3>
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
            <button class="button floating-action" type="submit">對比選取手機</button>
        </form>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
