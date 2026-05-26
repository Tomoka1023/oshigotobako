<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = '案件詳細';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . url('/deals/index.php'));
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

// 案件情報を取得
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
        <a href="<?= url('/deals/index.php') ?>">← 案件一覧へ戻る</a>
    </p>

    <?php
    require_once BASE_PATH . '/app/views/footer.php';
    exit;
}

// 添付ファイル一覧を取得
$stmt = $pdo->prepare("
    SELECT *
    FROM attachments
    WHERE target_type = 'deal'
      AND target_id = ?
    ORDER BY created_at DESC, id DESC
");
$stmt->execute([$id]);
$attachments = $stmt->fetchAll();

function format_file_size($bytes)
{
    $bytes = (int)$bytes;

    if ($bytes >= 1024 * 1024) {
        return round($bytes / 1024 / 1024, 1) . ' MB';
    }

    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }

    return $bytes . ' B';
}

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>案件詳細</h2>

<p>
    <a href="<?= url('/deals/index.php') ?>">← 案件一覧へ戻る</a>
</p>

<div class="table-wrap">
    <table class="table is-small">
        <tr>
            <th>ID</th>
            <td><?= (int)$deal['id'] ?></td>
        </tr>

        <tr>
            <th>案件名</th>
            <td><?= h($deal['title']) ?></td>
        </tr>

        <tr>
            <th>顧客</th>
            <td>
                <?php if (!empty($deal['customer_name'])): ?>
                    <a href="<?= url('/customers/show.php?id=' . (int)$deal['customer_id']) ?>">
                        <?= h($deal['customer_name']) ?>
                    </a>

                    <?php if (!empty($deal['customer_company'])): ?>
                        <br>
                        <small><?= h($deal['customer_company']) ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th>顧客メール</th>
            <td><?= h($deal['customer_email'] ?: '-') ?></td>
        </tr>

        <tr>
            <th>顧客電話番号</th>
            <td><?= h($deal['customer_phone'] ?: '-') ?></td>
        </tr>

        <tr>
            <th>ステータス</th>
            <td>
                <?= h($status_labels[$deal['status'] ?? ''] ?? ($deal['status'] ?? '-')) ?>
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
            <td><?= h($deal['due_date'] ?: '-') ?></td>
        </tr>

        <tr>
            <th>説明</th>
            <td><?= nl2br(h($deal['description'] ?: '-')) ?></td>
        </tr>

        <tr>
            <th>登録日</th>
            <td><?= h($deal['created_at']) ?></td>
        </tr>

        <tr>
            <th>更新日</th>
            <td><?= h($deal['updated_at']) ?></td>
        </tr>
    </table>
</div>

<div class="action-buttons">
    <a href="<?= url('/deals/edit.php?id=' . (int)$deal['id']) ?>">
        編集する
    </a>
    |
    <form class="delete-form" method="post" action="<?= url('/deals/delete.php') ?>">
        <input type="hidden" name="id" value="<?= (int)$deal['id'] ?>">
        <button type="submit" class="danger" onclick="return confirm('この案件を削除します。関連するタスクも削除される場合があります。本当に削除しますか？');">
            削除
        </button>
    </form>
</div>

<h3>添付ファイル</h3>

<form method="post" action="<?= url('/attachments/upload.php') ?>" enctype="multipart/form-data">
    <input type="hidden" name="target_type" value="deal">
    <input type="hidden" name="target_id" value="<?= (int)$deal['id'] ?>">

    <div>
        <label>
            ファイルを選択<br>
            <input type="file" name="attachment" required>
        </label>
    </div>

    <br>

    <button type="submit">アップロード</button>
</form>

<?php if (empty($attachments)): ?>
    <p>添付ファイルはまだありません。</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="table is-small">
            <thead>
                <tr>
                    <th>ファイル名</th>
                    <th>サイズ</th>
                    <th>アップロード日時</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attachments as $attachment): ?>
                    <tr>
                        <td><?= h($attachment['original_name']) ?></td>
                        <td><?= h(format_file_size($attachment['file_size'])) ?></td>
                        <td><?= h($attachment['created_at']) ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="<?= url('/attachments/download.php?id=' . (int)$attachment['id']) ?>">
                                    ダウンロード
                                </a>
                                /
                                <form class="delete-form" method="post" action="<?= url('/attachments/delete.php') ?>">
                                    <input type="hidden" name="id" value="<?= (int)$attachment['id'] ?>">
                                    <button
                                        type="submit"
                                        class="danger"
                                        onclick="return confirm('この添付ファイルを削除します。本当に削除しますか？');"
                                    >
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