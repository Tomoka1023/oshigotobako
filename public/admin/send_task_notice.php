<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';
require_once BASE_PATH . '/app/send_mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

// 管理者のメールアドレスを取得
$stmt = $pdo->prepare("
    SELECT email
    FROM users
    WHERE id = ?
      AND role = 'admin'
");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

if (!$admin || empty($admin['email'])) {
    $_SESSION['flash'] = '管理者のメールアドレスが見つかりませんでした。';
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

// 期限が3日以内の未完了タスクを取得
$stmt = $pdo->query("
    SELECT
        tasks.title,
        tasks.due_date,
        tasks.status,
        customers.name AS customer_name,
        deals.title AS deal_title
    FROM tasks
    LEFT JOIN customers ON customers.id = tasks.customer_id
    LEFT JOIN deals ON deals.id = tasks.deal_id
    WHERE tasks.status != 'done'
      AND tasks.due_date IS NOT NULL
      AND tasks.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ORDER BY tasks.due_date ASC
");

$tasks = $stmt->fetchAll();

if (empty($tasks)) {
    $_SESSION['flash'] = '期限が近い未完了タスクはありません。';
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$subject = '【おしごと箱】期限が近いタスクのお知らせ';

$body = "期限が近い未完了タスクがあります。\n\n";

foreach ($tasks as $task) {
    $body .= "--------------------\n";
    $body .= "タスク名：" . $task['title'] . "\n";
    $body .= "期限：" . $task['due_date'] . "\n";
    $body .= "顧客：" . ($task['customer_name'] ?: '-') . "\n";
    $body .= "案件：" . ($task['deal_title'] ?: '-') . "\n";
}

$body .= "\nおしごと箱より自動通知";

$result = send_mail($admin['email'], $subject, $body);

if ($result) {
    add_log(
        $pdo,
        'send_mail',
        'task',
        null,
        '期限間近タスク通知',
        '期限が近いタスクの通知メールを送信しました。'
    );

    $_SESSION['flash'] = '期限が近いタスクの通知メールを送信しました。';
} else {
    $error_message = $_SESSION['mail_error'] ?? '原因不明のエラーです。';
    unset($_SESSION['mail_error']);

    $_SESSION['flash'] = '通知メールの送信に失敗しました。' . $error_message;
}

header('Location: ' . BASE_URL . '/admin/index.php');
exit;