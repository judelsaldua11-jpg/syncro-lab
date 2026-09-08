<?php
// pages/warranty-handler.php - Process Warranty Claim

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check login
if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to file a warranty claim.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: warranty.php');
    exit;
}

$userId = $_SESSION['user_id'];
$orderItemId = (int)($_POST['order_item_id'] ?? 0);
$issueDescription = trim($_POST['issue_description'] ?? '');
$refundOption = $_POST['refund_option'] ?? 'repair';

// Validate
if ($orderItemId <= 0 || empty($issueDescription)) {
    header('Location: warranty.php?error=Please fill in all required fields.');
    exit;
}

// Validate refund option
$allowedOptions = ['repair', 'replacement', 'store_credit', 'cash_back'];
if (!in_array($refundOption, $allowedOptions)) {
    header('Location: warranty.php?error=Invalid refund option.');
    exit;
}

// Verify the order item belongs to this user
$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT oi.id 
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.id = ? AND o.user_id = ?
");
$stmt->execute([$orderItemId, $userId]);
if (!$stmt->fetch()) {
    header('Location: warranty.php?error=Invalid product selection.');
    exit;
}

// Insert warranty claim
try {
    $pdo->beginTransaction();

    // Insert claim
    $stmt = $pdo->prepare("
        INSERT INTO warranty_claims (order_item_id, user_id, branch_id, issue_description, refund_option, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    // We need to determine which branch to assign. For now, we'll set branch_id = 0 and later update.
    // We'll get the branch from the order or set to 1.
    $branchId = 1; // Default, you can improve this by getting from order
    $stmt->execute([$orderItemId, $userId, $branchId, $issueDescription, $refundOption]);
    $claimId = $pdo->lastInsertId();

    // Handle file uploads
    $uploadDir = __DIR__ . '/../uploads/warranty/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $uploadedCount = 0;

    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
        $totalFiles = count($_FILES['attachments']['name']);
        if ($totalFiles > 5) {
            throw new Exception('Maximum 5 files allowed.');
        }

        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $fileSize = $_FILES['attachments']['size'][$i];
            if ($fileSize > $maxSize) {
                throw new Exception("File " . $_FILES['attachments']['name'][$i] . " exceeds 5MB limit.");
            }

            $extension = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions)) {
                throw new Exception("File " . $_FILES['attachments']['name'][$i] . " has an invalid file type.");
            }

            // Generate unique filename
            $newName = time() . '_' . uniqid() . '.' . $extension;
            $destPath = $uploadDir . $newName;

            if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $destPath)) {
                // Save to database
                $stmt = $pdo->prepare("
                    INSERT INTO warranty_attachments (warranty_claim_id, file_name, file_path, file_size)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $claimId,
                    $_FILES['attachments']['name'][$i],
                    'uploads/warranty/' . $newName,
                    $fileSize
                ]);
                $uploadedCount++;
            }
        }
    }

    $pdo->commit();

    header('Location: warranty.php?success=Warranty claim submitted successfully! We will review your claim.');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: warranty.php?error=' . urlencode($e->getMessage()));
    exit;
}