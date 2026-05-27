<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = 'ユーザー追加';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($username === '' || $email === '' || $password === '') {
        $error = 'ユーザー名、メールアドレス、パスワードは必須です。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'メールアドレスの形式が正しくありません。';
    } elseif (!in_array($role, ['admin', 'user'], true)) {
        $error = '権限の値が正しくありません。';
    } else {
        try {
            // ユーザー名・メールアドレスの重複チェック
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM users 
                WHERE username = ? OR email = ?
            ");
            $stmt->execute([$username, $email]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = '同じユーザー名、またはメールアドレスがすでに登録されています。';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        username,
                        email, 
                        password, 
                        role
                    ) VALUES (
                        ?, ?, ?, ?
                    )
                ");

                $stmt->execute([
                    $username, 
                    $email, 
                    $hash, 
                    $role
                ]);

                $user_id = (int)$pdo->lastInsertId();

                add_log(
                    $pdo,
                    'create',
                    'user',
                    $user_id,
                    $username,
                    'ユーザー「' . $username . '」を登録しました。'
                );

                $_SESSION['flash'] = 'ユーザーを登録しました。';

                header('Location: ' . url('/admin/users.php'));
                exit;
            }
        } catch (PDOException $e) {
            $error = 'ユーザー登録に失敗しました。';
        }
    }
}

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>ユーザー追加</h2>

<p>
    <a href="<?= url('/admin/users.php') ?>">← ユーザー一覧へ戻る</a>
</p>

<?php if ($error): ?>
    <p style="color: red;">
        <?= h($error) ?>
    </p>
<?php endif; ?>

<form method="post" action="<?= url('/admin/create_user.php') ?>">
    <div>
        <label>
            ユーザー名<br>
            <input
                type="text"
                name="username"
                value="<?= h($_POST['username'] ?? '') ?>"
                required
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
                required
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            パスワード<br>
            <input type="password" name="password" required>
        </label>
    </div>

    <br>

    <div>
        <label>
            権限<br>
            <select name="role">
                <option value="user" <?= (($_POST['role'] ?? '') === 'user') ? 'selected' : '' ?>>
                    一般ユーザー
                </option>
                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>
                    管理者
                </option>
            </select>
        </label>
    </div>

    <br>

    <button type="submit">登録する</button>
</form>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>