<?php
// cron/run-all.php - Run all cron jobs

echo "=== SYNCRO LAB Cron Jobs ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Run release reservations
echo "--- Running release_reservations.php ---\n";
include __DIR__ . '/release_reservations.php';
echo "\n";

// Run membership check
echo "--- Running check_membership.php ---\n";
include __DIR__ . '/check_membership.php';
echo "\n";

echo "=== All jobs completed ===\n";
echo "Finished at: " . date('Y-m-d H:i:s') . "\n";