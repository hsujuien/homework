<?php
declare(strict_types=1);

$assetPrefix = '../';
require_once __DIR__ . '/../crawler/PhoneCrawler.php';
require_admin();

$results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urlsText = trim((string)($_POST['urls'] ?? ''));
    $urls = array_filter(array_map('trim', preg_split('/\R+/', $urlsText) ?: []));
    $crawler = new PhoneCrawler();

    foreach ($urls as $url) {
        $results[] = $crawler->crawlUrl($url);
    }
}

$logs = fetch_all('SELECT * FROM crawler_logs ORDER BY created_at DESC LIMIT 20');

$pageTitle = '爬蟲資料蒐集';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow">啟動爬蟲自動蒐集資料</p>
            <h1>手機規格爬蟲</h1>
        </div>
        <a class="button ghost" href="<?= h(url_for('admin/index.php')) ?>">返回控制台</a>
    </div>
    <form class="form-card wide" method="post">
        <label>規格頁網址
            <textarea name="urls" rows="6" placeholder="每行一個網址，例如：https://example.com/phone/spec"></textarea>
        </label>
        <button class="button lg" type="submit">開始爬取</button>
        <p class="muted">爬蟲會嘗試讀取頁面標題、規格表、常見關鍵字，並將結果寫入手機資料庫。</p>
    </form>

    <?php if ($results): ?>
        <div class="result-list">
            <?php foreach ($results as $result): ?>
                <div class="alert <?= $result['ok'] ? 'success' : 'danger' ?>">
                    <?= h($result['message']) ?>
                    <?php if (!empty($result['phone_id'])): ?>
                        <a href="<?= h(url_for('phone.php?id=' . (int)$result['phone_id'])) ?>">查看</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section">
    <h2>最近爬蟲紀錄</h2>
    <div class="table-wrap">
        <table class="spec-table">
            <thead>
                <tr>
                    <th>時間</th>
                    <th>狀態</th>
                    <th>訊息</th>
                    <th>網址</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= h($log['created_at']) ?></td>
                        <td><?= h($log['status']) ?></td>
                        <td><?= h($log['message']) ?></td>
                        <td><a href="<?= h($log['url']) ?>" target="_blank" rel="noreferrer">來源</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

