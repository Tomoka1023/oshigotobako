<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';

$page_title = '管理者ダッシュボード';
require_once BASE_PATH . '/app/views/header.php';
?>

<h2>管理者ダッシュボード</h2>

<p>管理者専用ページです。</p>

<ul>
    <li><a href="<?= BASE_URL ?>/admin/users.php">ユーザー管理</a></li>
    <li><a href="<?= BASE_URL ?>/admin/logs.php">操作ログ</a></li>
</ul>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>