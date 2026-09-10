<?php
// pages/profile-handler.php - Handles Profile Info, Password, and Preseted Addresses CRUD
session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/validation.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php?error=' . urlencode('Please log in first.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$pdo = getConnection();
$userId = $_SESSION['user_id'];
$action = trim($_POST['action'] ?? '');

// ----------------------------------------------------
// ACTION 1: UPDATE PERSONAL INFORMATION
// ----------------------------------------------------
if ($action === 'update_info') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $errors = [];
    if (empty($fullName)) {
        $errors[] = "Full Name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    if (!empty($phone)) {
        $phoneError = validatePhone($phone);
        if ($phoneError) {
            $errors[] = $phoneError;
        }
    }

    // Check email uniqueness if modified
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            $errors[] = "This email address is already registered to another account.";
        }
    }

    if (!empty($errors)) {
        header('Location: profile.php?error=' . urlencode(implode(' ', $errors)) . '&tab=info');
        exit;
    }

    try {
        $cleanedPhone = !empty($phone) ? preg_replace('/[^0-9+]/', '', $phone) : null;
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$fullName, $email, $cleanedPhone, $userId]);

        // Keep session data updated in sync
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;

        header('Location: profile.php?success=' . urlencode('Personal information updated successfully.') . '&tab=info');
        exit;
    } catch (PDOException $e) {
        header('Location: profile.php?error=' . urlencode('Failed to update profile. Please try again.') . '&tab=info');
        exit;
    }
}

// ----------------------------------------------------
// ACTION 2: CHANGE PASSWORD
// ----------------------------------------------------
elseif ($action === 'update_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (empty($currentPassword)) {
        $errors[] = "Please enter your current password.";
    }
    if (empty($newPassword)) {
        $errors[] = "New password is required.";
    } else {
        $passErr = validatePassword($newPassword);
        if ($passErr) {
            $errors[] = $passErr;
        }
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = "New passwords do not match.";
    }

    if (!empty($errors)) {
        header('Location: profile.php?error=' . urlencode(implode(' ', $errors)) . '&tab=security');
        exit;
    }

    // Verify current password against database hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !verifyPassword($currentPassword, $user['password_hash'])) {
        header('Location: profile.php?error=' . urlencode('Current password is incorrect.') . '&tab=security');
        exit;
    }

    try {
        $newHash = hashPassword($newPassword);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newHash, $userId]);

        header('Location: profile.php?success=' . urlencode('Password updated successfully.') . '&tab=security');
        exit;
    } catch (PDOException $e) {
        header('Location: profile.php?error=' . urlencode('Failed to change password. Please try again.') . '&tab=security');
        exit;
    }
}

// ----------------------------------------------------
// ACTION 3: ADD NEW ADDRESS
// ----------------------------------------------------
elseif ($action === 'add_address') {
    $result = validateAddressInput($_POST);
    if (!empty($result['errors'])) {
        header('Location: profile.php?error=' . urlencode(implode(' ', $result['errors'])) . '&tab=addresses');
        exit;
    }

    $data = $result['data'];

    try {
        $pdo->beginTransaction();

        // Check if this is the user's first address; if so, make it default automatically
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
        $countStmt->execute([$userId]);
        $existingCount = (int)$countStmt->fetchColumn();

        $isDefault = ($existingCount === 0 || $data['is_default'] == 1) ? 1 : 0;

        // If newly set as default, reset other addresses to 0
        if ($isDefault === 1) {
            $resetStmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $resetStmt->execute([$userId]);
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO user_addresses (user_id, label, recipient_name, phone, address_line, is_default)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
            $userId,
            $data['label'],
            $data['recipient_name'],
            $data['phone'],
            $data['address_line'],
            $isDefault
        ]);

        $pdo->commit();
        header('Location: profile.php?success=' . urlencode('Address added successfully.') . '&tab=addresses');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Location: profile.php?error=' . urlencode('Failed to save address: ' . $e->getMessage()) . '&tab=addresses');
        exit;
    }
}

// ----------------------------------------------------
// ACTION 4: EDIT / UPDATE ADDRESS
// ----------------------------------------------------
elseif ($action === 'edit_address') {
    $addressId = (int)($_POST['address_id'] ?? 0);
    $existing = getUserAddressById($addressId, $userId);

    if (!$existing) {
        header('Location: profile.php?error=' . urlencode('Address not found.') . '&tab=addresses');
        exit;
    }

    $result = validateAddressInput($_POST);
    if (!empty($result['errors'])) {
        header('Location: profile.php?error=' . urlencode(implode(' ', $result['errors'])) . '&tab=addresses');
        exit;
    }

    $data = $result['data'];

    try {
        $pdo->beginTransaction();

        $isDefault = $data['is_default'] == 1 ? 1 : 0;

        // If setting this one as default, unset all others
        if ($isDefault === 1) {
            $resetStmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $resetStmt->execute([$userId]);
        } else if ($existing['is_default'] == 1) {
            // If it was default and user unticked, verify if another default is needed or keep it as default if it's the only one
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            $countStmt->execute([$userId]);
            if ((int)$countStmt->fetchColumn() <= 1) {
                $isDefault = 1; // Always keep the sole address as default
            }
        }

        $updateStmt = $pdo->prepare("
            UPDATE user_addresses
            SET label = ?, recipient_name = ?, phone = ?, address_line = ?, is_default = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $updateStmt->execute([
            $data['label'],
            $data['recipient_name'],
            $data['phone'],
            $data['address_line'],
            $isDefault,
            $addressId,
            $userId
        ]);

        $pdo->commit();
        header('Location: profile.php?success=' . urlencode('Address updated successfully.') . '&tab=addresses');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Location: profile.php?error=' . urlencode('Failed to update address.') . '&tab=addresses');
        exit;
    }
}

// ----------------------------------------------------
// ACTION 5: SET DEFAULT ADDRESS
// ----------------------------------------------------
elseif ($action === 'set_default_address') {
    $addressId = (int)($_POST['address_id'] ?? 0);
    $existing = getUserAddressById($addressId, $userId);

    if (!$existing) {
        header('Location: profile.php?error=' . urlencode('Address not found.') . '&tab=addresses');
        exit;
    }

    try {
        $pdo->beginTransaction();

        $resetStmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $resetStmt->execute([$userId]);

        $setStmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $setStmt->execute([$addressId, $userId]);

        $pdo->commit();
        header('Location: profile.php?success=' . urlencode('Default delivery address updated.') . '&tab=addresses');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Location: profile.php?error=' . urlencode('Could not set default address.') . '&tab=addresses');
        exit;
    }
}

// ----------------------------------------------------
// ACTION 6: DELETE ADDRESS
// ----------------------------------------------------
elseif ($action === 'delete_address') {
    $addressId = (int)($_POST['address_id'] ?? 0);
    $existing = getUserAddressById($addressId, $userId);

    if (!$existing) {
        header('Location: profile.php?error=' . urlencode('Address not found.') . '&tab=addresses');
        exit;
    }

    try {
        $pdo->beginTransaction();

        $wasDefault = (int)$existing['is_default'] === 1;

        $delStmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $delStmt->execute([$addressId, $userId]);

        // If the deleted address was the default, make the most recent remaining address default
        if ($wasDefault) {
            $nextStmt = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $nextStmt->execute([$userId]);
            $nextId = $nextStmt->fetchColumn();
            if ($nextId) {
                $setStmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ?");
                $setStmt->execute([$nextId]);
            }
        }

        $pdo->commit();
        header('Location: profile.php?success=' . urlencode('Address deleted successfully.') . '&tab=addresses');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Location: profile.php?error=' . urlencode('Failed to delete address.') . '&tab=addresses');
        exit;
    }
}

// Unknown action fallback
header('Location: profile.php');
exit;
