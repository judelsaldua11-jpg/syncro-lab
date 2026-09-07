<?php
// inc/functions.php - Helper Functions for SYNCRO LAB

require_once __DIR__ . '/../database/config.php';

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getUserRole() {
    $user = getCurrentUser();
    return $user ? $user['role'] : null;
}

function isAdmin() {
    return getUserRole() === 'hq_admin';
}

function isBranchManager() {
    return getUserRole() === 'branch_manager';
}

function isCustomer() {
    return getUserRole() === 'customer' || !isLoggedIn();
}

// ============================================
// URL & REDIRECT FUNCTIONS
// ============================================

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function redirectWithMessage($url, $status, $message) {
    header("Location: $url?status=$status&message=" . urlencode($message));
    exit;
}

// ============================================
// DATA SANITIZATION
// ============================================

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function sanitizeArray($array) {
    return array_map('sanitizeInput', $array);
}

// ============================================
// PASSWORD FUNCTIONS
// ============================================

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ============================================
// MEMBERSHIP FUNCTIONS
// ============================================

function isMembershipActive($userId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT membership_expiry FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    if (!$result || !$result['membership_expiry']) {
        return false;
    }
    
    return strtotime($result['membership_expiry']) > time();
}

function getMembershipDiscount() {
    return 0.10; // 10% discount for members
}

// ============================================
// CART FUNCTIONS
// ============================================

function getCartCount() {
    if (isset($_SESSION['user_id'])) {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE cart_id = (SELECT id FROM cart WHERE user_id = ?)");
        $stmt->execute([$_SESSION['user_id']]);
        return (int) $stmt->fetchColumn();
    }
    return 0;
}

// ============================================
// BRANCH FUNCTIONS
// ============================================

function getBranches() {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY name");
    return $stmt->fetchAll();
}

function getBranchById($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ============================================
// HAVERSINE DISTANCE CALCULATION
// ============================================

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // kilometers
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c; // Distance in km
}

// ============================================
// ORDER FUNCTIONS
// ============================================

function getOrderStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'returned' => 'secondary'
    ];
    return $badges[$status] ?? 'secondary';
}

function getOrderStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned'
    ];
    return $labels[$status] ?? ucfirst($status);
}