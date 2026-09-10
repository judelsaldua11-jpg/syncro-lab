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

function getOrderStatusColor($status) {  // ← ADDED (was missing)
    $colors = [
        'pending' => '#f0ad4e',    // yellow
        'confirmed' => '#5bc0de',   // blue
        'shipped' => '#0275d8',     // dark blue
        'delivered' => '#5cb85c',   // green
        'cancelled' => '#d9534f',   // red
        'returned' => '#777777'     // gray
    ];
    return $colors[$status] ?? '#777777';
}

// ============================================
// CART FUNCTIONS
// ============================================

function getOrCreateCart($pdo, $userId = null) {
    $sessionId = session_id();
    
    if ($userId) {
        // Check if user has a cart
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();
        if ($cart) {
            return $cart['id'];
        }
        // Create new cart for user
        $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        return $pdo->lastInsertId();
    } else {
        // Guest cart using session_id
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $cart = $stmt->fetch();
        if ($cart) {
            return $cart['id'];
        }
        // Create new cart for session
        $stmt = $pdo->prepare("INSERT INTO cart (session_id) VALUES (?)");
        $stmt->execute([$sessionId]);
        return $pdo->lastInsertId();
    }
}

function addToCart($pdo, $cartId, $productId, $quantity = 1) {
    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cartId, $productId]);
    $item = $stmt->fetch();
    
    if ($item) {
        // Update quantity
        $newQty = $item['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQty, $item['id']]);
        return true;
    } else {
        // Insert new item
        $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$cartId, $productId, $quantity]);
        return true;
    }
}

function removeFromCart($pdo, $cartId, $productId) {
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cartId, $productId]);
    return true;
}

function updateCartQuantity($pdo, $cartId, $productId, $quantity) {
    if ($quantity <= 0) {
        return removeFromCart($pdo, $cartId, $productId);
    }
    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$quantity, $cartId, $productId]);
    return true;
}

function getCartItems($pdo, $cartId) {
    $stmt = $pdo->prepare("
        SELECT 
            ci.id AS cart_item_id,
            ci.product_id,
            ci.quantity,
            p.name AS product_name,
            p.sku,
            p.price,
            p.brand,
            (SELECT file_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image,
            (SELECT COUNT(*) FROM inventory WHERE product_id = p.id AND status = 'in_stock') AS total_stock
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cartId]);
    return $stmt->fetchAll();
}

function getCartTotal($pdo, $cartId) {
    $stmt = $pdo->prepare("
        SELECT SUM(ci.quantity * p.price) AS total
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cartId]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function getCartCount($pdo, $cartId) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) AS count FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);
    $result = $stmt->fetch();
    return (int)($result['count'] ?? 0);
}

function clearCart($pdo, $cartId) {
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);
    return true;
}


// ============================================
// RESERVATION FUNCTIONS
// ============================================

function reserveInventory($pdo, $productId, $branchId, $quantity) {
    $stmt = $pdo->prepare("
        UPDATE inventory 
        SET status = 'reserved', 
            reserved_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
        WHERE product_id = ? 
          AND branch_id = ? 
          AND status = 'in_stock'
        LIMIT ?
    ");
    $stmt->execute([$productId, $branchId, $quantity]);
    return $stmt->rowCount();
}

function releaseExpiredReservations($pdo) {
    $stmt = $pdo->prepare("
        UPDATE inventory 
        SET status = 'in_stock', 
            reserved_until = NULL
        WHERE status = 'reserved' 
          AND reserved_until < NOW()
    ");
    $stmt->execute();
    return $stmt->rowCount();
}

// ============================================
// INVENTORY LOGGING
// ============================================

function logInventoryActivity($pdo, $inventoryId, $productId, $branchId, $action, $oldStatus = null, $newStatus = null, $quantity = null, $notes = null) {
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) return;
    
    $stmt = $pdo->prepare("
        INSERT INTO inventory_logs (inventory_id, product_id, branch_id, user_id, action, old_status, new_status, quantity, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $inventoryId ?: null,
        $productId,
        $branchId,
        $userId,
        $action,
        $oldStatus,
        $newStatus,
        $quantity,
        $notes
    ]);
}

// ============================================
// USER ADDRESS FUNCTIONS
// ============================================

function getUserAddresses($userId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUserAddressById($addressId, $userId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$addressId, $userId]);
    return $stmt->fetch();
}

function getDefaultUserAddress($userId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
    $stmt->execute([$userId]);
    $address = $stmt->fetch();
    if (!$address) {
        // Fallback to most recent address if no default is explicitly marked
        $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $address = $stmt->fetch();
    }
    return $address ?: null;
}