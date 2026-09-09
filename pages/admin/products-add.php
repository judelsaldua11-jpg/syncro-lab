<?php
// pages/admin/products-add.php - Add New Product

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Check if user is logged in and is HQ Admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../auth/login.php?error=Please log in to access the admin panel.');
    exit;
}

$error = '';
$success = '';
$pdo = getConnection();

// Get all categories for dropdown
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryIds = $_POST['categories'] ?? [];
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    // Validate
    if (empty($name) || empty($sku) || empty($price)) {
        $error = 'Product name, SKU, and price are required.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = 'Price must be a positive number.';
    } else {
        try {
            $pdo->beginTransaction();

            // Insert product (without image - handled separately)
            $stmt = $pdo->prepare("
                INSERT INTO products (name, sku, brand, price, description, is_featured) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $sku, $brand, $price, $description, $isFeatured]);
            $productId = $pdo->lastInsertId();

            // Insert product categories
            if (!empty($categoryIds)) {
                $stmt = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
                foreach ($categoryIds as $categoryId) {
                    $stmt->execute([$productId, $categoryId]);
                }
            }

            // ============================================
            // HANDLE IMAGE UPLOAD
            // ============================================
            $imageError = null;
            $uploadedImages = 0;

            // Check if a file was uploaded
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['product_image'];
                    $maxSize = 5 * 1024 * 1024; // 5MB
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                    // Validate file size
                    if ($file['size'] > $maxSize) {
                        $imageError = 'Image file is too large. Maximum size is 5MB.';
                    } else {
                        // Validate MIME type
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);

                        if (!in_array($mimeType, $allowedTypes)) {
                            $imageError = 'Invalid file type. Allowed: JPG, PNG, WebP, GIF.';
                        } else {
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            if (!in_array($ext, $allowedExts)) {
                                $imageError = 'Invalid file extension. Allowed: jpg, jpeg, png, webp, gif.';
                            } else {
                                // Generate unique filename
                                $newName = uniqid('prod_') . '.' . $ext;
                                $uploadDir = __DIR__ . '/../../assets/images/products/';
                                if (!is_dir($uploadDir)) {
                                    mkdir($uploadDir, 0755, true);
                                }
                                $dest = $uploadDir . $newName;

                                if (move_uploaded_file($file['tmp_name'], $dest)) {
                                    $filePath = '/assets/images/products/' . $newName;

                                    // Insert into product_images table
                                    $stmt = $pdo->prepare("
                                        INSERT INTO product_images (product_id, file_path, is_primary, sort_order, uploaded_at) 
                                        VALUES (?, ?, ?, ?, NOW())
                                    ");
                                    $stmt->execute([$productId, $filePath, 1, 0]);
                                    $uploadedImages++;
                                } else {
                                    $imageError = 'Failed to move uploaded file.';
                                }
                            }
                        }
                    }
                } else {
                    $imageError = 'File upload error. Please try again.';
                }
            }

            // If image upload failed, but product was created, we can still commit
            // Just warn the user about the image issue
            if ($imageError) {
                // Roll back because image upload failed
                $pdo->rollBack();
                $error = $imageError;
            } else {
                $pdo->commit();
                $success = "Product added successfully! " . ($uploadedImages > 0 ? "Image uploaded. " : "No image uploaded. ") . "<a href='products.php'>View all products</a>";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $error = "SKU already exists. Please use a unique SKU.";
            } else {
                $error = "Failed to add product: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 36px; text-transform: uppercase; margin-bottom: 8px;">
            Add New Product
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Create a new product for the catalog.
        </p>

        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid var(--green);">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" style="background: #fff; padding: 32px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
            
            <div style="display: grid; gap: 20px;">
                <div>
                    <label for="name" style="font-weight: 700; display: block; margin-bottom: 4px;">Product Name *</label>
                    <input type="text" id="name" name="name" required
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                </div>

                <div>
                    <label for="sku" style="font-weight: 700; display: block; margin-bottom: 4px;">SKU *</label>
                    <input type="text" id="sku" name="sku" required
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">Unique product identifier (e.g., SHIM-DA-9200)</p>
                </div>

                <div>
                    <label for="brand" style="font-weight: 700; display: block; margin-bottom: 4px;">Brand</label>
                    <input type="text" id="brand" name="brand"
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                </div>

                <div>
                    <label for="price" style="font-weight: 700; display: block; margin-bottom: 4px;">Price *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0.01" required
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                </div>

                <div>
                    <label for="description" style="font-weight: 700; display: block; margin-bottom: 4px;">Description</label>
                    <textarea id="description" name="description" rows="4"
                              style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; font-family: inherit;"></textarea>
                </div>

                <!-- Image Upload -->
                <div>
                    <label for="product_image" style="font-weight: 700; display: block; margin-bottom: 4px;">Product Image</label>
                    <input type="file" id="product_image" name="product_image" accept="image/jpeg,image/png,image/webp,image/gif"
                           style="width: 100%; padding: 10px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                    <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">Supported: JPG, PNG, WebP, GIF (max 5MB)</p>
                </div>

                <div>
                    <label style="font-weight: 700; display: block; margin-bottom: 8px;">Categories</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($categories as $cat): ?>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 14px; padding: 4px 12px; background: var(--light); border-radius: var(--radius); border: 1px solid var(--gray); cursor: pointer;">
                                <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_featured" value="1">
                        Show on homepage (Featured Hardware)
                    </label>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        ADD PRODUCT
                    </button>
                    <a href="products.php" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        CANCEL
                    </a>
                </div>
            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>