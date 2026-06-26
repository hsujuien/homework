<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$assetPrefix = $assetPrefix ?? url_for('');
$user = current_user();
$flash = flash();
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> - <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= h(url_for('assets/css/style.css')) ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= h(url_for('index.php')) ?>">
        <span class="brand-mark">7D</span>
        <span><?= h(APP_NAME) ?></span>
    </a>
    <button class="nav-toggle" type="button" data-nav-toggle aria-label="開啟選單">☰</button>
    <nav class="nav" data-nav>
        <a href="<?= h(url_for('questionnaire.php')) ?>">需求問卷</a>
        <a href="<?= h(url_for('favorites.php')) ?>">收藏</a>
        <a href="<?= h(url_for('compare.php')) ?>">規格對比</a>
        <?php if ($user && $user['role'] === 'admin'): ?>
            <a href="<?= h(url_for('admin/index.php')) ?>">管理者控制台</a>
        <?php endif; ?>
        <?php if ($user): ?>
            <span class="nav-user"><?= h($user['name']) ?></span>
            <a class="button ghost sm" href="<?= h(url_for('logout.php')) ?>">登出</a>
        <?php else: ?>
            <a class="button ghost sm" href="<?= h(url_for('login.php')) ?>">登入</a>
            <a class="button sm" href="<?= h(url_for('register.php')) ?>">註冊</a>
        <?php endif; ?>
    </nav>
</header>
<?php if ($flash): ?>
    <div class="flash <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>
<main class="page">

