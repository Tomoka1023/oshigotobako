<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = 'ユーザー編集';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . url('/admin/users.php'));
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, username, email, role, created_at, updated_at
    FROM users
    WHERE id = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    require_once BASE_PATH . '/app/views/header.php';
    ?>

    <h2>ユーザーが見つかりません</h2>
    <p>指定されたユーザーは存在しないか、削除されています。</p>

    <p>
        <a href="<?= url('/admin/users.php') ?>">← ユーザー管理へ戻る</a>
    </p>

    <?php
    require_once BASE_PATH . '/app/views/footer.php';
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '') {
        $error = 'ユーザー名とメールアドレスは必須です。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'メールアドレスの形式が正しくありません。';
    } elseif (!in_array($role, ['admin', 'user'], true)) {
        $error = '権限の値が正しくありません。';
    } elseif ($id === (int)$_SESSION['user_id'] && $role !== 'admin') {
        $error = '自分自身の管理者権限は外せません。';
    } else {
        try {
            // 自分以外とユーザー名・メールアドレスが重複していないか確認
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM users
                WHERE (username = ? OR email = ?)
                  AND id != ?
            ");
            $stmt->execute([$username, $email, $id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = '同じユーザー名、またはメールアドレスがすでに登録されています。';
            } else {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET
                            username = ?,
                            email = ?,
                            password = ?,
                            role = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $username,
                        $email,
                        $hash,
                        $role,
                        $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET
                            username = ?,
                            email = ?,
                            role = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $username,
                        $email,
                        $role,
                        $id
                    ]);
                }

                add_log(
                    $pdo,
                    'update',
                    'user',
                    $id,
                    $username,
                    'ユーザー「' . $username . '」を更新しました。'
                );

                $_SESSION['flash'] = 'ユーザー情報を更新しました。';

                // 自分自身を編集した場合、セッションの表示情報も更新
                if ($id === (int)$_SESSION['user_id']) {
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;
                }

                header('Location: ' . url('/admin/users.php'));
                exit;
            }
        } catch (PDOException $e) {
            $error = 'ユーザー情報の更新に失敗しました。';
        }
    }
}

$form = [
    'username' => $_POST['username'] ?? $user['username'],
    'email' => $_POST['email'] ?? $user['email'],
    'role' => $_POST['role'] ?? $user['role'],
];

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>ユーザー編集</h2>

<p>
    <a href="<?= url('/admin/users.php') ?>">← ユーザー管理へ戻る</a>
</p>

<?php if ($error): ?>
    <p style="color: red;">
        <?= h($error) ?>
    </p>
<?php endif; ?>

<form method="post" action="<?= url('/admin/edit_user.php?id=' . (int)$user['id']) ?>">
    <div>
        <label>
            ユーザー名 <span style="color: red;">*</span><br>
            <input
                type="text"
                name="username"
                value="<?= h($form['username']) ?>"
                required
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            メールアドレス <span style="color: red;">*</span><br>
            <input
                type="email"
                name="email"
                value="<?= h($form['email']) ?>"
                required
            >
        </label>
    </div>

    <br>

    <div>
        <label>
            新しいパスワード<br>
            <input
                type="password"
                name="password"
                placeholder="変更しない場合は空欄"
            >
        </label>
        <p style="font-size: 13px; color: #8a776b;">
            パスワードを変更しない場合は空欄のままでOKです。
        </p>
    </div>

    <br>

    <div>
        <label>
            権限<br>
            <select name="role">
                <option value="user" <?= ($form['role'] ?? '') === 'user' ? 'selected' : '' ?>>
                    一般ユーザー
                </option>
                <option value="admin" <?= ($form['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                    管理者
                </option>
            </select>
        </label>
    </div>

    <br>

    <button type="submit">更新する</button>
</form>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>