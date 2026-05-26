<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = 'タスク詳細';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . url('/tasks/index.php'));
    exit;
}

$status_labels = [
    'todo' => '未着手',
    'doing' => '進行中',
    'done' => '完了',
];

$stmt = $pdo->prepare("
    SELECT
        tasks.*,
        customers.name AS customer_name,
        customers.company_name AS customer_company,
        deals.title AS deal_title
    FROM tasks
    LEFT JOIN customers ON customers.id = tasks.customer_id
    LEFT JOIN deals ON deals.id = tasks.deal_id
    WHERE tasks.id = ?
");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    $page_title = 'タスクが見つかりません';
    require_once BASE_PATH . '/app/views/header.php';
    ?>

    <h2>タスクが見つかりません</h2>
    <p>指定されたタスクは存在しないか、削除されています。</p>

    <p>
        <a href="<?= url('/tasks/index.php') ?>">← タスク一覧へ戻る</a>
    </p>

    <?php
    require_once BASE_PATH . '/app/views/footer.php';
    exit;
}

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>タスク詳細</h2>

<p>
    <a href="<?= url('/tasks/index.php') ?>">← タスク一覧へ戻る</a>
</p>

<div class="table-wrap">
    <table class="table is-small">
        <tr>
            <th>ID</th>
            <td><?= (int)$task['id'] ?></td>
        </tr>

        <tr>
            <th>タスク名</th>
            <td><?= h($task['title']) ?></td>
        </tr>

        <tr>
            <th>顧客</th>
            <td>
                <?php if (!empty($task['customer_name']) && !empty($task['customer_id'])): ?>
                    <a href="<?= url('/customers/show.php?id=' . (int)$task['customer_id']) ?>">
                        <?= h($task['customer_name']) ?>
                    </a>

                    <?php if (!empty($task['customer_company'])): ?>
                        <br>
                        <small>
                            <?= h($task['customer_company']) ?>
                        </small>
                    <?php endif; ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th>案件</th>
            <td>
                <?php if (!empty($task['deal_title']) && !empty($task['deal_id'])): ?>
                    <a href="<?= url('/deals/show.php?id=' . (int)$task['deal_id']) ?>">
                        <?= h($task['deal_title']) ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th>期限</th>
            <td><?= h($task['due_date'] ?: '-') ?></td>
        </tr>

        <tr>
            <th>ステータス</th>
            <td>
                <?= h($status_labels[$task['status'] ?? ''] ?? ($task['status'] ?? '-')) ?>
            </td>
        </tr>

        <tr>
            <th>説明</th>
            <td>
                <?= nl2br(h($task['description'] ?: '-')) ?>
            </td>
        </tr>

        <tr>
            <th>登録日</th>
            <td><?= h($task['created_at']) ?></td>
        </tr>

        <tr>
            <th>更新日</th>
            <td><?= h($task['updated_at']) ?></td>
        </tr>
    </table>
</div>

<div class="action-buttons">
    <a href="<?= url('/tasks/edit.php?id=' . (int)$task['id']) ?>">編集する</a>
    |

    <form class="delete-form" method="post" action="<?= url('/tasks/delete.php') ?>">
        <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
        <button type="submit" onclick="return confirm('このタスクを削除します。本当に削除しますか？');">
            削除
        </button>
    </form>
</div>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>