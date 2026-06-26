<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = '選擇你的需求偏好';
require __DIR__ . '/includes/header.php';
?>

<section class="section narrow">
    <p class="eyebrow">需求問卷</p>
    <h1>選擇你的需求偏好</h1>
    <p class="lead">依照你的需求調整各項維度，系統將為你推薦最適合的手機。</p>
    <form class="questionnaire" method="post" action="<?= h(url_for('recommend.php')) ?>">
        <?php foreach (DIMENSIONS as $key => $label): ?>
            <fieldset class="need-card">
                <legend><?= h($label) ?></legend>
                <div class="choice-row">
                    <?php foreach (array_reverse(NEED_LEVELS, true) as $value => $text): ?>
                        <label class="choice-pill">
                            <input type="radio" name="needs[<?= h($key) ?>]" value="<?= (int)$value ?>" <?= checked($value === 2) ?>>
                            <span><?= h($text) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        <?php endforeach; ?>
        <button class="button lg block" type="submit">獲取推薦</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

