<?php
declare(strict_types=1);

define('APP_NAME', '智慧手機型號推薦網站');
define('APP_BASE_PATH', dirname(__DIR__));
define('APP_PUBLIC_BASE', '/smartphone-recommender/');
define('DATA_DIR', APP_BASE_PATH . '/data');
define('DB_HOST', 'sql200.infinityfree.com');
define('DB_NAME', 'if0_42264599_smartphone_recommender_mysql');
define('DB_USER', 'if0_42264599');
define('DB_PASS', 'ryan20051103');
define('DB_CHARSET', 'utf8mb4');
define('CRAWLER_CACHE_DIR', APP_BASE_PATH . '/storage/crawler_cache');

date_default_timezone_set('Asia/Taipei');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

