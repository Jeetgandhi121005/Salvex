<?php

function salvex_sync_billing_status(mysqli $conn): void
{
    static $alreadyRun = false;

    if ($alreadyRun) {
        return;
    }

    $alreadyRun = true;

    $sql = "
        UPDATE billing b
        INNER JOIN appointments a ON a.id = b.appointment_id
        SET
            b.status = 'Paid',
            b.paid_at = COALESCE(b.paid_at, DATE_ADD(a.created_at, INTERVAL 4 HOUR))
        WHERE b.status = 'Unpaid'
          AND a.created_at <= DATE_SUB(NOW(), INTERVAL 4 HOUR)
    ";

    mysqli_query($conn, $sql);
}

