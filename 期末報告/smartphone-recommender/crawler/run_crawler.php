<?php
declare(strict_types=1);

require_once __DIR__ . '/PhoneCrawler.php';

$urls = array_slice($argv ?? [], 1);
$sourceFile = __DIR__ . '/sources.json';

if (!$urls && file_exists($sourceFile)) {
    $loaded = json_decode((string)file_get_contents($sourceFile), true);
    if (is_array($loaded)) {
        $urls = $loaded;
    }
}

if (!$urls) {
    echo "用法：php crawler/run_crawler.php https://example.com/phone-page\n";
    echo "也可以把網址陣列寫入 crawler/sources.json。\n";
    exit(0);
}

$crawler = new PhoneCrawler();
foreach ($urls as $url) {
    $result = $crawler->crawlUrl((string)$url);
    echo ($result['ok'] ? '[OK] ' : '[FAIL] ') . $result['message'] . PHP_EOL;
}

