<?php
require_once BASE_PATH . '/app/helpers.php';

if (!isset($page_title)) {
    $page_title = 'ホーム';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= url('/favicon.png') ?>" type="image/png">
    <title><?= h($page_title) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
</head>
<body>

<header>
    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= url('/dashboard.php') ?>">ダッシュボード</a>

            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a href="<?= url('/admin/index.php') ?>">管理者メニュー</a>
            <?php endif; ?>

            <a href="<?= url('/logout.php') ?>">ログアウト</a>
        <?php else: ?>
            <a href="<?= url('/login.php') ?>">ログイン</a>
        <?php endif; ?>
    </nav>
</header>

<main>

<?php require_once BASE_PATH . '/app/views/flash.php'; ?>