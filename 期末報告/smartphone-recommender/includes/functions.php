<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

const DIMENSIONS = [
    'display' => '螢幕',
    'performance' => '性能',
    'storage' => '儲存',
    'camera' => '相機',
    'battery' => '續航',
    'communication' => '通訊',
    'features' => '功能',
];

const NEED_LEVELS = [
    4 => '超高',
    3 => '高',
    2 => '普通',
    1 => '低',
    0 => '沒差',
];

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function text_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function text_upper(string $value): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
}

function first_char(string $value): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, 1, 'UTF-8');
    }

    return substr($value, 0, 1);
}

function app_base_url(): string
{
    $base = defined('APP_PUBLIC_BASE') ? (string)APP_PUBLIC_BASE : '';
    if ($base === '' || $base === '/') {
        return '/';
    }

    return '/' . trim($base, '/') . '/';
}

function url_for(string $path = ''): string
{
    $path = trim($path);
    if ($path === '') {
        return app_base_url();
    }

    if (preg_match('/^(?:https?:)?\/\//i', $path) || str_starts_with($path, '#') || str_starts_with($path, 'mailto:') || str_starts_with($path, 'tel:')) {
        return $path;
    }

    $base = app_base_url();
    if (str_starts_with($path, $base)) {
        return $path;
    }

    return $base . ltrim($path, '/');
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }

    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function selected($value, $expected): string
{
    return (string)$value === (string)$expected ? 'selected' : '';
}

function checked(bool $condition): string
{
    return $condition ? 'checked' : '';
}

function get_setting(string $key, string $default = ''): string
{
    $row = fetch_one('SELECT value FROM system_settings WHERE `key` = ?', [$key]);
    return $row['value'] ?? $default;
}

function set_setting(string $key, string $value): void
{
    execute_sql(
        'INSERT INTO system_settings (`key`, value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value)',
        [$key, $value]
    );
}

function normalize_score(float $value, float $max): float
{
    if ($max <= 0) {
        return 0.0;
    }

    return max(0.0, min(100.0, ($value / $max) * 100));
}

function bool_score($value): float
{
    return ((int)$value) === 1 ? 100.0 : 0.0;
}

function format_release_date(?string $releaseDate): string
{
    $releaseDate = trim((string)$releaseDate);
    if ($releaseDate === '') {
        return '未提供';
    }

    if (preg_match('/^(\d{4})[-\/年.](\d{1,2})(?:[-\/月.](\d{1,2}))?/u', $releaseDate, $matches)) {
        $year = $matches[1];
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        if (!empty($matches[3])) {
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            return "{$year}年{$month}月{$day}日";
        }
        return "{$year}年{$month}月";
    }

    if (preg_match('/^(\d{4})$/', $releaseDate, $matches)) {
        return $matches[1] . '年';
    }

    return $releaseDate;
}

function has_5g_support(?string $fiveg): bool
{
    $fiveg = trim((string)$fiveg);
    if ($fiveg === '') {
        return false;
    }

    $lower = text_lower($fiveg);
    if (preg_match('/^(no|none|false|n\/a|na|0|無|否|不支援|-)+$/u', $lower)) {
        return false;
    }

    if (str_contains($lower, '5g') || preg_match('/(?:^|[^\w])n\d{1,3}(?:$|[^\w])/i', $fiveg)) {
        return true;
    }

    if (preg_match('/\b(4g|lte|3g|2g)\b/i', $lower)) {
        return false;
    }

    return true;
}

function fiveg_support_label(?string $fiveg): string
{
    return has_5g_support($fiveg) ? '有' : '無';
}

function screen_size_score(?string $screenSize): float
{
    $screenSize = trim((string)$screenSize);
    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:吋|inch|inches|")/iu', $screenSize, $matches)) {
        return normalize_score((float)$matches[1], 7.0);
    }

    return panel_score($screenSize);
}

function panel_score(?string $panel): float
{
    $panel = text_lower((string)$panel);
    if (str_contains($panel, 'ltpo')) {
        return 100.0;
    }
    if (str_contains($panel, 'amoled') || str_contains($panel, 'oled')) {
        return 88.0;
    }
    if (str_contains($panel, 'mini')) {
        return 82.0;
    }
    if (str_contains($panel, 'ips')) {
        return 65.0;
    }
    return 50.0;
}

function wifi_score(?string $wifi): float
{
    $wifi = text_lower((string)$wifi);
    if ($wifi === '') {
        return 0.0;
    }
    if (str_contains($wifi, '7') || str_contains($wifi, '802.11be')) {
        return 100.0;
    }
    if (str_contains($wifi, '6e')) {
        return 70.0;
    }
    if (str_contains($wifi, '6') || str_contains($wifi, '802.11ax')) {
        return 52.0;
    }
    if (str_contains($wifi, '5') || str_contains($wifi, '802.11ac')) {
        return 28.0;
    }
    if (str_contains($wifi, '4') || str_contains($wifi, '802.11n')) {
        return 15.0;
    }
    return 10.0;
}

function bluetooth_score(?string $bluetooth): float
{
    if (preg_match('/(\d+(?:\.\d+)?)/', (string)$bluetooth, $matches)) {
        $version = (float)$matches[1];
        if ($version >= 6.0) {
            return 100.0;
        }
        if ($version >= 5.4) {
            return 72.0;
        }
        if ($version >= 5.3) {
            return 62.0;
        }
        if ($version >= 5.2) {
            return 52.0;
        }
        if ($version >= 5.1) {
            return 44.0;
        }
        if ($version >= 5.0) {
            return 36.0;
        }
        if ($version >= 4.0) {
            return 18.0;
        }
        return 10.0;
    }
    return trim((string)$bluetooth) === '' ? 0.0 : 10.0;
}

function video_score(?string $video): float
{
    $video = text_lower((string)$video);
    if (str_contains($video, '8k')) {
        return 100.0;
    }
    if (str_contains($video, '4k 120') || str_contains($video, '4k120')) {
        return 92.0;
    }
    if (str_contains($video, '4k 60') || str_contains($video, '4k60')) {
        return 84.0;
    }
    if (str_contains($video, '4k')) {
        return 74.0;
    }
    if (str_contains($video, '1080')) {
        return 55.0;
    }
    return 45.0;
}

function waterproof_score(?string $rating): float
{
    $rating = text_upper((string)$rating);
    if (str_contains($rating, 'IP69')) {
        return 100.0;
    }
    if (str_contains($rating, 'IP68')) {
        return 92.0;
    }
    if (str_contains($rating, 'IP67')) {
        return 78.0;
    }
    if (str_contains($rating, 'IP54')) {
        return 45.0;
    }
    return 20.0;
}

function dimension_metric_weights(): array
{
    $rows = fetch_all('SELECT dimension, metric_key, weight FROM dimension_weights');
    $weights = [];

    foreach ($rows as $row) {
        $weights[$row['dimension']][$row['metric_key']] = (float)$row['weight'];
    }

    return $weights;
}

function weighted_average(array $metrics, array $weights): float
{
    $score = 0.0;
    $weightTotal = 0.0;

    foreach ($metrics as $key => $value) {
        $weight = $weights[$key] ?? 1.0;
        $score += $value * $weight;
        $weightTotal += $weight;
    }

    return $weightTotal > 0 ? $score / $weightTotal : 0.0;
}

function calculate_dimension_scores(array $phone, ?array $allWeights = null): array
{
    $weights = $allWeights ?? dimension_metric_weights();

    $scores = [];
    $scores['display'] = weighted_average([
        'panel_type' => screen_size_score($phone['panel_type'] ?? ''),
        'resolution' => normalize_score((float)($phone['ppi'] ?? 0), 520),
        'ppi' => normalize_score((float)($phone['ppi'] ?? 0), 520),
        'refresh_rate' => normalize_score((float)($phone['refresh_rate'] ?? 0), 144),
        'touch_sampling_rate' => normalize_score((float)($phone['touch_sampling_rate'] ?? 0), 480),
        'brightness' => normalize_score((float)($phone['brightness'] ?? 0), 3000),
    ], $weights['display'] ?? []);

    $scores['performance'] = weighted_average([
        'cpu' => normalize_score((float)($phone['antutu_score'] ?? 0), 2300000),
        'antutu_score' => normalize_score((float)($phone['antutu_score'] ?? 0), 2300000),
    ], $weights['performance'] ?? []);

    $scores['storage'] = weighted_average([
        'ram_gb' => normalize_score((float)($phone['ram_gb'] ?? 0), 16),
        'rom_gb' => normalize_score((float)($phone['rom_gb'] ?? 0), 1024),
    ], $weights['storage'] ?? []);

    $scores['battery'] = weighted_average([
        'battery_mah' => normalize_score((float)($phone['battery_mah'] ?? 0), 6000),
        'wired_charging_w' => normalize_score((float)($phone['wired_charging_w'] ?? 0), 120),
        'wireless_charging_w' => normalize_score((float)($phone['wireless_charging_w'] ?? 0), 80),
    ], $weights['battery'] ?? []);

    $scores['communication'] = weighted_average([
        'fiveg_bands' => has_5g_support($phone['fiveg_bands'] ?? '') ? 100.0 : 0.0,
        'wifi' => wifi_score($phone['wifi'] ?? ''),
        'bluetooth' => bluetooth_score($phone['bluetooth'] ?? ''),
        'esim' => bool_score($phone['esim'] ?? 0),
    ], $weights['communication'] ?? []);

    $scores['features'] = weighted_average([
        'fingerprint' => bool_score($phone['fingerprint'] ?? 0),
        'face_unlock' => bool_score($phone['face_unlock'] ?? 0),
        'waterproof_rating' => waterproof_score($phone['waterproof_rating'] ?? ''),
        'cooling' => bool_score($phone['cooling'] ?? 0),
    ], $weights['features'] ?? []);

    $scores['camera'] = weighted_average([
        'main_camera_mp' => normalize_score((float)($phone['main_camera_mp'] ?? 0), 200),
        'ultrawide_camera_mp' => normalize_score((float)($phone['ultrawide_camera_mp'] ?? 0), 50),
        'telephoto_camera_mp' => normalize_score((float)($phone['telephoto_camera_mp'] ?? 0), 50),
        'macro_camera_mp' => normalize_score((float)($phone['macro_camera_mp'] ?? 0), 12),
        'front_camera_mp' => normalize_score((float)($phone['front_camera_mp'] ?? 0), 50),
        'video_spec' => video_score($phone['video_spec'] ?? ''),
    ], $weights['camera'] ?? []);

    return $scores;
}

function need_weight(int $level): float
{
    return need_profile($level)['weight'];
}

function need_target(int $level): float
{
    $profile = need_profile($level);
    return (float)($profile['target'] ?? 50.0);
}

function need_profile(int $level): array
{
    $level = max(0, min(4, $level));

    return match ($level) {
        4 => [
            'min' => 85.0,
            'max' => 100.0,
            'target' => 94.0,
            'weight' => 1.65,
            'inner_penalty' => 4.2,
            'under_penalty' => 4.0,
            'over_penalty' => 0.0,
        ],
        3 => [
            'min' => 70.0,
            'max' => 85.0,
            'target' => 78.0,
            'weight' => 1.35,
            'inner_penalty' => 4.4,
            'under_penalty' => 3.2,
            'over_penalty' => 1.8,
        ],
        2 => [
            'min' => 45.0,
            'max' => 70.0,
            'target' => 58.0,
            'weight' => 1.0,
            'inner_penalty' => 4.0,
            'under_penalty' => 3.8,
            'over_penalty' => 3.8,
        ],
        1 => [
            'min' => 0.0,
            'max' => 45.0,
            'target' => 30.0,
            'weight' => 0.85,
            'inner_penalty' => 3.4,
            'under_penalty' => 0.0,
            'over_penalty' => 4.2,
        ],
        default => [
            'min' => 0.0,
            'max' => 100.0,
            'target' => 50.0,
            'weight' => 0.0,
            'inner_penalty' => 0.0,
            'under_penalty' => 0.0,
            'over_penalty' => 0.0,
        ],
    };
}

function fit_tolerance(float $settingTolerance, int $level): float
{
    if ($level <= 0) {
        return 100.0;
    }

    return max(0.0, min(24.0, 4.0 + ($settingTolerance * 0.18)));
}

function dimension_fit_score(float $score, int $level, float $settingTolerance): float
{
    $profile = need_profile($level);
    if (($profile['weight'] ?? 0.0) <= 0.0) {
        return 100.0;
    }

    $score = max(0.0, min(100.0, $score));
    $tolerance = fit_tolerance($settingTolerance, $level);

    if ($score < $profile['min']) {
        $gap = (float)$profile['min'] - $score;
        $penaltyMultiplier = (float)$profile['under_penalty'];
    } elseif ($score > $profile['max']) {
        $gap = $score - (float)$profile['max'];
        $penaltyMultiplier = (float)$profile['over_penalty'];
    } else {
        $gap = 0.0;
        $penaltyMultiplier = 0.0;
    }

    $centerPenalty = fit_center_penalty($score, $profile);
    $outerPenalty = curved_fit_penalty($gap, $penaltyMultiplier, $tolerance);

    return max(0.0, min(100.0, 100.0 - $centerPenalty - $outerPenalty));
}

function fit_center_penalty(float $score, array $profile): float
{
    $target = (float)$profile['target'];
    $range = $score <= $target
        ? max(1.0, $target - (float)$profile['min'])
        : max(1.0, (float)$profile['max'] - $target);
    $ratio = abs($score - $target) / $range;
    $maxPenalty = (float)$profile['inner_penalty'];

    return min(24.0, $maxPenalty * ($ratio ** 1.45));
}

function curved_fit_penalty(float $gap, float $penaltyMultiplier, float $tolerance): float
{
    if ($gap <= 0.0 || $penaltyMultiplier <= 0.0) {
        return 0.0;
    }

    $softGap = min($gap, max(0.0, $tolerance));
    $hardGap = max(0.0, $gap - max(0.0, $tolerance));
    $softPenalty = $penaltyMultiplier * (($softGap * 0.08) + ($softGap * $softGap * 0.002));
    $hardPenalty = $penaltyMultiplier * (($hardGap * 0.38) + ($hardGap * $hardGap * 0.018));
    $penalty = $softPenalty + $hardPenalty;

    return min(82.0, $penalty);
}

function average_need_level(array $needs): float
{
    $total = 0.0;
    $count = 0;

    foreach (DIMENSIONS as $key => $label) {
        $level = max(0, min(4, (int)($needs[$key] ?? 2)));
        if ($level <= 0) {
            continue;
        }

        $total += $level;
        $count++;
    }

    return $count > 0 ? $total / $count : 2.0;
}

function price_fit_penalty(array $phone, array $needs): float
{
    $price = (float)($phone['price'] ?? 0);
    if ($price <= 0) {
        return 0.0;
    }

    $averageNeed = average_need_level($needs);
    if ($averageNeed >= 3.5) {
        return 0.0;
    }

    if ($averageNeed <= 1.25) {
        $comfortablePrice = 18000.0;
        $penaltyRate = 0.0012;
        $maxPenalty = 42.0;
    } elseif ($averageNeed <= 2.25) {
        $comfortablePrice = 30000.0;
        $penaltyRate = 0.0011;
        $maxPenalty = 40.0;
    } else {
        $comfortablePrice = 50000.0;
        $penaltyRate = 0.00035;
        $maxPenalty = 18.0;
    }

    $excess = max(0.0, $price - $comfortablePrice);
    return round(min($maxPenalty, $excess * $penaltyRate), 1);
}

function recommend_phones(array $needs, int $limit = 12): array
{
    $phones = fetch_all('SELECT * FROM phones ORDER BY brand, model');
    $weights = dimension_metric_weights();
    $tolerance = (float)get_setting('match_tolerance', '28');
    $result = [];

    foreach ($phones as $phone) {
        $dimensionScores = calculate_dimension_scores($phone, $weights);
        $fitTotal = 0.0;
        $weightTotal = 0.0;

        foreach (DIMENSIONS as $key => $label) {
            $level = (int)($needs[$key] ?? 2);
            $weight = need_weight($level);
            if ($weight <= 0) {
                continue;
            }

            $score = (float)($dimensionScores[$key] ?? 0);
            $fitScore = dimension_fit_score($score, $level, $tolerance);
            $fitTotal += $fitScore * $weight;
            $weightTotal += $weight;
        }

        $baseMatchScore = $weightTotal > 0 ? $fitTotal / $weightTotal : 0.0;
        $rawMatchScore = max(0.0, min(100.0, $baseMatchScore - price_fit_penalty($phone, $needs)));
        $phone['dimension_scores'] = $dimensionScores;
        $phone['match_score'] = round($rawMatchScore, 2);
        $phone['_sort_score'] = $rawMatchScore;
        $result[] = $phone;
    }

    usort($result, function (array $a, array $b): int {
        $scoreCompare = ($b['_sort_score'] ?? $b['match_score']) <=> ($a['_sort_score'] ?? $a['match_score']);
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }

        $priceCompare = (int)($a['price'] ?? 0) <=> (int)($b['price'] ?? 0);
        if ($priceCompare !== 0) {
            return $priceCompare;
        }

        return strcmp(($a['brand'] ?? '') . ' ' . ($a['model'] ?? ''), ($b['brand'] ?? '') . ' ' . ($b['model'] ?? ''));
    });

    $result = array_slice($result, 0, $limit);
    foreach ($result as &$phone) {
        unset($phone['_sort_score']);
    }
    unset($phone);

    return $result;
}

function favorite_phone_ids(int $userId): array
{
    $rows = fetch_all('SELECT phone_id FROM favorites WHERE user_id = ?', [$userId]);
    return array_map('intval', array_column($rows, 'phone_id'));
}

function phone_by_id(int $id): ?array
{
    return fetch_one('SELECT * FROM phones WHERE id = ?', [$id]);
}

function phones_by_ids(array $ids): array
{
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    return fetch_all("SELECT * FROM phones WHERE id IN ($placeholders)", $ids);
}

function save_recommendation_log(int $userId, array $needs, array $phones): void
{
    execute_sql(
        'INSERT INTO recommendation_logs (user_id, needs_json, result_json, created_at) VALUES (?, ?, ?, NOW())',
        [$userId, json_encode($needs, JSON_UNESCAPED_UNICODE), json_encode(array_column($phones, 'id'), JSON_UNESCAPED_UNICODE)]
    );
}

function phone_payload_for_chart(array $phone): array
{
    return [
        'id' => (int)$phone['id'],
        'name' => $phone['brand'] . ' ' . $phone['model'],
        'scores' => $phone['dimension_scores'] ?? calculate_dimension_scores($phone),
    ];
}
