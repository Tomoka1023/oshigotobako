<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = 'タスク一覧';

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$status_labels = [
    'todo' => '未着手',
    'doing' => '進行中',
    'done' => '完了',
];

$sql = "
    SELECT
        tasks.*,
        customers.name AS customer_name,
        deals.title AS deal_title
    FROM tasks
    LEFT JOIN customers ON customers.id = tasks.customer_id
    LEFT JOIN deals ON deals.id = tasks.deal_id
";

$params = [];
$where = [];

if ($q !== '') {
    $where[] = "
        (
            tasks.title LIKE ?
            OR tasks.description LIKE ?
            OR customers.name LIKE ?
            OR deals.title LIKE ?
        )
    ";

    $keyword = '%' . $q . '%';
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}

if ($status !== '' && array_key_exists($status, $status_labels)) {
    $where[] = "tasks.status = ?";
    $params[] = $status;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
    ORDER BY
        CASE
            WHEN tasks.status = 'done' THEN 1
            ELSE 0
        END ASC,
        tasks.due_date IS NULL ASC,
        tasks.due_date ASC,
        tasks.updated_at DESC
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>タスク一覧</h2>

<div class="toolbar">
    <form method="get" action="<?= url('/tasks/index.php') ?>" class="search-form">
        <input
            type="text"
            name="q"
            value="<?= h($q) ?>"
            placeholder="タスク名・顧客名・案件名で検索"
        >

        <select name="status">
            <option value="">すべてのステータス</option>
            <?php foreach ($status_labels as $key => $label): ?>
                <option value="<?= h($key) ?>"
                    <?= $status === $key ? 'selected' : '' ?>>
                    <?= h($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn">検索</button>
    </form>
    <a href="<?= url('/tasks/create.php') ?>" class="btn btn-secondary">
        ＋ タスク登録
    </a>

    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="<?= url('/tasks/export_csv.php') ?>" class="btn btn-secondary">
            CSV出力
        </a>
    <?php endif; ?>
</div>

<?php if (empty($tasks)): ?>
    <p>タスクがまだ登録されていません。</p>
<?php else: ?>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>タスク名</th>
                <th>顧客</th>
                <th>案件</th>
                <th>期限</th>
                <th>ステータス</th>
                <th>更新日</th>
                <th>操作</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($tasks as $task): ?>
                <tr>
                    <td>
                        <?= (int)$task['id'] ?>
                    </td>

                    <td>
                        <?= h($task['title']) ?>
                    </td>

                    <td>
                        <?php if (!empty($task['customer_name']) && !empty($task['customer_id'])): ?>
                            <a href="<?= url('/customers/show.php?id=' . (int)$task['customer_id']) ?>">
                                <?= h($task['customer_name']) ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if (!empty($task['deal_title']) && !empty($task['deal_id'])): ?>
                            <a href="<?= url('/deals/show.php?id=' . (int)$task['deal_id']) ?>">
                                <?= h($task['deal_title']) ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if (!empty($task['due_date'])): ?>
                            <?= h($task['due_date']) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= h($status_labels[$task['status'] ?? ''] ?? ($task['status'] ?? '-')) ?>
                    </td>

                    <td>
                        <?= h($task['updated_at']) ?>
                    </td>

                    <td>
                        <div class="table-actions">
                            <a href="<?= url('/tasks/show.php?id=' . (int)$task['id']) ?>">詳細</a>
                            /
                            <a href="<?= url('/tasks/edit.php?id=' . (int)$task['id']) ?>">編集</a>
                            /
                            <form class="delete-form" method="post" action="<?= url('/tasks/delete.php') ?>">
                                <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                                <button type="submit" onclick="return confirm('このタスクを削除します。本当に削除しますか？');">
                                    削除
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>