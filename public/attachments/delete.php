<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/deals/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash'] = 'ファイル情報が正しくありません。';
    header('Location: ' . BASE_URL . '/deals/index.php');
    exit;
}

// 添付ファイル情報を取得
$stmt = $pdo->prepare("
    SELECT *
    FROM attachments
    WHERE id = ?
");
$stmt->execute([$id]);
$attachment = $stmt->fetch();

if (!$attachment) {
    $_SESSION['flash'] = '削除対象のファイルが見つかりませんでした。';
    header('Location: ' . BASE_URL . '/deals/index.php');
    exit;
}

$target_type = $attachment['target_type'];
$target_id = (int)$attachment['target_id'];
$original_name = $attachment['original_name'];
$file_path = BASE_PATH . '/' . $attachment['file_path'];

// 今回は案件添付だけ対応
if ($target_type !== 'deal') {
    $_SESSION['flash'] = 'この添付ファイルは削除できません。';
    header('Location: ' . BASE_URL . '/deals/index.php');
    exit;
}

try {
    // 実ファイルを削除
    if (is_file($file_path)) {
        unlink($file_path);
    }

    // DBレコードを削除
    $stmt = $pdo->prepare("
        DELETE FROM attachments
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    add_log(
        $pdo,
        'delete',
        'attachment',
        $id,
        $original_name,
        '案件ID「' . $target_id . '」の添付ファイル「' . $original_name . '」を削除しました。'
    );

    $_SESSION['flash'] = '添付ファイルを削除しました。';

} catch (PDOException $e) {
    $_SESSION['flash'] = '添付ファイルの削除に失敗しました。';
}

header('Location: ' . BASE_URL . '/deals/show.php?id=' . $target_id);
exit;