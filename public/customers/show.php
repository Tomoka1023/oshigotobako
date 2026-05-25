<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';

$page_title = '顧客詳細';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . BASE_URL . '/customers/index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM customers
    WHERE id = ?
");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    $page_title = '顧客が見つかりません';
    require_once BASE_PATH . '/app/views/header.php';
    ?>

    <h2>顧客が見つかりません</h2>
    <p>指定された顧客情報は存在しないか、削除されています。</p>

    <p>
        <a href="<?= BASE_URL ?>/customers/index.php">← 顧客一覧へ戻る</a>
    </p>

    <?php
    require_once BASE_PATH . '/app/views/footer.php';
    exit;
}

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>顧客詳細</h2>

<p>
    <a href="<?= BASE_URL ?>/customers/index.php">← 顧客一覧へ戻る</a>
</p>

<div class="table-wrap">
    <table class="table is-small">
        <tr>
            <th>ID</th>
            <td><?= htmlspecialchars($customer['id'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>顧客名</th>
            <td><?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>会社名</th>
            <td><?= htmlspecialchars($customer['company_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>メールアドレス</th>
            <td><?= htmlspecialchars($customer['email'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>電話番号</th>
            <td><?= htmlspecialchars($customer['phone'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>住所</th>
            <td>
                <?= nl2br(htmlspecialchars($customer['address'] ?: '-', ENT_QUOTES, 'UTF-8')) ?>
            </td>
        </tr>

        <tr>
            <th>メモ</th>
            <td>
                <?= nl2br(htmlspecialchars($customer['memo'] ?: '-', ENT_QUOTES, 'UTF-8')) ?>
            </td>
        </tr>

        <tr>
            <th>登録日</th>
            <td><?= htmlspecialchars($customer['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>更新日</th>
            <td><?= htmlspecialchars($customer['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    </table>
</div>

<div class="action-buttons">
    <a href="<?= BASE_URL ?>/customers/edit.php?id=<?= (int)$customer['id'] ?>">編集する</a>
    |
    <form class="delete-form" method="post" action="<?= BASE_URL ?>/customers/delete.php" style="display:inline;">
        <input type="hidden" name="id" value="<?= (int)$customer['id'] ?>">
        <button type="submit" onclick="return confirm('この顧客を削除します。関連する案件やタスクも削除される場合があります。本当に削除しますか？');">
            削除
        </button>
    </form>
</div>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>