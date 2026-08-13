<?php
/**
 * Helper pencatatan activity log.
 */

function get_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function log_activity(mysqli $connection, int $userId, string $activity): void
{
    $ipAddress = get_client_ip();
    $sql = 'INSERT INTO activity_logs (user_id, activity, ip_address) VALUES (?, ?, ?)';
    $statement = mysqli_prepare($connection, $sql);

    if (!$statement) {
        return;
    }

    mysqli_stmt_bind_param($statement, 'iss', $userId, $activity, $ipAddress);
    mysqli_stmt_execute($statement);
    mysqli_stmt_close($statement);
}
