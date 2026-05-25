<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = '顧客一覧';

$q = trim($_GET['q'] ?? '');

$sql = "
    SELECT *
    FROM customers
";

$params = [];

if ($q !== '') {
    $sql .= "
        WHERE name LIKE ?
           OR company_name LIKE ?
           OR email LIKE ?
           OR phone LIKE ?
    ";

    $keyword = '%' . $q . '%';
    $params = [$keyword, $keyword, $keyword, $keyword];
}

$sql .= "
    ORDER BY updated_at DESC, id DESC
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>顧客一覧</h2>

<div class="toolbar">
    <form method="get" action="<?= url('/customers/index.php') ?>" class="search-form">
        <input
            type="text"
            name="q"
            value="<?= h($q) ?>"
            placeholder="名前・会社名・メール・電話で検索"
        >
        <button type="submit" class="btn">検索</button>
    </form>
    <a href="<?= url('/customers/create.php') ?>" class="btn btn-secondary">
        ＋ 顧客登録
    </a>

    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="<?= url('/customers/export_csv.php') ?>" class="btn btn-secondary">
            CSV出力
        </a>
    <?php endif; ?>
</div>

<?php if (empty($customers)): ?>
    <p>顧客がまだ登録されていません。</p>
<?php else: ?>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>顧客名</th>
                <th>会社名</th>
                <th>メール</th>
                <th>電話番号</th>
                <th>登録日</th>
                <th>操作</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td>
                        <?= (int)$customer['id'] ?>
                    </td>

                    <td>
                        <?= h($customer['name']) ?>
                    </td>

                    <td>
                        <?= h($customer['company_name'] ?: '-') ?>
                    </td>

                    <td>
                        <?= h($customer['email'] ?: '-') ?>
                    </td>

                    <td>
                        <?= h($customer['phone'] ?: '-') ?>
                    </td>

                    <td>
                        <?= h($customer['created_at']) ?>
                    </td>

                    <td>
                        <div class="table-actions">
                            <a href="<?= url('/customers/show.php?id=' . (int)$customer['id']) ?>">詳細</a>
                            /
                            <a href="<?= url('/customers/edit.php?id=' . (int)$customer['id']) ?>">編集</a>
                            /
                            <form class="delete-form" method="post" action="<?= url('/customers/delete.php') ?>">
                                <input type="hidden" name="id" value="<?= (int)$customer['id'] ?>">
                                <button type="submit" onclick="return confirm('この顧客を削除します。関連する案件やタスクも削除される場合があります。本当に削除しますか？');">
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