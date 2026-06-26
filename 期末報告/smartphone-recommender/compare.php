<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$ids = $_GET['ids'] ?? [];
if (!is_array($ids)) {
    $ids = [$ids];
}

$selectedPhones = phones_by_ids($ids);
$allPhones = fetch_all('SELECT id, brand, model FROM phones ORDER BY brand, model');
$selectedPhoneScores = [];
foreach ($selectedPhones as $phone) {
    $selectedPhoneScores[(int)$phone['id']] = calculate_dimension_scores($phone);
}
$chartSeries = array_map(function (array $phone): array {
    $phone['dimension_scores'] = calculate_dimension_scores($phone);
    return phone_payload_for_chart($phone);
}, $selectedPhones);

$pageTitle = '規格比較';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow">規格比較</p>
            <h1>支援多款手機規格重疊對比</h1>
        </div>
    </div>
    <form class="compare-picker" method="get">
        <label>選擇手機
            <select name="ids[]" multiple size="6">
                <?php foreach ($allPhones as $phone): ?>
                    <option value="<?= (int)$phone['id'] ?>" <?= in_array((int)$phone['id'], array_map('intval', $ids), true) ? 'selected' : '' ?>>
                        <?= h($phone['brand'] . ' ' . $phone['model']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="button" type="submit">開始對比</button>
    </form>
</section>

<?php if ($selectedPhones): ?>
<section class="section">
    <div class="result-layout">
        <div class="chart-panel">
            <canvas
                data-radar
                width="540"
                height="430"
                data-labels='<?= h(json_encode(array_values(DIMENSIONS), JSON_UNESCAPED_UNICODE)) ?>'
                data-series='<?= h(json_encode($chartSeries, JSON_UNESCAPED_UNICODE)) ?>'
            ></canvas>
        </div>
        <div class="need-summary">
            <h2>七維度總覽</h2>
            <div class="score-card-list">
                <?php foreach ($selectedPhones as $phone): ?>
                    <?php $scores = $selectedPhoneScores[(int)$phone['id']] ?? calculate_dimension_scores($phone); ?>
                    <article class="score-card">
                        <h3><?= h($phone['brand'] . ' ' . $phone['model']) ?></h3>
                        <?php foreach (DIMENSIONS as $key => $label): ?>
                            <div class="score-line">
                                <span><?= h($label) ?></span>
                                <strong><?= h((string)round($scores[$key] ?? 0)) ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <div class="score-line score-total-row">
                            <span>總平均</span>
                            <strong><?= h((string)round(array_sum($scores) / count($scores))) ?></strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="spec-table">
            <colgroup>
                <col class="spec-label-col">
                <?php foreach ($selectedPhones as $_): ?>
                    <col class="spec-phone-col">
                <?php endforeach; ?>
            </colgroup>
            <thead>
                <tr>
                    <th>規格</th>
                    <?php foreach ($selectedPhones as $phone): ?>
                        <th><?= h($phone['brand'] . ' ' . $phone['model']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $text = static fn($value): string => trim((string)$value) !== '' ? (string)$value : '未提供';
                $number = static function ($value, string $unit = ''): string {
                    $number = (float)$value;
                    if ($number <= 0) {
                        return '未提供';
                    }
                    $formatted = rtrim(rtrim((string)$number, '0'), '.');
                    return $formatted . $unit;
                };
                $integer = static function ($value, string $unit = ''): string {
                    $number = (int)$value;
                    return $number > 0 ? number_format($number) . $unit : '未提供';
                };
                $yesNo = static fn($value): string => ((int)$value) === 1 ? '有' : '無';
                $specGroups = [
                    '基本資訊' => [
                        ['價格', fn($p) => ((int)$p['price'] > 0 ? 'NT$ ' . number_format((int)$p['price']) : '未提供')],
                        ['上市日期', fn($p) => format_release_date($p['release_date'] ?? '')],
                    ],
                    '螢幕' => [
                        ['螢幕大小', fn($p) => $text($p['panel_type'] ?? '')],
                        ['解析度', fn($p) => $text($p['resolution'] ?? '')],
                        ['PPI', fn($p) => $integer($p['ppi'] ?? 0, ' PPI')],
                        ['更新率', fn($p) => $integer($p['refresh_rate'] ?? 0, 'Hz')],
                        ['觸控採樣率', fn($p) => $integer($p['touch_sampling_rate'] ?? 0, 'Hz')],
                        ['亮度', fn($p) => $integer($p['brightness'] ?? 0, ' nits')],
                    ],
                    '性能' => [
                        ['CPU', fn($p) => $text($p['cpu'] ?? '')],
                        ['安兔兔跑分', fn($p) => $integer($p['antutu_score'] ?? 0)],
                    ],
                    '儲存' => [
                        ['RAM', fn($p) => $number($p['ram_gb'] ?? 0, 'GB')],
                        ['ROM', fn($p) => $number($p['rom_gb'] ?? 0, 'GB')],
                    ],
                    '相機' => [
                        ['主鏡頭', fn($p) => $number($p['main_camera_mp'] ?? 0, 'MP')],
                        ['超廣角', fn($p) => ((float)($p['ultrawide_camera_mp'] ?? 0) > 0 ? $number($p['ultrawide_camera_mp'], 'MP') : '無')],
                        ['長焦', fn($p) => ((float)($p['telephoto_camera_mp'] ?? 0) > 0 ? $number($p['telephoto_camera_mp'], 'MP') : '無')],
                        ['微距', fn($p) => ((float)($p['macro_camera_mp'] ?? 0) > 0 ? $number($p['macro_camera_mp'], 'MP') : '無')],
                        ['前鏡頭', fn($p) => $number($p['front_camera_mp'] ?? 0, 'MP')],
                        ['錄影規格', fn($p) => $text($p['video_spec'] ?? '')],
                    ],
                    '續航' => [
                        ['電池容量', fn($p) => $integer($p['battery_mah'] ?? 0, 'mAh')],
                        ['有線充電', fn($p) => ((int)($p['wired_charging_w'] ?? 0) > 0 ? $integer($p['wired_charging_w'], 'W') : '未提供')],
                        ['無線充電', fn($p) => ((int)($p['wireless_charging_w'] ?? 0) > 0 ? $integer($p['wireless_charging_w'], 'W') : '無')],
                    ],
                    '通訊' => [
                        ['5G 支援', fn($p) => fiveg_support_label($p['fiveg_bands'] ?? '')],
                        ['Wi-Fi', fn($p) => $text($p['wifi'] ?? '')],
                        ['藍牙', fn($p) => $text($p['bluetooth'] ?? '')],
                        ['eSIM', fn($p) => $yesNo($p['esim'] ?? 0)],
                    ],
                    '功能' => [
                        ['指紋辨識', fn($p) => $yesNo($p['fingerprint'] ?? 0)],
                        ['臉部辨識', fn($p) => $yesNo($p['face_unlock'] ?? 0)],
                        ['防水', fn($p) => $text($p['waterproof_rating'] ?? '')],
                        ['散熱板', fn($p) => $yesNo($p['cooling'] ?? 0)],
                    ],
                ];
                ?>
                <?php foreach ($specGroups as $groupLabel => $groupRows): ?>
                    <tr class="spec-group-row">
                        <th colspan="<?= count($selectedPhones) + 1 ?>"><?= h($groupLabel) ?></th>
                    </tr>
                    <?php foreach ($groupRows as [$label, $render]): ?>
                    <tr>
                        <th><?= h($label) ?></th>
                        <?php foreach ($selectedPhones as $phone): ?>
                            <td><?= h($render($phone)) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
