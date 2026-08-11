<?php
require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../shared/billing_sync.php';

salvex_sync_billing_status($conn);

echo 'Billing status sync completed.' . PHP_EOL;

