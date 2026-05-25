<?php
require_once __DIR__ . '/../../app/config.php';
require_once BASE_PATH . '/app/admin_auth.php';
require_once BASE_PATH . '/app/db.php';
require_once BASE_PATH . '/app/helpers.php';

$stmt = $pdo->query("
    SELECT
        id,
        name,
        company_name,
        email,
        phone,
        address,
        memo,
        created_at,
        updated_at
    FROM customers
    ORDER BY id ASC
");

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

add_log(
    $pdo,
    'export',
    'customer',
    null,
    'CSV',
    '顧客一覧をCSV出力しました。'
);

$filename = 'customers_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=Shift_JIS');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

function csv_sjis($value) {
    return mb_convert_encoding((string)($value ?? ''), 'SJIS-win', 'UTF-8');
}

fputcsv($output, array_map('csv_sjis', [
    'ID',
    '顧客名',
    '会社名',
    'メールアドレス',
    '電話番号',
    '住所',
    'メモ',
    '登録日',
    '更新日',
]));

foreach ($customers as $customer) {
    fputcsv($output, [
        csv_sjis($customer['id']),
        csv_sjis($customer['name']),
        csv_sjis($customer['company_name']),
        csv_sjis($customer['email']),
        csv_sjis('="' . $customer['phone'] . '"'),
        csv_sjis($customer['address']),
        csv_sjis($customer['memo']),
        csv_sjis($customer['created_at']),
        csv_sjis($customer['updated_at']),
    ]);
}

fclose($output);
exit;