<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = '管理者メニュー';
require_once BASE_PATH . '/app/views/header.php';
?>

<h2>管理者メニュー</h2>

<p>管理者専用ページです。</p>

<ul>
    <li><a href="<?= url('/admin/users.php') ?>">ユーザー管理</a></li>
    <li><a href="<?= url('/admin/logs.php') ?>">操作ログ</a></li>
</ul>

<form method="post" action="<?= url('/admin/send_task_notice.php') ?>">
    <button type="submit" onclick="return confirm('期限が近いタスクの通知メールを送信しますか？');">
        期限間近タスクをメール通知
    </button>
</form>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>