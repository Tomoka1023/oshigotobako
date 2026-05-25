<?php

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url($path = '')
{
    return BASE_URL . $path;
}

function redirect($path)
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function format_date($date)
{
    if (empty($date)) {
        return '-';
    }

    return date('Y/m/d', strtotime($date));
}

function format_datetime($datetime)
{
    if (empty($datetime)) {
        return '-';
    }

    return date('Y/m/d H:i', strtotime($datetime));
}

function format_yen($amount)
{
    if ((int)$amount <= 0) {
        return '-';
    }

    return '¥' . number_format((int)$amount);
}

function status_label($status, $labels)
{
    return $labels[$status] ?? $status;
}

function add_log($pdo, $action, $target_type, $target_id, $target_name, $message)
{
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO operation_logs (
            user_id,
            action,
            target_type,
            target_id,
            target_name,
            message,
            ip_address
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        $user_id,
        $action,
        $target_type,
        $target_id,
        $target_name,
        $message,
        $ip_address
    ]);
}