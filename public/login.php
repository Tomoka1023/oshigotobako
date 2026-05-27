<?php
require_once __DIR__ . '/../app/config.php';
session_start();

require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ユーザー名で検索
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header('Location: ' . url('/dashboard.php'));
        exit;
    } else {
        $error = 'ユーザー名またはパスワードが間違っています。';
    }
}

$page_title = 'ログイン';
require_once BASE_PATH . '/app/views/header.php';
?>

<h2>ログイン</h2>

<?php if ($error): ?>
    <p style="color: red;">
        <?= h($error) ?>
    </p>
<?php endif; ?>

<form method="post" action="<?= url('/login.php') ?>" class="login">
    <label>
        ユーザー名:
        <input type="text" name="username" required>
    </label>
    <br>

    <label>
        パスワード:
        <input type="password" name="password" required>
    </label>
    <br>

    <button type="submit">ログイン</button>
</form>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>