<?php
// pages/admin/categories.php - Manage Categories

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Check if user is logged in and is HQ Admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../auth/login.php?error=Please log in to access the admin panel.');
    exit;
}

$pdo = getConnection();
$message = '';
$error = '';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $categoryId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $productCount = $stmt->fetchColumn();
        
        if ($productCount > 0) {
            $error = "Cannot delete category. It is assigned to $productCount product(s).";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $message = "Category deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Failed to delete category.";
    }
}

// Get all categories
$stmt = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM product_categories pc WHERE pc.category_id = c.id) AS product_count,
           (SELECT name FROM categories WHERE id = c.parent_id) AS parent_name
    FROM categories c
    ORDER BY c.name
");
$categories = $stmt->fetchAll();

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
                    Categories
                </h1>
                <p style="color: var(--gray-dark); font-size: 18px;">
                    Manage product categories and subcategories.
                </p>
            </div>
            <a href="categories-add.php" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 24px;">
                + ADD CATEGORY
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

        <!-- Categories Table -->
        <?php if (empty($categories)): ?>
            <div style="text-align: center; padding: 60px 0; background: #fff; border-radius: var(--radius); border: 1px solid var(--gray);">
                <p style="font-size: 22px; color: var(--gray-dark);">No categories found.</p>
                <a href="categories-add.php" class="btn btn--green" style="margin-top: 20px;">Add Your First Category</a>
            </div>
        <?php else: ?>
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); overflow: hidden; box-shadow: var(--shadow); overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                    <thead style="background: var(--dark); color: var(--light);">
                        <tr>
                            <th style="padding: 12px 16px; text-align: left; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Name</th>
                            <th style="padding: 12px 16px; text-align: left; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slug</th>
                            <th style="padding: 12px 16px; text-align: left; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Parent</th>
                            <th style="padding: 12px 16px; text-align: center; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Products</th>
                            <th style="padding: 12px 16px; text-align: center; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                            <th style="padding: 12px 16px; text-align: center; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr style="border-bottom: 1px solid var(--gray);">
                                <td style="padding: 12px 16px;">
                                    <strong><?= htmlspecialchars($cat['name']) ?></strong>
                                </td>
                                <td style="padding: 12px 16px; color: var(--gray-dark); font-size: 14px;">
                                    <?= htmlspecialchars($cat['slug']) ?>
                                </td>
                                <td style="padding: 12px 16px; color: var(--gray-dark); font-size: 14px;">
                                    <?= $cat['parent_name'] ? htmlspecialchars($cat['parent_name']) : '—' ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: center; font-weight: 700;">
                                    <?= $cat['product_count'] ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span style="display: inline-block; padding: 2px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; background: <?= $cat['is_active'] ? '#e8f5e9' : '#ffebee' ?>; color: <?= $cat['is_active'] ? '#2e7d32' : '#c62828' ?>;">
                                        <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                                        <a href="categories-edit.php?id=<?= $cat['id'] ?>" 
                                           style="padding: 4px 12px; background: var(--dark); color: var(--light); border-radius: var(--radius); text-decoration: none; font-size: 14px;">
                                            Edit
                                        </a>
                                        <a href="?action=delete&id=<?= $cat['id'] ?>" 
                                           onclick="return confirm('Are you sure you want to delete this category? This will also remove it from all products.');"
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
            <p style="color: var(--gray-dark); font-size: 14px; margin-top: 12px;">
                Total categories: <?= count($categories) ?>
            </p>
        <?php endif; ?>

        <p style="margin-top: 32px;">
            <a href="dashboard.php" style="color: var(--green); font-weight: 700;">&larr; Back to Dashboard</a>
        </p>
        
    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>