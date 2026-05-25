<?php

if (!isset($page_title)) {
    $page_title = 'ホーム';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?= BASE_URL ?>/favicon.png" type="image/png">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
</head>
<body>

<header>
    <nav>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?= BASE_URL ?>/dashboard.php">ダッシュボード</a>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="<?= BASE_URL ?>/admin/index.php">管理者メニュー</a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/logout.php">ログアウト</a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php">ログイン</a>
    <?php endif; ?>
    </nav>
</header>

<main>

<?php require_once BASE_PATH . '/app/views/flash.php'; ?>