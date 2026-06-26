<?php
declare(strict_types=1);

$assetPrefix = '../';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$phone = $id ? phone_by_id($id) : null;

if (!function_exists('phone_form_value')) {
    function phone_form_value(?array $phone, string $key): string
    {
        return (string)($phone[$key] ?? '');
    }
}

if (!function_exists('phone_form_has_5g')) {
    function phone_form_has_5g(?array $phone): bool
    {
        return has_5g_support($phone['fiveg_bands'] ?? '');
    }
}

$fields = [
    ['brand', '品牌', 'text'],
    ['model', '型號', 'text'],
    ['price', '價格', 'number'],
    ['image_url', '圖片網址', 'url'],
    ['release_date', '上市日期', 'text'],
    ['panel_type', '螢幕大小', 'text'],
    ['resolution', '解析度', 'text'],
    ['ppi', 'PPI', 'number'],
    ['refresh_rate', '更新率', 'number'],
    ['touch_sampling_rate', '觸控採樣率', 'number'],
    ['brightness', '亮度', 'number'],
    ['cpu', 'CPU 型號', 'text'],
    ['antutu_score', '安兔兔跑分', 'number'],
    ['ram_gb', 'RAM GB', 'number'],
    ['rom_gb', 'ROM GB', 'number'],
    ['battery_mah', '電池容量', 'number'],
    ['wired_charging_w', '有線充電 W', 'number'],
    ['wireless_charging_w', '無線充電 W', 'number'],
    ['main_camera_mp', '主鏡頭 MP', 'number'],
    ['ultrawide_camera_mp', '超廣角 MP', 'number'],
    ['telephoto_camera_mp', '長焦 MP', 'number'],
    ['macro_camera_mp', '微距 MP', 'number'],
    ['front_camera_mp', '前鏡頭 MP', 'number'],
    ['video_spec', '錄影規格', 'text'],
    ['fiveg_bands', '5G 支援', 'select_5g'],
    ['wifi', 'Wi-Fi', 'text'],
    ['bluetooth', '藍牙', 'text'],
    ['waterproof_rating', '防水', 'text'],
    ['source_url', '資料來源網址', 'url'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    foreach ($fields as $field) {
        [$key, , $type] = $field;
        if ($type === 'select_5g') {
            $data[$key] = (string)($_POST[$key] ?? '0') === '1' ? '5G' : '';
            continue;
        }

        $data[$key] = trim((string)($_POST[$key] ?? ''));
    }
    foreach (['esim', 'fingerprint', 'face_unlock', 'cooling'] as $key) {
        $data[$key] = isset($_POST[$key]) ? 1 : 0;
    }
    $data['specs_json'] = $phone['specs_json'] ?? '{}';

    if ($id) {
        $data['id'] = $id;
        execute_sql(
            'UPDATE phones SET
                brand = :brand, model = :model, price = :price, image_url = :image_url, release_date = :release_date,
                panel_type = :panel_type, resolution = :resolution, ppi = :ppi, refresh_rate = :refresh_rate,
                touch_sampling_rate = :touch_sampling_rate, brightness = :brightness, cpu = :cpu, antutu_score = :antutu_score,
                ram_gb = :ram_gb, rom_gb = :rom_gb, battery_mah = :battery_mah, wired_charging_w = :wired_charging_w,
                wireless_charging_w = :wireless_charging_w, main_camera_mp = :main_camera_mp, ultrawide_camera_mp = :ultrawide_camera_mp,
                telephoto_camera_mp = :telephoto_camera_mp, macro_camera_mp = :macro_camera_mp, front_camera_mp = :front_camera_mp,
                video_spec = :video_spec, fiveg_bands = :fiveg_bands, wifi = :wifi, bluetooth = :bluetooth, esim = :esim,
                fingerprint = :fingerprint, face_unlock = :face_unlock, waterproof_rating = :waterproof_rating, cooling = :cooling,
                specs_json = :specs_json, source_url = :source_url, updated_at = NOW()
             WHERE id = :id',
            $data
        );
        flash('手機資料已更新。');
    } else {
        execute_sql(
            'INSERT INTO phones (
                brand, model, price, image_url, release_date, panel_type, resolution, ppi, refresh_rate,
                touch_sampling_rate, brightness, cpu, antutu_score, ram_gb, rom_gb, battery_mah,
                wired_charging_w, wireless_charging_w, main_camera_mp, ultrawide_camera_mp,
                telephoto_camera_mp, macro_camera_mp, front_camera_mp, video_spec, fiveg_bands,
                wifi, bluetooth, esim, fingerprint, face_unlock, waterproof_rating, cooling,
                specs_json, source_url, created_at, updated_at
            ) VALUES (
                :brand, :model, :price, :image_url, :release_date, :panel_type, :resolution, :ppi, :refresh_rate,
                :touch_sampling_rate, :brightness, :cpu, :antutu_score, :ram_gb, :rom_gb, :battery_mah,
                :wired_charging_w, :wireless_charging_w, :main_camera_mp, :ultrawide_camera_mp,
                :telephoto_camera_mp, :macro_camera_mp, :front_camera_mp, :video_spec, :fiveg_bands,
                :wifi, :bluetooth, :esim, :fingerprint, :face_unlock, :waterproof_rating, :cooling,
                :specs_json, :source_url, NOW(), NOW()
            )',
            $data
        );
        flash('手機資料已新增。');
    }

    redirect('admin/phones.php');
}

$pageTitle = $phone ? '編輯手機' : '新增手機';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow"><?= $phone ? '編輯規格細節' : '新增手機資料' ?></p>
            <h1><?= h($pageTitle) ?></h1>
        </div>
        <a class="button ghost" href="<?= h(url_for('admin/phones.php')) ?>">返回列表</a>
    </div>
    <form class="admin-form" method="post">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <div class="form-grid">
            <?php foreach ($fields as [$key, $label, $type]): ?>
                <label class="<?= $type === 'textarea' ? 'span-2' : '' ?>"><?= h($label) ?>
                    <?php if ($type === 'textarea'): ?>
                        <textarea name="<?= h($key) ?>" rows="4"><?= h(phone_form_value($phone, $key)) ?></textarea>
                    <?php elseif ($type === 'select_5g'): ?>
                        <select name="<?= h($key) ?>">
                            <option value="1" <?= selected(phone_form_has_5g($phone) ? '1' : '0', '1') ?>>有</option>
                            <option value="0" <?= selected(phone_form_has_5g($phone) ? '1' : '0', '0') ?>>無</option>
                        </select>
                    <?php else: ?>
                        <input
                            type="<?= h($type) ?>"
                            name="<?= h($key) ?>"
                            value="<?= h(phone_form_value($phone, $key)) ?>"
                            <?= in_array($key, ['brand', 'model'], true) ? 'required' : '' ?>
                            <?= $type === 'number' ? 'step="any"' : '' ?>
                        >
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="toggle-grid">
            <?php foreach (['esim' => 'eSIM', 'fingerprint' => '指紋辨識', 'face_unlock' => '臉部辨識', 'cooling' => '散熱板'] as $key => $label): ?>
                <label class="switch-line">
                    <input type="checkbox" name="<?= h($key) ?>" <?= checked((int)($phone[$key] ?? 0) === 1) ?>>
                    <?= h($label) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <button class="button lg" type="submit">儲存手機資料</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
