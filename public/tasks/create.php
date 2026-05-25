<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = 'タスク登録';

$error = '';

$status_labels = [
    'todo' => '未着手',
    'doing' => '進行中',
    'done' => '完了',
];

// 案件一覧を取得：顧客名も一緒に取る
$stmt = $pdo->query("
    SELECT
        deals.id,
        deals.title,
        customers.name AS customer_name,
        customers.company_name AS customer_company
    FROM deals
    LEFT JOIN customers ON customers.id = deals.customer_id
    ORDER BY deals.id DESC
");

$deals = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deal_id = (int)($_POST['deal_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $status = $_POST['status'] ?? 'todo';

    if ($deal_id <= 0) {
        $error = '案件を選択してください。';
    } elseif ($title === '') {
        $error = 'タスク名は必須です。';
    } elseif (!array_key_exists($status, $status_labels)) {
        $error = 'ステータスの値が正しくありません。';
    } else {
        try {
            // 選ばれた案件から customer_id を自動取得
            $stmt = $pdo->prepare("
                SELECT customer_id
                FROM deals
                WHERE id = ?
            ");
            $stmt->execute([$deal_id]);
            $deal = $stmt->fetch();

            if (!$deal) {
                $error = '選択された案件が見つかりません。';
            } else {
                $customer_id = (int)$deal['customer_id'];

                $stmt = $pdo->prepare("
                    INSERT INTO tasks (
                        deal_id,
                        customer_id,
                        title,
                        description,
                        due_date,
                        status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?
                    )
                ");

                $stmt->execute([
                    $deal_id,
                    $customer_id,
                    $title,
                    $description,
                    $due_date === '' ? null : $due_date,
                    $status
                ]);

                $task_id = $pdo->lastInsertId();

                add_log(
                    $pdo,
                    'create',
                    'task',
                    $task_id,
                    $title,
                    'タスク「' . $title . '」を登録しました。'
                );

                $_SESSION['flash'] = 'タスクを登録しました。';

                header('Location: ' . BASE_URL . '/tasks/index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'タスクの登録に失敗しました。';
        }
    }
}

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>タスク登録</h2>

<p>
    <a href="<?= BASE_URL ?>/tasks/index.php">← タスク一覧へ戻る</a>
</p>

<?php if ($error): ?>
    <p style="color: red;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

<?php if (empty($deals)): ?>
    <p>先に案件を登録してください。</p>
    <p>
        <a href="<?= BASE_URL ?>/deals/create.php">＋ 案件登録へ</a>
    </p>
<?php else: ?>

<form method="post" action="<?= BASE_URL ?>/tasks/create.php">
    <div>
        <label>
            案件 <span style="color: red;">*</span><br>
            <select name="deal_id" required>
                <option value="">選択してください</option>

                <?php foreach ($deals as $deal): ?>
                    <option
                        value="<?= (int)$deal['id'] ?>"
                        <?= ((int)($_POST['deal_id'] ?? 0) === (int)$deal['id']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($deal['title'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($deal['customer_name'])): ?>
                            （<?= htmlspecialchars($deal['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($deal['customer_company'])): ?>
                                / <?= htmlspecialchars($deal['customer_company'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                            ）
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <br>

    <div>
        <label>
            タスク名 <span style="color: red;">*</span><br>
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
            期限<br>
            <input
                type="date"
                name="due_date"
                value="<?= htmlspecialchars($_POST['due_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
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
                        <?= (($_POST['status'] ?? 'todo') === $key) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <br>

    <button type="submit">登録する</button>
</form>

<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>