<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/admin/users.php'));
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . url('/admin/users.php'));
    exit;
}

// 自分自身は削除できないようにする
if ($id === (int)$_SESSION['user_id']) {
    $_SESSION['flash'] = '自分自身は削除できません。';
    header('Location: ' . url('/admin/users.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT username
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['flash'] = '削除対象のユーザーが見つかりませんでした。';
        header('Location: ' . url('/admin/users.php'));
        exit;
    }

    $user_name = $user['username'];

    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    add_log(
        $pdo,
        'delete',
        'user',
        $id,
        $user_name,
        'ユーザー「' . $user_name . '」を削除しました。'
    );

    $_SESSION['flash'] = 'ユーザーを削除しました。';

} catch (PDOException $e) {
    $_SESSION['flash'] = 'ユーザーの削除に失敗しました。';
}

header('Location: ' . url('/admin/users.php'));
exit;