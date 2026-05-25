<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$status_labels = [
    'todo' => '未着手',
    'doing' => '進行中',
    'done' => '完了',
];

$stmt = $pdo->query("
    SELECT
        tasks.id,
        tasks.title,
        tasks.description,
        tasks.status,
        tasks.due_date,
        tasks.created_at,
        tasks.updated_at,
        customers.name AS customer_name,
        deals.title AS deal_title
    FROM tasks
    LEFT JOIN customers ON customers.id = tasks.customer_id
    LEFT JOIN deals ON deals.id = tasks.deal_id
    ORDER BY tasks.id ASC
");

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

add_log(
    $pdo,
    'export',
    'task',
    null,
    'CSV',
    'タスク一覧をCSV出力しました。'
);

$filename = 'tasks_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=Shift_JIS');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

function csv_sjis($value) {
    return mb_convert_encoding((string)($value ?? ''), 'SJIS-win', 'UTF-8');
}

// 見出し行
fputcsv($output, array_map('csv_sjis', [
    'ID',
    'タスク名',
    '顧客名',
    '案件名',
    'ステータス',
    '期限',
    '説明',
    '登録日',
    '更新日',
]));

foreach ($tasks as $task) {
    fputcsv($output, [
        csv_sjis($task['id']),
        csv_sjis($task['title']),
        csv_sjis($task['customer_name'] ?: '-'),
        csv_sjis($task['deal_title'] ?: '-'),
        csv_sjis($status_labels[$task['status']] ?? $task['status']),
        csv_sjis($task['due_date']),
        csv_sjis($task['description']),
        csv_sjis($task['created_at']),
        csv_sjis($task['updated_at']),
    ]);
}

fclose($output);
exit;