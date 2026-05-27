<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/tasks/index.php'));
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . url('/tasks/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT title
        FROM tasks
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $task = $stmt->fetch();

    if (!$task) {
        $_SESSION['flash'] = '削除対象のタスクが見つかりませんでした。';
        header('Location: ' . url('/tasks/index.php'));
        exit;
    }

    $task_title = $task['title'];

    $stmt = $pdo->prepare("
        DELETE FROM tasks
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    add_log(
        $pdo,
        'delete',
        'task',
        $id,
        $task_title,
        'タスク「' . $task_title . '」を削除しました。'
    );

    $_SESSION['flash'] = 'タスクを削除しました。';

} catch (PDOException $e) {
    $_SESSION['flash'] = 'タスクの削除に失敗しました。';
}

header('Location: ' . url('/tasks/index.php'));
exit;