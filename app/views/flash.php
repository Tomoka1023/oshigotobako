<?php
if (!empty($_SESSION['flash'])):
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
?>

    <div class="flash-message">
        <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
    </div>

<?php endif; ?>