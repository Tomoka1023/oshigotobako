<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = '案件編集';

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

// 編集対象の案件を取得
$stmt = $pdo->prepare("
    SELECT *
    FROM deals
    WHERE id = ?
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

// 顧客一覧を取得
$stmt = $pdo->query("
    SELECT id, name, company_name
    FROM customers
    ORDER BY id DESC
");
$customers = $stmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'new';
    $amount = trim($_POST['amount'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');

    if ($customer_id <= 0) {
        $error = '顧客を選択してください。';
    } elseif ($title === '') {
        $error = '案件名は必須です。';
    } elseif (!array_key_exists($status, $status_labels)) {
        $error = 'ステータスの値が正しくありません。';
    } elseif ($amount !== '' && !ctype_digit($amount)) {
        $error = '金額は0以上の整数で入力してください。';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE deals
                SET
                    customer_id = ?,
                    title = ?,
                    description = ?,
                    status = ?,
                    amount = ?,
                    due_date = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $customer_id,
                $title,
                $description,
                $status,
                $amount === '' ? 0 : (int)$amount,
                $due_date === '' ? null : $due_date,
                $id
            ]);

            add_log(
                $pdo,
                'update',
                'deal',
                $id,
                $title,
                '案件「' . $title . '」を更新しました。'
            );

            $_SESSION['flash'] = '案件情報を更新しました。';

            header('Location: ' . BASE_URL . '/deals/show.php?id=' . $id);
            exit;
        } catch (PDOException $e) {
            $error = '案件情報の更新に失敗しました。';
        }
    }
}

// POST後にエラーが出た時は入力値を優先、最初の表示はDBの値を使う
$form = [
    'customer_id' => $_POST['customer_id'] ?? $deal['customer_id'],
    'title' => $_POST['title'] ?? $deal['title'],
    'description' => $_POST['description'] ?? $deal['description'],
    'status' => $_POST['status'] ?? $deal['status'],
    'amount' => $_POST['amount'] ?? $deal['amount'],
    'due_date' => $_POST['due_date'] ?? $deal['due_date'],
];

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>案件編集</h2>

<p>
    <a href="<?= BASE_URL ?>/deals/show.php?id=<?= (int)$deal['id'] ?>">← 案件詳細へ戻る</a>
</p>

<?php if ($error): ?>
    <p style="color: red;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

<?php if (empty($customers)): ?>
    <p>顧客が登録されていません。先に顧客を登録してください。</p>
    <p>
        <a href="<?= BASE_URL ?>/customers/create.php">＋ 顧客登録へ</a>
    </p>
<?php else: ?>

<form method="post" action="<?= BASE_URL ?>/deals/edit.php?id=<?= (int)$deal['id'] ?>">
    <div>
        <label>
            顧客 <span style="color: red;">*</span><br>
            <select name="customer_id" required>
                <option value="">選択してください</option>

                <?php foreach ($customers as $customer): ?>
                    <option
                        value="<?= (int)$customer['id'] ?>"
                        <?= ((int)$form['customer_id'] === (int)$customer['id']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($customer['company_name'])): ?>
                            （<?= htmlspecialchars($customer['company_name'], ENT_QUOTES, 'UTF-8') ?>）
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <br>

    <div>
        <label>
            案件名 <span style="color: red;">*</span><br>
            <input
                type="text"
                name="title"
                value="<?= htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            説明<br>
            <textarea name="description" rows="5"><?= htmlspecialchars($form['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
    </div>

    <br>

    <div>
        <label>
            ステータス<br>
            <select name="status">
                <?php foreach ($status_labels as $key => $label): ?>
                    <option
                        value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                        <?= ($form['status'] === $key) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <br>

    <div>
        <label>
            金額<br>
            <input
                type="number"
                name="amount"
                min="0"
                step="1"
                value="<?= htmlspecialchars((string)$form['amount'], ENT_QUOTES, 'UTF-8') ?>"
                placeholder="例：50000"
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            期限<br>
            <input
                type="date"
                name="due_date"
                value="<?= htmlspecialchars($form['due_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>
    </div>

    <br>

    <button type="submit">更新する</button>
</form>

<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>