<?php
require_once __DIR__ . '/../app/config.php';
require_once BASE_PATH . '/app/auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$page_title = 'ダッシュボード';

// 顧客数
$stmt = $pdo->query("SELECT COUNT(*) FROM customers");
$customer_count = $stmt->fetchColumn();

// 案件数
$stmt = $pdo->query("SELECT COUNT(*) FROM deals");
$deal_count = $stmt->fetchColumn();

// 未完了タスク数
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM tasks
    WHERE status != 'done'
");
$undone_task_count = $stmt->fetchColumn();

// 期限切れタスク数
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM tasks
    WHERE status != 'done'
      AND due_date IS NOT NULL
      AND due_date < CURDATE()
");
$overdue_task_count = $stmt->fetchColumn();

// 今日のタスク
$stmt = $pdo->query("
    SELECT
        tasks.*,
        deals.title AS deal_title,
        customers.name AS customer_name
    FROM tasks
    LEFT JOIN deals ON deals.id = tasks.deal_id
    LEFT JOIN customers ON customers.id = tasks.customer_id
    WHERE tasks.status != 'done'
      AND tasks.due_date = CURDATE()
    ORDER BY tasks.id DESC
    LIMIT 5
");
$today_tasks = $stmt->fetchAll();

// 近日のタスク
$stmt = $pdo->query("
    SELECT
        tasks.*,
        deals.title AS deal_title,
        customers.name AS customer_name
    FROM tasks
    LEFT JOIN deals ON deals.id = tasks.deal_id
    LEFT JOIN customers ON customers.id = tasks.customer_id
    WHERE tasks.status != 'done'
      AND tasks.due_date IS NOT NULL
      AND tasks.due_date > CURDATE()
    ORDER BY tasks.due_date ASC
    LIMIT 5
");
$upcoming_tasks = $stmt->fetchAll();

// 案件ステータス別件数
$status_labels = [
    'new' => '新規',
    'proposal' => '提案中',
    'estimate' => '見積済',
    'ordered' => '受注',
    'completed' => '完了',
    'lost' => '失注',
];

$deal_status_counts = [
    'new' => 0,
    'proposal' => 0,
    'estimate' => 0,
    'ordered' => 0,
    'completed' => 0,
    'lost' => 0,
];

$stmt = $pdo->query("
    SELECT status, COUNT(*) AS count
    FROM deals
    GROUP BY status
");

$deal_status_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($deal_status_rows as $row) {
    if (isset($deal_status_counts[$row['status']])) {
        $deal_status_counts[$row['status']] = (int)$row['count'];
    }
}

// グラフ用：最大件数を取得
$max_status_count = 0;

foreach ($deal_status_counts as $count) {
    if ((int)$count > $max_status_count) {
        $max_status_count = (int)$count;
    }
}

require_once BASE_PATH . '/app/views/header.php';
?>

<h2>ダッシュボード</h2>

<p>
    ようこそ、
    <?= h($_SESSION['username'] ?? '') ?>さん
</p>

<section>
    <h3>全体サマリー</h3>

    <div class="dashboard-cards">
        <div class="dashboard-card">
            <p>顧客数</p>
            <strong><?= (int)$customer_count ?></strong>
        </div>

        <div class="dashboard-card">
            <p>案件数</p>
            <strong><?= (int)$deal_count ?></strong>
        </div>

        <div class="dashboard-card">
            <p>未完了タスク</p>
            <strong><?= (int)$undone_task_count ?></strong>
        </div>

        <div class="dashboard-card">
            <p>期限切れタスク</p>
            <strong><?= (int)$overdue_task_count ?></strong>
        </div>
    </div>
</section>

<section class="dashboard-section">
    <div class="section-header">
        <h3>案件ステータス別</h3>
        <p>現在の案件がどの段階にあるかを確認できます。</p>
    </div>

    <div class="stage-summary-grid">
        <?php foreach ($deal_status_counts as $status => $count): ?>
            <div class="stage-summary-card">
                <div class="stage-name">
                    <?= h($status_labels[$status] ?? $status) ?>
                    <span><?= h($status) ?></span>
                </div>

                <div class="stage-count">
                    <?= (int)$count ?><span>件</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="stage-chart-card">
        <h3>ステータス別 件数グラフ</h3>

        <div class="stage-bar-list">
            <?php foreach ($deal_status_counts as $status => $count): ?>
                <?php
                    $count = (int)$count;
                    $width = $max_status_count > 0 ? ($count / $max_status_count) * 100 : 0;
                ?>

                <div class="stage-bar-row">
                    <div class="stage-bar-label">
                        <?= h($status_labels[$status] ?? $status) ?>
                    </div>

                    <div class="stage-bar-track">
                        <div class="stage-bar-fill" style="width: <?= $width ?>%;"></div>
                    </div>

                    <div class="stage-bar-count">
                        <?= $count ?>件
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section>
    <h3>メニュー</h3>

    <ul>
        <li><a href="<?= url('/customers/index.php') ?>">顧客一覧</a></li>
        <li><a href="<?= url('/deals/index.php') ?>">案件一覧</a></li>
        <li><a href="<?= url('/tasks/index.php') ?>">タスク一覧</a></li>
    </ul>
</section>

<section>
    <h3>今日のタスク</h3>

    <?php if (empty($today_tasks)): ?>
        <p>今日が期限の未完了タスクはありません。</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>タスク名</th>
                        <th>顧客</th>
                        <th>案件</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($today_tasks as $task): ?>
                        <tr>
                            <td><?= h($task['title']) ?></td>
                            <td><?= h($task['customer_name'] ?: '-') ?></td>
                            <td><?= h($task['deal_title'] ?: '-') ?></td>
                            <td>
                                <a href="<?= url('/tasks/show.php?id=' . (int)$task['id']) ?>">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section>
    <h3>近日のタスク</h3>

    <?php if (empty($upcoming_tasks)): ?>
        <p>近日中の未完了タスクはありません。</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>期限</th>
                        <th>タスク名</th>
                        <th>顧客</th>
                        <th>案件</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming_tasks as $task): ?>
                        <tr>
                            <td><?= h($task['due_date']) ?></td>
                            <td><?= h($task['title']) ?></td>
                            <td><?= h($task['customer_name'] ?: '-') ?></td>
                            <td><?= h($task['deal_title'] ?: '-') ?></td>
                            <td>
                                <a href="<?= url('/tasks/show.php?id=' . (int)$task['id']) ?>">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once BASE_PATH . '/app/views/footer.php'; ?>