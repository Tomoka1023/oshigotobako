<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';

$page_title = '案件詳細';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . BASE_URL . '/deals/index.php');
    exit;
}

$status_labels = [
    'new' => '新規',
    'proposal' => '提案中',
    'estimate' => '見積済',
    'ordered' => '受注',
    'completed' => '完了',
    'lost' => '失注',
];

$stmt = $pdo->prepare("
    SELECT
        deals.*,
        customers.name AS customer_name,
        customers.company_name AS customer_company,
        customers.email AS customer_email,
        customers.phone AS customer_phone
    FROM deals
    LEFT JOIN customers ON customers.id = deals.customer_id
    WHERE deals.id = ?
");
$stmt->execute([$id]);
$deal = $stmt->fetch();

if (!$deal) {
    $page_title = '案件が見つかりません';
    require_once BASE_PATH . '/app/views/header.php';
    ?>

    <h2>案件が見つかりません</h2>
    <p>指定された案件情報は存在しないか、削除されています。</p>

    <p>
        <a href="<?= BASE_URL ?>/deals/index.php">← 案件一覧へ戻る</a>
    </p>

    <?php
    require_once BASE_PATH . '/app/views/footer.php';
    exit;
}

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>案件詳細</h2>

<p>
    <a href="<?= BASE_URL ?>/deals/index.php">← 案件一覧へ戻る</a>
</p>

<div class="table-wrap">
    <table class="table is-small">
        <tr>
            <th>ID</th>
            <td><?= htmlspecialchars($deal['id'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>案件名</th>
            <td><?= htmlspecialchars($deal['title'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>顧客</th>
            <td>
                <?php if (!empty($deal['customer_name'])): ?>
                    <a href="<?= BASE_URL ?>/customers/show.php?id=<?= (int)$deal['customer_id'] ?>">
                        <?= htmlspecialchars($deal['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                    </a>

                    <?php if (!empty($deal['customer_company'])): ?>
                        <br>
                        <small>
                            <?= htmlspecialchars($deal['customer_company'], ENT_QUOTES, 'UTF-8') ?>
                        </small>
                    <?php endif; ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th>顧客メール</th>
            <td><?= htmlspecialchars($deal['customer_email'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>顧客電話番号</th>
            <td><?= htmlspecialchars($deal['customer_phone'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>ステータス</th>
            <td>
                <?= htmlspecialchars($status_labels[$deal['status']] ?? $deal['status'], ENT_QUOTES, 'UTF-8') ?>
            </td>
        </tr>

        <tr>
            <th>金額</th>
            <td>
                <?php if ((int)$deal['amount'] > 0): ?>
                    ¥<?= number_format((int)$deal['amount']) ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th>期限</th>
            <td><?= htmlspecialchars($deal['due_date'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>説明</th>
            <td>
                <?= nl2br(htmlspecialchars($deal['description'] ?: '-', ENT_QUOTES, 'UTF-8')) ?>
            </td>
        </tr>

        <tr>
            <th>登録日</th>
            <td><?= htmlspecialchars($deal['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>

        <tr>
            <th>更新日</th>
            <td><?= htmlspecialchars($deal['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    </table>
</div>

<div class="action-buttons">
    <a href="<?= BASE_URL ?>/deals/edit.php?id=<?= (int)$deal['id'] ?>">編集する</a>
    |
    <form class="delete-form" method="post" action="<?= BASE_URL ?>/deals/delete.php" style="display:inline;">
        <input type="hidden" name="id" value="<?= (int)$deal['id'] ?>">
        <button type="submit" onclick="return confirm('この案件を削除します。関連するタスクも削除される場合があります。本当に削除しますか？');">
            削除
        </button>
    </form>
</div>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>