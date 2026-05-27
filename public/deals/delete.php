<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/deals/index.php'));
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . url('/deals/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT title
        FROM deals
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $deal = $stmt->fetch();

    if (!$deal) {
        $_SESSION['flash'] = '削除対象の案件が見つかりませんでした。';
        header('Location: ' . url('/deals/index.php'));
        exit;
    }

    $deal_title = $deal['title'];

    $stmt = $pdo->prepare("
        DELETE FROM deals
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    add_log(
        $pdo,
        'delete',
        'deal',
        $id,
        $deal_title,
        '案件「' . $deal_title . '」を削除しました。'
    );

    $_SESSION['flash'] = '案件を削除しました。';

} catch (PDOException $e) {
    $_SESSION['flash'] = '案件の削除に失敗しました。';
}

header('Location: ' . url('/deals/index.php'));
exit;