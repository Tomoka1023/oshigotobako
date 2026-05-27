<?php
require_once BASE_PATH . '/app/helpers.php';

if (!empty($_SESSION['flash'])):
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
?>

    <div class="flash-message">
        <?= h($flash) ?>
    </div>

<?php endif; ?>