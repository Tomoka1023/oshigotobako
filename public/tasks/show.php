<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';

$page_title = 'タスク詳細';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . BASE_URL . '/tasks/index.php');
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
        <a href="<?= BASE_URL ?>/tasks/index.php">← タスク一覧へ戻る</a>
    </p>

    <?php
    require_once BASE_PATH . '/app/views/footer.php';
    exit;
}

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>タスク詳細</h2>

<p>
    <a href="<?= BASE_URL ?>/tasks/index.php">← タスク一覧へ戻る</a>
</p>

<div class="table-wrap">
    <table class="table is-small">
        <tr>
            <th>ID</th>
            <td><?= htmlspecialchars($task['id'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>タスク名</th>
            <td><?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>顧客</th>
            <td>
                <?php if (!empty($task['customer_name']) && !empty($task['customer_id'])): ?>
                    <a href="<?= BASE_URL ?>/customers/show.php?id=<?= (int)$task['customer_id'] ?>">
                        <?= htmlspecialchars($task['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                    </a>

                    <?php if (!empty($task['customer_company'])): ?>
                        <br>
                        <small>
                            <?= htmlspecialchars($task['customer_company'], ENT_QUOTES, 'UTF-8') ?>
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
                    <a href="<?= BASE_URL ?>/deals/show.php?id=<?= (int)$task['deal_id'] ?>">
                        <?= htmlspecialchars($task['deal_title'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th>期限</th>
            <td><?= htmlspecialchars($task['due_date'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>ステータス</th>
            <td>
                <?= htmlspecialchars($status_labels[$task['status']] ?? $task['status'], ENT_QUOTES, 'UTF-8') ?>
            </td>
        </tr>

        <tr>
            <th>説明</th>
            <td>
                <?= nl2br(htmlspecialchars($task['description'] ?: '-', ENT_QUOTES, 'UTF-8')) ?>
            </td>
        </tr>

        <tr>
            <th>登録日</th>
            <td><?= htmlspecialchars($task['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>更新日</th>
            <td><?= htmlspecialchars($task['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    </table>
</div>

<div class="action-buttons">
    <a href="<?= BASE_URL ?>/tasks/edit.php?id=<?= (int)$task['id'] ?>">編集する</a>
    |

    <form class="delete-form" method="post" action="<?= BASE_URL ?>/tasks/delete.php" style="display:inline;">
        <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
        <button type="submit" onclick="return confirm('このタスクを削除します。本当に削除しますか？');">
            削除
        </button>
    </form>
</div>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>