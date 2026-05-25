<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = 'ユーザー管理';

// ユーザー一覧を取得
$stmt = $pdo->query("
    SELECT id, username, email, role, created_at
    FROM users
    ORDER BY id ASC
");

$users = $stmt->fetchAll();

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>ユーザー管理</h2>

<p>
    <a class="btn" href="<?= url('/admin/create_user.php') ?>">＋ ユーザー追加</a>
</p>

<?php if (empty($users)): ?>
    <p>ユーザーがまだ登録されていません。</p>
<?php else: ?>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>ユーザー名</th>
                <th>メールアドレス</th>
                <th>権限</th>
                <th>登録日</th>
                <th>操作</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <?= (int)$user['id'] ?>
                    </td>

                    <td>
                        <?= h($user['username']) ?>
                    </td>

                    <td>
                        <?= h($user['email']) ?>
                    </td>

                    <td>
                        <?php if ($user['role'] === 'admin'): ?>
                            管理者
                        <?php else: ?>
                            一般ユーザー
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= h($user['created_at']) ?>
                    </td>

                    <td>
                        <div class="table-actions">
                            <a href="<?= url('/admin/edit_user.php?id=' . (int)$user['id']) ?>">編集</a>

                            <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                /
                                <form class="delete-form" method="post" action="<?= url('/admin/delete_user.php') ?>">
                                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                    <button type="submit" class="danger" onclick="return confirm('このユーザーを削除します。関連するデータも削除される場合があります。本当に削除しますか？');">
                                        削除
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>