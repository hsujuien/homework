<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = register_user(
        $_POST['account'] ?? '',
        $_POST['password'] ?? '',
        $_POST['password_confirm'] ?? ''
    );
    if ($result['ok']) {
        flash($result['message']);
        redirect('index.php');
    }
    $message = $result['message'];
}

$account = trim((string)($_POST['account'] ?? ''));

$pageTitle = '註冊';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-layout">
    <form class="form-card" method="post">
        <p class="eyebrow">建立帳號</p>
        <h1>註冊一般使用者</h1>
        <div class="alert success">建立帳號後即可填寫需求問卷、收藏手機與查看推薦結果。</div>
        <?php if ($message): ?><div class="alert danger"><?= h($message) ?></div><?php endif; ?>
        <label>帳號
            <input type="text" name="account" value="<?= h($account) ?>" autocomplete="username" minlength="3" maxlength="50" required>
        </label>
        <label>密碼
            <input type="password" name="password" minlength="6" autocomplete="new-password" required>
        </label>
        <label>確認密碼
            <input type="password" name="password_confirm" minlength="6" autocomplete="new-password" required>
        </label>
        <button class="button block" type="submit">註冊</button>
        <p class="muted">已經有帳號？<a href="<?= h(url_for('login.php')) ?>">前往登入</a></p>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
