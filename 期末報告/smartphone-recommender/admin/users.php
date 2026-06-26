<?php
declare(strict_types=1);

$assetPrefix = '../';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$current = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0 && $userId !== (int)$current['id']) {
        if ($action === 'role') {
            $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
            execute_sql('UPDATE users SET role = ? WHERE id = ?', [$role, $userId]);
            flash('使用者權限已更新。');
        }
        if ($action === 'delete') {
            execute_sql('DELETE FROM users WHERE id = ?', [$userId]);
            flash('使用者已刪除。');
        }
    }
    redirect('admin/users.php');
}

$users = fetch_all('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');

$pageTitle = '使用者管理';
require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="section-head">
        <div>
            <p class="eyebrow">管理使用者帳號</p>
            <h1>使用者帳號</h1>
        </div>
        <a class="button ghost" href="<?= h(url_for('admin/index.php')) ?>">返回控制台</a>
    </div>
    <div class="table-wrap">
        <table class="spec-table">
            <thead>
                <tr>
                    <th>姓名</th>
                    <th>Email</th>
                    <th>角色</th>
                    <th>建立時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $item): ?>
                    <tr>
                        <td><?= h($item['name']) ?></td>
                        <td><?= h($item['email']) ?></td>
                        <td><?= h($item['role'] === 'admin' ? '管理者' : '一般使用者') ?></td>
                        <td><?= h($item['created_at']) ?></td>
                        <td class="action-cell">
                            <?php if ((int)$item['id'] !== (int)$current['id']): ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="role">
                                    <input type="hidden" name="user_id" value="<?= (int)$item['id'] ?>">
                                    <select name="role">
                                        <option value="user" <?= selected($item['role'], 'user') ?>>一般使用者</option>
                                        <option value="admin" <?= selected($item['role'], 'admin') ?>>管理者</option>
                                    </select>
                                    <button class="button ghost sm" type="submit">更新</button>
                                </form>
                                <form method="post" onsubmit="return confirm('確定刪除此帳號？')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= (int)$item['id'] ?>">
                                    <button class="button danger sm" type="submit">刪除</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">目前帳號</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

