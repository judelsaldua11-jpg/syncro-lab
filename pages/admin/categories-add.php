<?php
// pages/admin/categories-add.php - Add Category

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Check if user is logged in and is HQ Admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../auth/login.php?error=Please log in to access the admin panel.');
    exit;
}

$pdo = getConnection();
$error = '';
$success = '';

// Get parent categories for dropdown
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$parentCategories = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $name = ucfirst(strtolower($name));  // Sentence case
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validate
    if (empty($name)) {
        $error = 'Category name is required.';
    } elseif (empty($slug)) {
        // Auto-generate slug from name
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    } else {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $slug), '-'));
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO categories (name, slug, description, parent_id, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $slug, $description ?: null, $parentId ?: null, $isActive]);
            
            $success = "Category added successfully! <a href='categories.php'>View all categories</a>";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $error = "Slug '$slug' already exists. Please use a different name.";
            } else {
                $error = "Failed to add category: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 36px; text-transform: uppercase; margin-bottom: 8px;">
            Add New Category
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Create a new product category or subcategory.
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

        <form method="POST" action="" style="background: #fff; padding: 32px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
            
            <div style="display: grid; gap: 20px;">
                <div>
                    <label for="name" style="font-weight: 700; display: block; margin-bottom: 4px;">Category Name *</label>
                    <input type="text" id="name" name="name" required
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                        This will be displayed to customers (e.g., "Bikes", "Components").
                    </p>
                </div>

                <div>
                    <label for="slug" style="font-weight: 700; display: block; margin-bottom: 4px;">Slug (URL-friendly name)</label>
                    <input type="text" id="slug" name="slug"
                           placeholder="e.g., bikes, road-bikes, shimano-groupsets"
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                        Used in URLs (e.g., shop.php?category=slug). Leave blank to auto-generate from name.
                    </p>
                </div>

                <div>
                    <label for="parent_id" style="font-weight: 700; display: block; margin-bottom: 4px;">Parent Category (Optional)</label>
                    <select id="parent_id" name="parent_id"
                            style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                        <option value="0">None (Top-level category)</option>
                        <?php foreach ($parentCategories as $parent): ?>
                            <option value="<?= $parent['id'] ?>">
                                <?= htmlspecialchars($parent['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                        Select a parent category to create a subcategory.
                    </p>
                </div>

                <div>
                    <label for="description" style="font-weight: 700; display: block; margin-bottom: 4px;">Description (Optional)</label>
                    <textarea id="description" name="description" rows="3"
                              style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; font-family: inherit;"></textarea>
                </div>

                <div>
                    <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active (visible to customers)
                    </label>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        ADD CATEGORY
                    </button>
                    <a href="categories.php" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        CANCEL
                    </a>
                </div>
            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>