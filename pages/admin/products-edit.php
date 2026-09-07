<?php
// pages/admin/products-edit.php - Edit Product

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Check if user is logged in and is HQ Admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../auth/login.php?error=Please log in to access the admin panel.');
    exit;
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: products.php?error=Invalid product ID.');
    exit;
}

$pdo = getConnection();

// Get product data
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php?error=Product not found.');
    exit;
}

// Get product categories
$stmt = $pdo->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
$stmt->execute([$productId]);
$productCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get all categories
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryIds = $_POST['categories'] ?? [];
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($name) || empty($sku) || empty($price)) {
        $error = 'Product name, SKU, and price are required.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = 'Price must be a positive number.';
    } else {
        try {
            $pdo->beginTransaction();

            // Update product
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, sku = ?, brand = ?, price = ?, description = ?, is_featured = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $sku, $brand, $price, $description, $isFeatured, $productId]);

            // Update categories (delete old, insert new)
            $stmt = $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?");
            $stmt->execute([$productId]);

            if (!empty($categoryIds)) {
                $stmt = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
                foreach ($categoryIds as $categoryId) {
                    $stmt->execute([$productId, $categoryId]);
                }
            }

            $pdo->commit();
            $success = "Product updated successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $error = "SKU already exists. Please use a unique SKU.";
            } else {
                $error = "Failed to update product: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Page Header -->
        <h1 style="font-family: var(--font-heading); font-size: 36px; text-transform: uppercase; margin-bottom: 8px;">
            Edit Product
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Update product details for <?= htmlspecialchars($product['name']) ?>
        </p>

        <!-- Messages -->
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

        <!-- Form -->
        <form method="POST" action="" style="background: #fff; padding: 32px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
            
            <div style="display: grid; gap: 20px;">
                <div>
                    <label for="name" style="font-weight: 700; display: block; margin-bottom: 4px;">Product Name *</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($product['name']) ?>"
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                </div>

                <div>
                    <label for="sku" style="font-weight: 700; display: block; margin-bottom: 4px;">SKU *</label>
                    <input type="text" id="sku" name="sku" required value="<?= htmlspecialchars($product['sku']) ?>"
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">Unique product identifier</p>
                </div>

                <div>
                    <label for="brand" style="font-weight: 700; display: block; margin-bottom: 4px;">Brand</label>
                    <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($product['brand'] ?? '') ?>"
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                </div>

                <div>
                    <label for="price" style="font-weight: 700; display: block; margin-bottom: 4px;">Price *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0.01" required value="<?= $product['price'] ?>"
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                </div>

                <div>
                    <label for="description" style="font-weight: 700; display: block; margin-bottom: 4px;">Description</label>
                    <textarea id="description" name="description" rows="4"
                              style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; font-family: inherit;"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>

                <div>
                    <label style="font-weight: 700; display: block; margin-bottom: 8px;">Categories</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($categories as $cat): ?>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 14px; padding: 4px 12px; background: var(--light); border-radius: var(--radius); border: 1px solid var(--gray); cursor: pointer;">
                                <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                                    <?= in_array($cat['id'], $productCategories) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
                        Show on homepage (Featured Hardware)
                    </label>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        UPDATE PRODUCT
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