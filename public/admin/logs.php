<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = '操作ログ';

$stmt = $pdo->query("
    SELECT
        operation_logs.*,
        users.username
    FROM operation_logs
    LEFT JOIN users ON users.id = operation_logs.user_id
    ORDER BY operation_logs.created_at DESC, operation_logs.id DESC
    LIMIT 300
");

$logs = $stmt->fetchAll();

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>操作ログ</h2>

<p>
    <a href="<?= url('/admin/index.php') ?>">← 管理者メニューへ戻る</a>
</p>

<?php if (empty($logs)): ?>
    <p>操作ログはまだありません。</p>
<?php else: ?>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>日時</th>
                <th>ユーザー</th>
                <th>操作</th>
                <th>対象</th>
                <th>内容</th>
                <th>IP</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= h($log['created_at']) ?></td>
                    <td><?= h($log['username'] ?: '不明') ?></td>
                    <td><?= h($log['action']) ?></td>
                    <td>
                        <?= h($log['target_type']) ?>
                        <?php if (!empty($log['target_id'])): ?>
                            #<?= (int)$log['target_id'] ?>
                        <?php endif; ?>
                    </td>
                    <td><?= h($log['message']) ?></td>
                    <td><?= h($log['ip_address'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>