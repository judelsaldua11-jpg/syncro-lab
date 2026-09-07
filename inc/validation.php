<?php
// inc/validation.php - Form Validation for SYNCRO LAB

// ============================================
// BASE VALIDATION FUNCTIONS (Keep from original)
// ============================================

function validateEmailFormat(string $value): ?string
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "Enter a valid email address.";
}

function validateRequired(string $value, string $label): ?string
{
    return trim($value) === '' ? "$label is required." : null;
}

function validateIntRange(string $value, string $label, int $min, int $max): ?string
{
    $ok = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max],
    ]);
    return $ok !== false ? null : "$label must be a whole number between $min and $max.";
}

// ============================================
// NEW: SYNCRO LAB VALIDATION FUNCTIONS
// ============================================

function validatePhone(string $value): ?string
{
    $cleaned = preg_replace('/[^0-9+]/', '', $value);
    if (empty($cleaned) || strlen($cleaned) < 10) {
        return "Please enter a valid phone number (minimum 10 digits).";
    }
    return null;
}

function validatePassword(string $value): ?string
{
    if (strlen($value) < 8) {
        return "Password must be at least 8 characters long.";
    }
    return null;
}

function validateConfirmPassword(string $password, string $confirm): ?string
{
    return $password === $confirm ? null : "Passwords do not match.";
}

function validateDate(string $value, string $format = 'Y-m-d'): ?string
{
    $date = DateTime::createFromFormat($format, $value);
    return ($date && $date->format($format) === $value) ? null : "Please enter a valid date.";
}

function validateFutureDate(string $value): ?string
{
    $error = validateDate($value);
    if ($error) return $error;
    
    $date = new DateTime($value);
    $now = new DateTime();
    if ($date < $now) {
        return "Date must be in the future.";
    }
    return null;
}

function validatePositiveNumber(string $value, string $label): ?string
{
    if (!is_numeric($value) || $value <= 0) {
        return "$label must be a positive number.";
    }
    return null;
}

function validateFileUpload(array $file, int $maxSize = 5, array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "File upload failed. Please try again.";
    }
    
    $fileSize = $file['size'] / 1024 / 1024; // Convert to MB
    if ($fileSize > $maxSize) {
        return "File is too large. Maximum allowed size is {$maxSize}MB.";
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return "File type not allowed. Allowed types: " . implode(', ', $allowedTypes);
    }
    
    return null;
}

// ============================================
// FORM-SPECIFIC VALIDATION FUNCTIONS
// ============================================

function validateUserRegistration(array $post): array
{
    $fullName = trim($post['full_name'] ?? '');
    $email = trim($post['email'] ?? '');
    $password = $post['password'] ?? '';
    $confirmPassword = $post['confirm_password'] ?? '';
    $phone = trim($post['phone'] ?? '');
    
    $errors = array_filter([
        validateRequired($fullName, 'Full Name'),
        validateRequired($email, 'Email'),
        validateEmailFormat($email),
        validateRequired($password, 'Password'),
        validatePassword($password),
        validateConfirmPassword($password, $confirmPassword),
        $phone ? validatePhone($phone) : null,
    ]);
    
    $errors = array_values($errors);
    
    if (empty($errors)) {
        $fullName = htmlspecialchars($fullName);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $phone = $phone ? preg_replace('/[^0-9+]/', '', $phone) : null;
    }
    
    return [
        'errors' => $errors,
        'data' => [
            'full_name' => $fullName,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
        ]
    ];
}

function validateUserLogin(array $post): array
{
    $email = trim($post['email'] ?? '');
    $password = $post['password'] ?? '';
    
    $errors = array_filter([
        validateRequired($email, 'Email'),
        validateRequired($password, 'Password'),
    ]);
    
    $errors = array_values($errors);
    
    return [
        'errors' => $errors,
        'data' => [
            'email' => $email,
            'password' => $password,
        ]
    ];
}

function validateBookingInput(array $post): array
{
    $branchId = trim($post['branch_id'] ?? '');
    $serviceType = trim($post['service_type'] ?? '');
    $scheduledDate = trim($post['scheduled_date'] ?? '');
    $notes = trim($post['notes'] ?? '');
    
    $errors = array_filter([
        validateRequired($branchId, 'Branch'),
        validateIntRange($branchId, 'Branch', 1, 999),
        validateRequired($serviceType, 'Service Type'),
        validateRequired($scheduledDate, 'Scheduled Date'),
        validateFutureDate($scheduledDate),
    ]);
    
    $errors = array_values($errors);
    
    return [
        'errors' => $errors,
        'data' => [
            'branch_id' => (int) $branchId,
            'service_type' => htmlspecialchars($serviceType),
            'scheduled_date' => $scheduledDate,
            'notes' => htmlspecialchars($notes),
        ]
    ];
}

function validateWarrantyInput(array $post, array $files): array
{
    $orderItemId = trim($post['order_item_id'] ?? '');
    $issueDescription = trim($post['issue_description'] ?? '');
    $refundOption = trim($post['refund_option'] ?? 'repair');
    
    $allowedRefundOptions = ['repair', 'replacement', 'store_credit', 'cash_back'];
    
    $errors = array_filter([
        validateRequired($orderItemId, 'Order Item'),
        validateIntRange($orderItemId, 'Order Item', 1, 999999),
        validateRequired($issueDescription, 'Issue Description'),
        in_array($refundOption, $allowedRefundOptions) ? null : "Invalid refund option.",
    ]);
    
    // Validate file uploads (max 5 files)
    $fileErrors = [];
    $uploadedFiles = [];
    $fileCount = 0;
    
    foreach ($files as $key => $file) {
        if (strpos($key, 'attachment_') === 0 && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileCount++;
            $error = validateFileUpload($file, 5);
            if ($error) {
                $fileErrors[] = $error;
            } else {
                $uploadedFiles[$key] = $file;
            }
        }
    }
    
    if ($fileCount > 5) {
        $fileErrors[] = "Maximum 5 files allowed.";
    }
    
    $errors = array_merge($errors, $fileErrors);
    $errors = array_values(array_filter($errors));
    
    return [
        'errors' => $errors,
        'data' => [
            'order_item_id' => (int) $orderItemId,
            'issue_description' => htmlspecialchars($issueDescription),
            'refund_option' => $refundOption,
            'files' => $uploadedFiles,
        ]
    ];
}

function validateCheckoutInput(array $post): array
{
    $shippingAddress = trim($post['shipping_address'] ?? '');
    $paymentMethod = trim($post['payment_method'] ?? '');
    $branchId = trim($post['branch_id'] ?? '');
    
    $allowedPaymentMethods = ['credit_card', 'cash_on_delivery', 'gcash', 'paymaya'];
    
    $errors = array_filter([
        validateRequired($shippingAddress, 'Shipping Address'),
        validateRequired($paymentMethod, 'Payment Method'),
        !in_array($paymentMethod, $allowedPaymentMethods) ? "Invalid payment method." : null,
        validateRequired($branchId, 'Branch'),
        validateIntRange($branchId, 'Branch', 1, 999),
    ]);
    
    $errors = array_values($errors);
    
    return [
        'errors' => $errors,
        'data' => [
            'shipping_address' => htmlspecialchars($shippingAddress),
            'payment_method' => $paymentMethod,
            'branch_id' => (int) $branchId,
        ]
    ];
}

function validateProductInput(array $post): array
{
    $name = trim($post['name'] ?? '');
    $sku = trim($post['sku'] ?? '');
    $price = trim($post['price'] ?? '');
    $description = trim($post['description'] ?? '');
    $categoryIds = $post['category_ids'] ?? [];
    
    $errors = array_filter([
        validateRequired($name, 'Product Name'),
        validateRequired($sku, 'SKU'),
        validateRequired($price, 'Price'),
        validatePositiveNumber($price, 'Price'),
        empty($categoryIds) ? "Select at least one category." : null,
    ]);
    
    $errors = array_values($errors);
    
    return [
        'errors' => $errors,
        'data' => [
            'name' => htmlspecialchars($name),
            'sku' => htmlspecialchars($sku),
            'price' => (float) $price,
            'description' => htmlspecialchars($description),
            'category_ids' => array_map('intval', (array) $categoryIds),
        ]
    ];
}