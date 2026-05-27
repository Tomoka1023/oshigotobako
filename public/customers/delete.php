<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/customers/index.php'));
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . url('/customers/index.php'));
    exit;
}

try {
    // 削除前に顧客名を取得
    $stmt = $pdo->prepare("
        SELECT name
        FROM customers
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        $_SESSION['flash'] = '削除対象の顧客が見つかりませんでした。';
        header('Location: ' . url('/customers/index.php'));
        exit;
    }

    $customer_name = $customer['name'];

    // 顧客を削除
    $stmt = $pdo->prepare("
        DELETE FROM customers
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    add_log(
        $pdo,
        'delete',
        'customer',
        $id,
        $customer_name,
        '顧客「' . $customer_name . '」を削除しました。'
    );

    $_SESSION['flash'] = '顧客を削除しました。';

} catch (PDOException $e) {
    $_SESSION['flash'] = '顧客の削除に失敗しました。';
}

header('Location: ' . url('/customers/index.php'));
exit;