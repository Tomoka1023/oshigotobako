<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = '顧客登録';

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
                INSERT INTO customers (
                    name,
                    company_name,
                    email,
                    phone,
                    address,
                    memo
                ) VALUES (
                    ?, ?, ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $name,
                $company_name,
                $email,
                $phone,
                $address,
                $memo
            ]);
            
            $customer_id = (int)$pdo->lastInsertId();
            
            add_log(
                $pdo,
                'create',
                'customer',
                $customer_id,
                $name,
                '顧客「' . $name . '」を登録しました。'
            );
            
            $_SESSION['flash'] = '顧客を登録しました。';
            
            header('Location: ' . url('/customers/index.php'));
            exit;
        } catch (PDOException $e) {
            $error = '顧客の登録に失敗しました。';
        }
    }
}

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>顧客登録</h2>

<p>
    <a href="<?= url('/customers/index.php') ?>">← 顧客一覧へ戻る</a>
</p>

<?php if ($error): ?>
    <p style="color: red;">
        <?= h($error) ?>
    </p>
<?php endif; ?>

<form method="post" action="<?= url('/customers/create.php') ?>">
    <div>
        <label>
            顧客名 <span style="color: red;">*</span><br>
            <input
                type="text"
                name="name"
                value="<?= h($_POST['name'] ?? '') ?>"
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
                value="<?= h($_POST['company_name'] ?? '') ?>"
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
                value="<?= h($_POST['email'] ?? '') ?>"
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
                value="<?= h($_POST['phone'] ?? '') ?>"
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            住所<br>
            <textarea name="address" rows="3"><?= h($_POST['address'] ?? '') ?></textarea>
        </label>
    </div>

    <br>

    <div>
        <label>
            メモ<br>
            <textarea name="memo" rows="5"><?= h($_POST['memo'] ?? '') ?></textarea>
        </label>
    </div>

    <br>

    <button type="submit">登録する</button>
</form>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>