<?php
declare(strict_types=1);

$appJsPath = __DIR__ . '/../assets/js/app.js';
$appJsVersion = file_exists($appJsPath) ? (string)filemtime($appJsPath) : (string)time();
?>
</main>
<footer class="footer">
    <span>七維度評分系統：螢幕、性能、儲存、相機、續航、通訊、功能</span>
</footer>
<script src="<?= h(url_for('assets/js/app.js')) ?>?v=<?= h($appJsVersion) ?>"></script>
</body>
</html>
