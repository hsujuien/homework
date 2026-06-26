<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$account = trim((string)($_POST['account'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login_user($account, $_POST['password'] ?? '')) {
        redirect('index.php');
    }
    $error = '帳號或密碼不正確。';
}

$pageTitle = '登入';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-layout">
    <form class="form-card" method="post">
        <p class="eyebrow">登入註冊</p>
        <h1>登入系統</h1>
        <p class="muted">使用帳號密碼登入系統，管理者擁有額外權限。</p>
        <?php if ($error): ?><div class="alert danger"><?= h($error) ?></div><?php endif; ?>
        <label>帳號
            <input type="text" name="account" value="<?= h($account) ?>" autocomplete="username" required>
        </label>
        <label>密碼
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="button block" type="submit">登入</button>
        <p class="muted">還沒有帳號？<a href="<?= h(url_for('register.php')) ?>" style="color: #0f766e; font-weight: 700; text-decoration: none;">註冊新使用者</a></p>
        <p class="muted">示範帳號：user / user123；管理者：admin / admin123</p>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
