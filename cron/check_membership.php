<?php
// cron/check_membership.php - Check Expired Memberships
// Run this script daily

set_time_limit(0);

require_once __DIR__ . '/../database/config.php';

// Log file
$logFile = __DIR__ . '/logs/membership_check.log';
$logDir = __DIR__ . '/logs';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

try {
    $pdo = getConnection();
    
    // Find users with expired membership (expired date is in the past and still active)
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, membership_expiry 
        FROM users 
        WHERE membership_expiry IS NOT NULL 
          AND membership_expiry < CURDATE() 
          AND is_active = 1
    ");
    $stmt->execute();
    $expiredUsers = $stmt->fetchAll();
    
    $timestamp = date('Y-m-d H:i:s');
    
    if (empty($expiredUsers)) {
        $message = "[$timestamp] No expired memberships found.\n";
        echo $message;
        file_put_contents($logFile, $message, FILE_APPEND);
    } else {
        // Log each expired user
        $message = "[$timestamp] Found " . count($expiredUsers) . " expired membership(s):\n";
        foreach ($expiredUsers as $user) {
            $message .= "  - ID: {$user['id']}, Name: {$user['full_name']}, Email: {$user['email']}, Expired: {$user['membership_expiry']}\n";
        }
        $message .= "\n";
        echo $message;
        file_put_contents($logFile, $message, FILE_APPEND);
        
        // Optional: Send email notifications (future enhancement)
        // For now, we just log it
    }
    
} catch (Exception $e) {
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] ERROR: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $message, FILE_APPEND);
    echo $message;
}