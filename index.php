<?php
// index.php - Homepage Assembler

session_start();
require_once 'database/config.php';
require_once 'inc/functions.php';

// Get PDO connection (always needed for the homepage)
$pdo = getConnection();

// Get cart count (if logged in)
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT SUM(ci.quantity) AS count 
        FROM cart_items ci 
        JOIN cart c ON ci.cart_id = c.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = (int) $stmt->fetchColumn();
}

// Define base URL for assets
$baseUrl = '';

include 'src/Views/layouts/header.php';
include 'src/Views/home/hero.php';
include 'src/Views/home/services.php';
include 'src/Views/home/hardware.php';
include 'src/Views/home/about.php';
include 'src/Views/home/locations.php';
include 'src/Views/layouts/footer.php';
?>