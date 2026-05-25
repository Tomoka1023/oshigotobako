<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = 'タスク編集';

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

// 編集対象のタスクを取得
$stmt = $pdo->prepare("
    SELECT *
    FROM tasks
    WHERE id = ?
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

// 案件一覧を取得
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

$error = '';

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
                    UPDATE tasks
                    SET
                        deal_id = ?,
                        customer_id = ?,
                        title = ?,
                        description = ?,
                        due_date = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $deal_id,
                    $customer_id,
                    $title,
                    $description,
                    $due_date === '' ? null : $due_date,
                    $status,
                    $id
                ]);

                add_log(
                    $pdo,
                    'update',
                    'task',
                    $id,
                    $title,
                    'タスク「' . $title . '」を更新しました。'
                );

                $_SESSION['flash'] = 'タスク情報を更新しました。';

                header('Location: ' . BASE_URL . '/tasks/show.php?id=' . $id);
                exit;
            }
        } catch (PDOException $e) {
            $error = 'タスク情報の更新に失敗しました。';
        }
    }
}

// エラー時は入力値を優先、初回表示はDBの値を使う
$form = [
    'deal_id' => $_POST['deal_id'] ?? $task['deal_id'],
    'title' => $_POST['title'] ?? $task['title'],
    'description' => $_POST['description'] ?? $task['description'],
    'due_date' => $_POST['due_date'] ?? $task['due_date'],
    'status' => $_POST['status'] ?? $task['status'],
];

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>タスク編集</h2>

<p>
    <a href="<?= BASE_URL ?>/tasks/show.php?id=<?= (int)$task['id'] ?>">← タスク詳細へ戻る</a>
</p>

<?php if ($error): ?>
    <p style="color: red;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

<?php if (empty($deals)): ?>
    <p>案件が登録されていません。先に案件を登録してください。</p>
    <p>
        <a href="<?= BASE_URL ?>/deals/create.php">＋ 案件登録へ</a>
    </p>
<?php else: ?>

<form method="post" action="<?= BASE_URL ?>/tasks/edit.php?id=<?= (int)$task['id'] ?>">
    <div>
        <label>
            案件 <span style="color: red;">*</span><br>
            <select name="deal_id" required>
                <option value="">選択してください</option>

                <?php foreach ($deals as $deal): ?>
                    <option
                        value="<?= (int)$deal['id'] ?>"
                        <?= ((int)$form['deal_id'] === (int)$deal['id']) ? 'selected' : '' ?>
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
            期限<br>
            <input
                type="date"
                name="due_date"
                value="<?= htmlspecialchars($form['due_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
                        <?= ($form['status'] === $key) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <br>

    <button type="submit">更新する</button>
</form>

<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>