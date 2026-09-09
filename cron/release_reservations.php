<?php
// cron/release_reservations.php - Release Expired Inventory Reservations
// Run this script every 5 minutes

// Set time limit to unlimited
set_time_limit(0);

// Include database connection
require_once __DIR__ . '/../database/config.php';

// Log file
$logFile = __DIR__ . '/logs/release_reservations.log';
$logDir = __DIR__ . '/logs';

// Create logs directory if it doesn't exist
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

try {
    $pdo = getConnection();
    
    // Count expired reservations before release
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE status = 'reserved' AND reserved_until < NOW()");
    $stmt->execute();
    $expiredCount = $stmt->fetchColumn();
    
    // Release expired reservations
    $stmt = $pdo->prepare("
        UPDATE inventory 
        SET status = 'in_stock', 
            reserved_until = NULL
        WHERE status = 'reserved' 
          AND reserved_until < NOW()
    ");
    $stmt->execute();
    $releasedCount = $stmt->rowCount();
    
    // Log the result
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] Released $releasedCount expired reservation(s). (Found $expiredCount expired)\n";
    file_put_contents($logFile, $message, FILE_APPEND);
    
    echo $message;
    
} catch (Exception $e) {
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] ERROR: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $message, FILE_APPEND);
    echo $message;
}