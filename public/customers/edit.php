<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = '顧客編集';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . BASE_URL . '/customers/index.php');
    exit;
}

// まず編集対象の顧客を取得
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $memo = trim($_POST['memo'] ?? '');

    if ($name === '') {
        $error = '顧客名は必須です。';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'メールアドレスの形式が正しくありません。';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE customers
                SET
                    name = ?,
                    company_name = ?,
                    email = ?,
                    phone = ?,
                    address = ?,
                    memo = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $company_name,
                $email,
                $phone,
                $address,
                $memo,
                $id
            ]);

            add_log(
                $pdo,
                'update',
                'customer',
                $id,
                $name,
                '顧客「' . $name . '」を更新しました。'
            );

            $_SESSION['flash'] = '顧客情報を更新しました。';

            header('Location: ' . BASE_URL . '/customers/show.php?id=' . $id);
            exit;
        } catch (PDOException $e) {
            $error = '顧客情報の更新に失敗しました。';
        }
    }
}

// POST後にエラーが出た時は入力値を優先、最初の表示はDBの値を使う
$form = [
    'name' => $_POST['name'] ?? $customer['name'],
    'company_name' => $_POST['company_name'] ?? $customer['company_name'],
    'email' => $_POST['email'] ?? $customer['email'],
    'phone' => $_POST['phone'] ?? $customer['phone'],
    'address' => $_POST['address'] ?? $customer['address'],
    'memo' => $_POST['memo'] ?? $customer['memo'],
];

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>顧客編集</h2>

<p>
    <a href="<?= BASE_URL ?>/customers/show.php?id=<?= (int)$customer['id'] ?>">← 顧客詳細へ戻る</a>
</p>

<?php if ($error): ?>
    <p style="color: red;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

<form method="post" action="<?= BASE_URL ?>/customers/edit.php?id=<?= (int)$customer['id'] ?>">
    <div>
        <label>
            顧客名 <span style="color: red;">*</span><br>
            <input
                type="text"
                name="name"
                value="<?= htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            会社名<br>
            <input
                type="text"
                name="company_name"
                value="<?= htmlspecialchars($form['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            メールアドレス<br>
            <input
                type="email"
                name="email"
                value="<?= htmlspecialchars($form['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            電話番号<br>
            <input
                type="text"
                name="phone"
                value="<?= htmlspecialchars($form['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            住所<br>
            <textarea name="address" rows="3"><?= htmlspecialchars($form['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
    </div>

    <br>

    <div>
        <label>
            メモ<br>
            <textarea name="memo" rows="5"><?= htmlspecialchars($form['memo'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </label>
    </div>

    <br>

    <button type="submit">更新する</button>
</form>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>