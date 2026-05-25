<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$status_labels = [
    'new' => '新規',
    'proposal' => '提案中',
    'estimate' => '見積済',
    'ordered' => '受注',
    'completed' => '完了',
    'lost' => '失注',
];

$stmt = $pdo->query("
    SELECT
        deals.id,
        deals.title,
        deals.description,
        deals.status,
        deals.amount,
        deals.due_date,
        deals.created_at,
        deals.updated_at,
        customers.name AS customer_name,
        customers.company_name AS customer_company
    FROM deals
    LEFT JOIN customers ON customers.id = deals.customer_id
    ORDER BY deals.id ASC
");

$deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

add_log(
    $pdo,
    'export',
    'deal',
    null,
    'CSV',
    '案件一覧をCSV出力しました。'
);

$filename = 'deals_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=Shift_JIS');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

function csv_sjis($value) {
    return mb_convert_encoding((string)($value ?? ''), 'SJIS-win', 'UTF-8');
}

// 見出し行
fputcsv($output, array_map('csv_sjis', [
    'ID',
    '案件名',
    '顧客名',
    '会社名',
    'ステータス',
    '金額',
    '期限',
    '説明',
    '登録日',
    '更新日',
]));

foreach ($deals as $deal) {
    fputcsv($output, [
        csv_sjis($deal['id']),
        csv_sjis($deal['title']),
        csv_sjis($deal['customer_name'] ?: '-'),
        csv_sjis($deal['customer_company'] ?: '-'),
        csv_sjis($status_labels[$deal['status']] ?? $deal['status']),
        csv_sjis($deal['amount']),
        csv_sjis($deal['due_date']),
        csv_sjis($deal['description']),
        csv_sjis($deal['created_at']),
        csv_sjis($deal['updated_at']),
    ]);
}

fclose($output);
exit;