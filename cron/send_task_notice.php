<?php
require_once __DIR__ . '/../app/config.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';
require_once BASE_PATH . '/app/send_mail.php';

date_default_timezone_set('Asia/Tokyo');

// 期限が3日以内の未完了タスクを取得
$stmt = $pdo->query("
    SELECT
        tasks.id,
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
    ORDER BY tasks.due_date ASC, tasks.id ASC
");

$tasks = $stmt->fetchAll();

if (empty($tasks)) {
    echo "期限が近い未完了タスクはありません。\n";
    exit;
}

// 管理者ユーザーのメールアドレスを取得
$stmt = $pdo->query("
    SELECT id, username, email
    FROM users
    WHERE role = 'admin'
      AND email IS NOT NULL
      AND email != ''
");

$admins = $stmt->fetchAll();

if (empty($admins)) {
    echo "送信先の管理者メールアドレスがありません。\n";
    exit;
}

$subject = '【おしごと箱】期限が近いタスクのお知らせ';

$body = "期限が近い未完了タスクがあります。\n\n";

foreach ($tasks as $task) {
    $body .= "--------------------\n";
    $body .= "タスク名：" . $task['title'] . "\n";
    $body .= "期限：" . $task['due_date'] . "\n";
    $body .= "顧客：" . ($task['customer_name'] ?: '-') . "\n";
    $body .= "案件：" . ($task['deal_title'] ?: '-') . "\n\n";
}

$body .= "おしごと箱より自動通知\n";

$success_count = 0;
$failed_count = 0;

foreach ($admins as $admin) {
    $result = send_mail($admin['email'], $subject, $body);

    if ($result) {
        $success_count++;

        add_log(
            $pdo,
            'send_mail',
            'task',
            null,
            '期限間近タスク自動通知',
            'cronで期限が近いタスクの通知メールを送信しました。送信先：' . $admin['email']
        );
    } else {
        $failed_count++;

        $error_message = $_SESSION['mail_error'] ?? '原因不明のエラー';
        unset($_SESSION['mail_error']);

        add_log(
            $pdo,
            'send_mail_failed',
            'task',
            null,
            '期限間近タスク自動通知',
            'cronの通知メール送信に失敗しました。送信先：' . $admin['email'] . ' / ' . $error_message
        );
    }
}

echo "メール通知処理が完了しました。\n";
echo "成功：" . $success_count . "件\n";
echo "失敗：" . $failed_count . "件\n";