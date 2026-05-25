<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = '案件一覧';

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$sql = "
    SELECT
        deals.*,
        customers.name AS customer_name,
        customers.company_name AS customer_company
    FROM deals
    LEFT JOIN customers ON customers.id = deals.customer_id
";

$params = [];
$where = [];

if ($q !== '') {
    $where[] = "
        (
            deals.title LIKE ?
            OR deals.description LIKE ?
            OR customers.name LIKE ?
            OR customers.company_name LIKE ?
        )
    ";

    $keyword = '%' . $q . '%';
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}

$allowed_statuses = ['new', 'proposal', 'estimate', 'ordered', 'completed', 'lost'];

if ($status !== '' && in_array($status, $allowed_statuses, true)) {
    $where[] = "deals.status = ?";
    $params[] = $status;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
    ORDER BY deals.updated_at DESC, deals.id DESC
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$deals = $stmt->fetchAll();

$status_labels = [
    'new' => '新規',
    'proposal' => '提案中',
    'estimate' => '見積済',
    'ordered' => '受注',
    'completed' => '完了',
    'lost' => '失注',
];

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>案件一覧</h2>

<div class="toolbar">
    <form method="get" action="<?= url('/deals/index.php') ?>" class="search-form">
        <input
            type="text"
            name="q"
            value="<?= h($q) ?>"
            placeholder="案件名・顧客名で検索"
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
    <a href="<?= url('/deals/create.php') ?>" class="btn btn-secondary">
        ＋ 案件登録
    </a>

    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="<?= url('/deals/export_csv.php') ?>" class="btn btn-secondary">
            CSV出力
        </a>
    <?php endif; ?>
</div>

<?php if (empty($deals)): ?>
    <p>案件がまだ登録されていません。</p>
<?php else: ?>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>案件名</th>
                <th>顧客名</th>
                <th>ステータス</th>
                <th>金額</th>
                <th>期限</th>
                <th>更新日</th>
                <th>操作</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($deals as $deal): ?>
                <tr>
                    <td>
                        <?= (int)$deal['id'] ?>
                    </td>

                    <td>
                        <?= h($deal['title']) ?>
                    </td>

                    <td>
                        <?php if (!empty($deal['customer_name'])): ?>
                            <?= h($deal['customer_name']) ?>

                            <?php if (!empty($deal['customer_company'])): ?>
                                <br>
                                <small>
                                    <?= h($deal['customer_company']) ?>
                                </small>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= h($status_labels[$deal['status'] ?? ''] ?? ($deal['status'] ?? '-')) ?>
                    </td>

                    <td>
                        <?php if ((int)$deal['amount'] > 0): ?>
                            ¥<?= number_format((int)$deal['amount']) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= h($deal['due_date'] ?: '-') ?>
                    </td>

                    <td>
                        <?= h($deal['updated_at']) ?>
                    </td>

                    <td>
                        <div class="table-actions">
                            <a href="<?= url('/deals/show.php?id=' . (int)$deal['id']) ?>">詳細</a>
                            /
                            <a href="<?= url('/deals/edit.php?id=' . (int)$deal['id']) ?>">編集</a>
                            /
                            <form class="delete-form" method="post" action="<?= url('/deals/delete.php') ?>">
                                <input type="hidden" name="id" value="<?= (int)$deal['id'] ?>">
                                <button type="submit" onclick="return confirm('この案件を削除します。関連するタスクも削除される場合があります。本当に削除しますか？');">
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