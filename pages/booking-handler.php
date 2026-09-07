<?php
// pages/booking-handler.php - Process Service Booking

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check login
if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to book a service.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php');
    exit;
}

$userId = $_SESSION['user_id'];
$branchId = (int)($_POST['branch_id'] ?? 0);
$serviceType = $_POST['service_type'] ?? '';
$scheduledDate = $_POST['scheduled_date'] ?? '';
$notes = trim($_POST['notes'] ?? '');

// Validate
if ($branchId <= 0 || empty($serviceType) || empty($scheduledDate)) {
    header('Location: booking.php?error=Please fill in all required fields.');
    exit;
}

// Validate service type
$allowedServices = ['custom_build', 'repair_maintenance', 'bike_fit'];
if (!in_array($serviceType, $allowedServices)) {
    header('Location: booking.php?error=Invalid service type.');
    exit;
}

// Validate date (must be in the future)
$now = new DateTime();
$date = DateTime::createFromFormat('Y-m-d\TH:i', $scheduledDate);
if (!$date || $date < $now) {
    header('Location: booking.php?error=Scheduled date must be in the future.');
    exit;
}

$pdo = getConnection();

// Insert booking
try {
    $stmt = $pdo->prepare("
        INSERT INTO service_bookings (user_id, branch_id, service_type, scheduled_date, notes, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$userId, $branchId, $serviceType, $scheduledDate, $notes]);
    $bookingId = $pdo->lastInsertId();

    header('Location: booking.php?success=Booking submitted successfully! We will confirm your appointment shortly.');
    exit;
} catch (PDOException $e) {
    header('Location: booking.php?error=Failed to submit booking. Please try again.');
    exit;
}