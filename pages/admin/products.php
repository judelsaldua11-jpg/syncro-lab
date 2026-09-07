<?php
// pages/admin/products.php - HQ Admin Product Management

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Check if user is logged in and is HQ Admin
if (!isLoggedIn()) {
    header('Location: ../auth/login.php?error=Please log in to access the admin panel.');
    exit;
}

if (!isAdmin()) {
    header('Location: ../../index.php?error=You do not have permission to access this page.');
    exit;
}

// Handle product actions
$action = $_GET['action'] ?? '';
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

$pdo = getConnection();

// Handle toggle featured
if ($action === 'toggle_featured' && $productId > 0) {
    try {
        // Get current featured status
        $stmt = $pdo->prepare("SELECT is_featured FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if ($product) {
            $newStatus = $product['is_featured'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE products SET is_featured = ? WHERE id = ?");
            $stmt->execute([$newStatus, $productId]);
            $message = "Product featured status updated successfully!";
        }
    } catch (PDOException $e) {
        $error = "Failed to update product status.";
    }
}

// Handle delete product
if ($action === 'delete' && $productId > 0) {
    try {
        // Check if product has orders
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM order_items oi 
            JOIN inventory i ON oi.inventory_id = i.id 
            WHERE i.product_id = ?
        ");
        $stmt->execute([$productId]);
        $orderCount = $stmt->fetchColumn();
        
        if ($orderCount > 0) {
            $error = "Cannot delete product. It has been ordered by customers.";
        } else {
            // Delete product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $message = "Product deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Failed to delete product.";
    }
}

// Get all products
$stmt = $pdo->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM inventory WHERE product_id = p.id AND status = 'in_stock') AS total_stock,
           (SELECT file_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image
    FROM products p
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <div>
                <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
                    Product Management
                </h1>
                <p style="color: var(--gray-dark); font-size: 18px;">
                    Manage products and featured items on the homepage.
                </p>
            </div>
            <a href="products-add.php" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 24px;">
                + ADD NEW PRODUCT
            </a>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid var(--green);">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Products Table -->
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 60px 0; background: #fff; border-radius: var(--radius); border: 1px solid var(--gray);">
                <p style="font-size: 22px; color: var(--gray-dark);">No products found.</p>
                <a href="products-add.php" class="btn btn--green" style="margin-top: 20px;">Add Your First Product</a>
            </div>
        <?php else: ?>
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); overflow: hidden; box-shadow: var(--shadow);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: var(--dark); color: var(--light);">
                        <tr>
                            <th style="padding: 16px 20px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Image</th>
                            <th style="padding: 16px 20px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Product</th>
                            <th style="padding: 16px 20px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">SKU</th>
                            <th style="padding: 16px 20px; text-align: center; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Stock</th>
                            <th style="padding: 16px 20px; text-align: center; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Featured</th>
                            <th style="padding: 16px 20px; text-align: center; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr style="border-bottom: 1px solid var(--gray);">
                                <td style="padding: 12px 20px; width: 60px;">
                                    <img src="<?= !empty($product['image']) ? '../../' . htmlspecialchars($product['image']) : '../../assets/images/placeholder.png' ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius);">
                                </td>
                                <td style="padding: 12px 20px;">
                                    <strong><?= htmlspecialchars($product['name']) ?></strong>
                                    <br>
                                    <span style="color: var(--gray-dark); font-size: 14px;"><?= htmlspecialchars($product['brand'] ?? '') ?></span>
                                </td>
                                <td style="padding: 12px 20px; color: var(--gray-dark); font-size: 14px;">
                                    <?= htmlspecialchars($product['sku']) ?>
                                </td>
                                <td style="padding: 12px 20px; text-align: center;">
                                    <span style="color: <?= $product['total_stock'] > 0 ? 'var(--green)' : '#d9534f' ?>; font-weight: 700;">
                                        <?= (int) $product['total_stock'] ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 20px; text-align: center;">
                                    <a href="?action=toggle_featured&id=<?= $product['id'] ?>" 
                                       style="display: inline-block; padding: 4px 12px; border-radius: 20px; text-decoration: none; font-size: 12px; font-weight: 700; background: <?= $product['is_featured'] ? 'var(--green)' : 'var(--gray)' ?>; color: <?= $product['is_featured'] ? 'var(--dark)' : '#fff' ?>;">
                                        <?= $product['is_featured'] ? '★ FEATURED' : '☆ NOT FEATURED' ?>
                                    </a>
                                </td>
                                <td style="padding: 12px 20px; text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <a href="products-edit.php?id=<?= $product['id'] ?>" 
                                           style="padding: 4px 12px; background: var(--dark); color: var(--light); border-radius: var(--radius); text-decoration: none; font-size: 14px;">
                                            Edit
                                        </a>
                                        <a href="?action=delete&id=<?= $product['id'] ?>" 
                                           onclick="return confirm('Are you sure you want to delete this product?');"
                                           style="padding: 4px 12px; background: #d9534f; color: #fff; border-radius: var(--radius); text-decoration: none; font-size: 14px;">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Back to Dashboard -->
        <p style="margin-top: 32px;">
            <a href="dashboard.php" style="color: var(--green); font-weight: 700;">&larr; Back to Dashboard</a>
        </p>
        
    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>