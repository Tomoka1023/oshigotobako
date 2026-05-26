<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$target_type = $_POST['target_type'] ?? '';
$target_id = (int)($_POST['target_id'] ?? 0);

$allowed_targets = ['deal'];

if (!in_array($target_type, $allowed_targets, true) || $target_id <= 0) {
    $_SESSION['flash'] = '添付先の情報が正しくありません。';
    header('Location: ' . BASE_URL . '/deals/index.php');
    exit;
}

if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash'] = 'ファイルのアップロードに失敗しました。';
    header('Location: ' . BASE_URL . '/deals/show.php?id=' . $target_id);
    exit;
}

$file = $_FILES['attachment'];

$max_size = 5 * 1024 * 1024; // 5MB

if ($file['size'] > $max_size) {
    $_SESSION['flash'] = 'ファイルサイズは5MB以内にしてください。';
    header('Location: ' . BASE_URL . '/deals/show.php?id=' . $target_id);
    exit;
}

$original_name = $file['name'];
$tmp_name = $file['tmp_name'];
$file_size = (int)$file['size'];

$extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

$allowed_extensions = [
    'jpg',
    'jpeg',
    'png',
    'gif',
    'webp',
    'pdf',
    'txt',
    'csv',
    'xlsx',
    'docx',
];

if (!in_array($extension, $allowed_extensions, true)) {
    $_SESSION['flash'] = 'この種類のファイルはアップロードできません。';
    header('Location: ' . BASE_URL . '/deals/show.php?id=' . $target_id);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $finfo->file($tmp_name);

$saved_name = bin2hex(random_bytes(16)) . '.' . $extension;

$upload_dir = BASE_PATH . '/storage/uploads/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$save_path = $upload_dir . $saved_name;

if (!move_uploaded_file($tmp_name, $save_path)) {
    $_SESSION['flash'] = 'ファイルの保存に失敗しました。';
    header('Location: ' . BASE_URL . '/deals/show.php?id=' . $target_id);
    exit;
}

$db_file_path = 'storage/uploads/' . $saved_name;

$stmt = $pdo->prepare("
    INSERT INTO attachments (
        target_type,
        target_id,
        original_name,
        saved_name,
        file_path,
        mime_type,
        file_size,
        uploaded_by
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?
    )
");

$stmt->execute([
    $target_type,
    $target_id,
    $original_name,
    $saved_name,
    $db_file_path,
    $mime_type,
    $file_size,
    $_SESSION['user_id'] ?? null,
]);

$attachment_id = $pdo->lastInsertId();

add_log(
    $pdo,
    'upload',
    'attachment',
    $attachment_id,
    $original_name,
    '案件ID「' . $target_id . '」にファイル「' . $original_name . '」を添付しました。'
);

$_SESSION['flash'] = 'ファイルをアップロードしました。';

header('Location: ' . BASE_URL . '/deals/show.php?id=' . $target_id);
exit;