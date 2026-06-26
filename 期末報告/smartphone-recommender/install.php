<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$schema = file_get_contents(__DIR__ . '/database/schema.sql');
db()->exec($schema);

$adminExists = fetch_one('SELECT id FROM users WHERE email = ?', ['admin@example.com']);
if (!$adminExists) {
    execute_sql(
        'INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())',
        ['系統管理者', 'admin@example.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin']
    );
}

$userExists = fetch_one('SELECT id FROM users WHERE email = ?', ['user@example.com']);
if (!$userExists) {
    execute_sql(
        'INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())',
        ['一般使用者', 'user@example.com', password_hash('user123', PASSWORD_DEFAULT), 'user']
    );
}

$weights = [
    ['display', 'panel_type', '螢幕大小', 1.2],
    ['display', 'resolution', '解析度', 1.0],
    ['display', 'ppi', 'PPI', 1.1],
    ['display', 'refresh_rate', '更新率', 1.2],
    ['display', 'touch_sampling_rate', '觸控採樣率', 0.8],
    ['display', 'brightness', '亮度', 1.0],
    ['performance', 'cpu', 'CPU型號', 1.0],
    ['performance', 'antutu_score', '安兔兔跑分', 1.4],
    ['storage', 'ram_gb', 'RAM', 1.1],
    ['storage', 'rom_gb', 'ROM容量', 1.0],
    ['battery', 'battery_mah', '電池容量', 1.2],
    ['battery', 'wired_charging_w', '有線充電瓦數', 1.0],
    ['battery', 'wireless_charging_w', '無線充電瓦數', 0.7],
    ['communication', 'fiveg_bands', '5G支援', 1.2],
    ['communication', 'wifi', 'Wi-Fi', 1.0],
    ['communication', 'bluetooth', '藍牙', 0.8],
    ['communication', 'esim', 'eSIM', 0.8],
    ['features', 'fingerprint', '指紋辨識', 0.8],
    ['features', 'face_unlock', '臉部辨識', 0.8],
    ['features', 'waterproof_rating', '防水', 1.1],
    ['features', 'cooling', '散熱板', 0.9],
    ['camera', 'main_camera_mp', '主鏡頭', 1.2],
    ['camera', 'ultrawide_camera_mp', '超廣角', 0.9],
    ['camera', 'telephoto_camera_mp', '長焦', 1.0],
    ['camera', 'macro_camera_mp', '微距', 0.45],
    ['camera', 'front_camera_mp', '前鏡頭', 0.7],
    ['camera', 'video_spec', '錄影規格', 1.0],
];

foreach ($weights as $weight) {
    execute_sql(
        'INSERT INTO dimension_weights (dimension, metric_key, label, weight) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE label = VALUES(label), weight = VALUES(weight)',
        $weight
    );
}

set_setting('match_tolerance', get_setting('match_tolerance', '28'));

$phones = [
    [
        'Apple', 'iPhone 15 Pro', 36900, 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=900&q=80', '2023-09',
        '6.1 inch', '2556 x 1179', 460, 120, 240, 2000,
        'A17 Pro', 1640000, 8, 256, 3274, 27, 15,
        48, 12, 12, 0, 12, '4K 60fps ProRes',
        'n1/n2/n3/n5/n7/n8/n12/n20/n25/n28/n30/n38/n40/n41/n48/n66/n70/n77/n78/n79',
        'Wi-Fi 6E', '5.3', 1, 0, 1, 'IP68', 1,
    ],
    [
        'Samsung', 'Galaxy S24 Ultra', 43900, 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?auto=format&fit=crop&w=900&q=80', '2024-01',
        '6.8 inch', '3120 x 1440', 505, 120, 240, 2600,
        'Snapdragon 8 Gen 3 for Galaxy', 1880000, 12, 512, 5000, 45, 15,
        200, 12, 50, 0, 12, '8K 30fps / 4K 120fps',
        'n1/n2/n3/n5/n7/n8/n12/n20/n25/n28/n38/n40/n41/n66/n71/n77/n78/n79',
        'Wi-Fi 7', '5.3', 1, 1, 1, 'IP68', 1,
    ],
    [
        'Google', 'Pixel 8 Pro', 33990, 'https://images.unsplash.com/photo-1664478546384-d57ffe74a78c?auto=format&fit=crop&w=900&q=80', '2023-10',
        '6.7 inch', '2992 x 1344', 489, 120, 240, 2400,
        'Google Tensor G3', 1150000, 12, 256, 5050, 30, 23,
        50, 48, 48, 0, 10.5, '4K 60fps',
        'n1/n2/n3/n5/n7/n8/n12/n20/n25/n28/n30/n38/n40/n41/n48/n66/n71/n77/n78',
        'Wi-Fi 7', '5.3', 1, 1, 1, 'IP68', 1,
    ],
    [
        'ASUS', 'ROG Phone 8 Pro', 36990, 'https://images.unsplash.com/photo-1605236453806-6ff36851218e?auto=format&fit=crop&w=900&q=80', '2024-01',
        '6.78 inch', '2400 x 1080', 388, 165, 720, 2500,
        'Snapdragon 8 Gen 3', 2110000, 16, 512, 5500, 65, 15,
        50, 13, 32, 0, 32, '8K 24fps / 4K 60fps',
        'n1/n2/n3/n5/n7/n8/n12/n20/n25/n28/n38/n40/n41/n66/n77/n78/n79',
        'Wi-Fi 7', '5.3', 1, 1, 1, 'IP68', 1,
    ],
    [
        'Sony', 'Xperia 1 V', 39990, 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?auto=format&fit=crop&w=900&q=80', '2023-05',
        '6.5 inch', '3840 x 1644', 643, 120, 240, 1000,
        'Snapdragon 8 Gen 2', 1520000, 12, 256, 5000, 30, 0,
        48, 12, 12, 0, 12, '4K 120fps',
        'n1/n3/n5/n7/n8/n28/n38/n40/n41/n77/n78/n79',
        'Wi-Fi 6E', '5.3', 0, 1, 1, 'IP65/IP68', 1,
    ],
    [
        'Xiaomi', '14 Ultra', 32999, 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?auto=format&fit=crop&w=900&q=80', '2024-02',
        '6.73 inch', '3200 x 1440', 522, 120, 240, 3000,
        'Snapdragon 8 Gen 3', 2040000, 16, 512, 5000, 90, 80,
        50, 50, 50, 0, 32, '8K 30fps / 4K 120fps',
        'n1/n2/n3/n5/n7/n8/n12/n20/n28/n38/n40/n41/n66/n77/n78/n79',
        'Wi-Fi 7', '5.4', 1, 1, 1, 'IP68', 1,
    ],
];

foreach ($phones as $phone) {
    execute_sql(
        'INSERT IGNORE INTO phones (
            brand, model, price, image_url, release_date, panel_type, resolution, ppi, refresh_rate,
            touch_sampling_rate, brightness, cpu, antutu_score, ram_gb, rom_gb, battery_mah,
            wired_charging_w, wireless_charging_w, main_camera_mp, ultrawide_camera_mp,
            telephoto_camera_mp, macro_camera_mp, front_camera_mp, video_spec, fiveg_bands,
            wifi, bluetooth, esim, fingerprint, face_unlock, waterproof_rating, cooling,
            specs_json, source_url, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
        )',
        [
            ...$phone,
            json_encode(['seed' => true], JSON_UNESCAPED_UNICODE),
            '',
        ]
    );
}

$isCli = PHP_SAPI === 'cli';
if ($isCli) {
    echo "資料庫初始化完成。\n";
    echo "管理者：admin@example.com / admin123\n";
    echo "一般使用者：user@example.com / user123\n";
} else {
    flash('資料庫初始化完成。管理者：admin@example.com / admin123；一般使用者：user@example.com / user123');
    redirect('index.php');
}
