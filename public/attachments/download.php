<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$id = (int)($_GET['id'] ?? 0);

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
    $_SESSION['flash'] = '指定されたファイルが見つかりません。';
    header('Location: ' . BASE_URL . '/deals/index.php');
    exit;
}

$file_path = BASE_PATH . '/' . $attachment['file_path'];

if (!is_file($file_path)) {
    $_SESSION['flash'] = 'ファイル本体が見つかりません。';
    header('Location: ' . BASE_URL . '/deals/show.php?id=' . (int)$attachment['target_id']);
    exit;
}

// 操作ログ
add_log(
    $pdo,
    'download',
    'attachment',
    $id,
    $attachment['original_name'],
    'ファイル「' . $attachment['original_name'] . '」をダウンロードしました。'
);

// ダウンロード用ヘッダー
header('Content-Type: ' . ($attachment['mime_type'] ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($file_path));
header('Content-Disposition: attachment; filename="' . basename($attachment['original_name']) . '"');
header('X-Content-Type-Options: nosniff');

readfile($file_path);
exit;