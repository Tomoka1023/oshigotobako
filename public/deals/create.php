<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = '案件登録';

$error = '';

$status_labels = [
    'new' => '新規',
    'proposal' => '提案中',
    'estimate' => '見積済',
    'ordered' => '受注',
    'completed' => '完了',
    'lost' => '失注',
];

// 顧客一覧を取得
$stmt = $pdo->query("
    SELECT id, name, company_name
    FROM customers
    ORDER BY id DESC
");
$customers = $stmt->fetchAll();

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
                INSERT INTO deals (
                    customer_id,
                    title,
                    description,
                    status,
                    amount,
                    due_date
                ) VALUES (
                    ?, ?, ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $customer_id,
                $title,
                $description,
                $status,
                $amount === '' ? 0 : (int)$amount,
                $due_date === '' ? null : $due_date
            ]);

            $new_id = $pdo->lastInsertId();

            add_log(
                $pdo,
                'create',
                'deal',
                $new_id,
                $title,
                '案件「' . $title . '」を登録しました。'
            );

            $_SESSION['flash'] = '案件を登録しました。';

            header('Location: ' . BASE_URL . '/deals/index.php');
            exit;
        } catch (PDOException $e) {
            $error = '案件の登録に失敗しました。';
        }
    }
}

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>案件登録</h2>

<p>
    <a href="<?= BASE_URL ?>/deals/index.php">← 案件一覧へ戻る</a>
</p>

<?php if ($error): ?>
    <p style="color: red;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

<?php if (empty($customers)): ?>
    <p>先に顧客を登録してください。</p>
    <p>
        <a href="<?= BASE_URL ?>/customers/create.php">＋ 顧客登録へ</a>
    </p>
<?php else: ?>

<form method="post" action="<?= BASE_URL ?>/deals/create.php">
    <div>
        <label>
            顧客 <span style="color: red;">*</span><br>
            <select name="customer_id" required>
                <option value="">選択してください</option>

                <?php foreach ($customers as $customer): ?>
                    <option
                        value="<?= (int)$customer['id'] ?>"
                        <?= ((int)($_POST['customer_id'] ?? 0) === (int)$customer['id']) ? 'selected' : '' ?>
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
                value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            説明<br>
            <textarea name="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
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
                        <?= (($_POST['status'] ?? 'new') === $key) ? 'selected' : '' ?>
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
                value="<?= htmlspecialchars($_POST['amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
                value="<?= htmlspecialchars($_POST['due_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>
    </div>

    <br>

    <button type="submit">登録する</button>
</form>

<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>