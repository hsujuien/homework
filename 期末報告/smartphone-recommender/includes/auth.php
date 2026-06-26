<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return fetch_one('SELECT id, name, email, role, created_at FROM users WHERE id = ?', [$_SESSION['user_id']]);
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        exit('你沒有管理者權限。');
    }
}

function login_user(string $account, string $password): bool
{
    $account = trim($account);
    $user = fetch_user_by_account($account);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user_id'] = (int)$user['id'];
    return true;
}

function fetch_user_by_account(string $account): ?array
{
    $user = fetch_one('SELECT * FROM users WHERE email = ?', [$account]);
    if ($user || str_contains($account, '@')) {
        return $user;
    }

    return fetch_one('SELECT * FROM users WHERE email = ?', [$account . '@example.com']);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

function register_user(string $account, string $password, string $passwordConfirm = ''): array
{
    $account = trim($account);

    if ($account === '') {
        return ['ok' => false, 'message' => '請輸入帳號。'];
    }

    if (!preg_match('/^[\p{L}\p{N}_.-]{3,50}$/u', $account)) {
        return ['ok' => false, 'message' => '帳號需為 3 到 50 個字，可使用中英文、數字、底線、短橫線或點。'];
    }

    if (strlen($password) < 6) {
        return ['ok' => false, 'message' => '密碼至少 6 碼。'];
    }

    if ($passwordConfirm !== '' && $password !== $passwordConfirm) {
        return ['ok' => false, 'message' => '兩次輸入的密碼不一致。'];
    }

    $exists = fetch_one('SELECT id FROM users WHERE email = ?', [$account]);
    if (!$exists && !str_contains($account, '@')) {
        $exists = fetch_one('SELECT id FROM users WHERE email = ?', [$account . '@example.com']);
    }
    if ($exists) {
        return ['ok' => false, 'message' => '這個帳號已經被使用。'];
    }

    execute_sql(
        'INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())',
        [$account, $account, password_hash($password, PASSWORD_DEFAULT), 'user']
    );

    $user = fetch_one('SELECT id FROM users WHERE email = ?', [$account]);
    if ($user) {
        $_SESSION['user_id'] = (int)$user['id'];
    }

    return ['ok' => true, 'message' => '註冊成功，已為你登入。'];
}

function redirect(string $path): void
{
    header('Location: ' . url_for($path));
    exit;
}
