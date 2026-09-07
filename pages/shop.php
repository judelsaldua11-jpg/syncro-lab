<?php
// pages/shop.php - SYNCRO LAB Product Catalog

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$pdo = getConnection();

// Build the query
$sql = "SELECT p.*, 
               (SELECT COUNT(*) FROM inventory WHERE product_id = p.id AND status = 'in_stock') AS total_stock,
               (SELECT file_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image
        FROM products p
        WHERE p.is_active = 1 AND p.is_phase_out = 0";

$countSql = "SELECT COUNT(*) FROM products p WHERE p.is_active = 1 AND p.is_phase_out = 0";
$params = [];

// Add search filter
if (!empty($search)) {
    $sql .= " AND (p.name LIKE :search OR p.brand LIKE :search OR p.sku LIKE :search)";
    $countSql .= " AND (p.name LIKE :search OR p.brand LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = "%$search%";
}

// Add category filter
if ($category > 0) {
    $sql .= " AND EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_id = :category)";
    $countSql .= " AND EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_id = :category)";
    $params[':category'] = $category;
}

// Add sorting and pagination
$sql .= " ORDER BY p.id DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

// Get total count for pagination
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    if ($key !== ':limit' && $key !== ':offset') {
        $countStmt->bindValue($key, $value);
    }
}
$countStmt->execute();
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Get products
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    if ($key === ':limit' || $key === ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$products = $stmt->fetchAll();

// Get all categories for filter dropdown
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Page Header -->
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px; color: var(--dark);">
            Shop
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Browse our premium bike components and gear.
        </p>

        <!-- Search & Filter Bar -->
        <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 32px;">
            <form method="GET" action="" style="flex: 1; display: flex; gap: 12px; min-width: 280px;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search products..." 
                       style="flex: 1; height: 48px; padding: 0 16px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 24px;">SEARCH</button>
            </form>
            <form method="GET" action="" style="display: flex; gap: 12px;">
                <select name="category" style="height: 48px; padding: 0 16px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 20px;">FILTER</button>
                <?php if (!empty($search) || $category > 0): ?>
                    <a href="shop.php" class="btn btn--dark" style="height: 48px; font-size: 16px; padding: 0 20px;">CLEAR</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Product Count -->
        <p style="color: var(--gray-dark); font-size: 14px; margin-bottom: 24px;">
            Showing <?= count($products) ?> of <?= $totalProducts ?> products
        </p>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 60px 0;">
                <p style="font-size: 22px; color: var(--gray-dark);">No products found.</p>
                <a href="shop.php" class="btn btn--green" style="margin-top: 20px;">View All Products</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
                <?php foreach ($products as $product): ?>
                    <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow); display: flex; flex-direction: column;">
                        
                        <!-- Product Image -->
                        <a href="product-detail.php?id=<?= $product['id'] ?>" style="display: block; height: 200px; overflow: hidden; border-radius: var(--radius); margin-bottom: 12px;">
                            <img src="<?= !empty($product['image']) ? '../' . htmlspecialchars($product['image']) : '../assets/images/placeholder.png' ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </a>
                        
                        <!-- Product Info -->
                        <a href="product-detail.php?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit; flex: 1;">
                            <h3 style="font-family: var(--font-heading); font-size: 20px; text-transform: uppercase; margin-bottom: 4px;">
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
                            <p style="color: var(--gray-dark); font-size: 14px; margin-bottom: 8px;">
                                <?= htmlspecialchars($product['brand'] ?? '') ?>
                            </p>
                            <p style="font-family: var(--font-heading); font-size: 24px; color: var(--dark);">
                                ₱ <?= number_format($product['price'], 2) ?>
                            </p>
                            <p style="color: <?= $product['total_stock'] > 0 ? 'var(--green)' : '#d9534f' ?>; font-size: 14px; font-weight: 700;">
                                <?= $product['total_stock'] > 0 ? 'IN STOCK: ' . $product['total_stock'] : 'OUT OF STOCK' ?>
                            </p>
                        </a>
                        
                        <!-- Add to Cart Form -->
                        <?php if ($product['total_stock'] > 0): ?>
                            <form method="POST" action="cart.php?action=add" style="margin-top: 12px;">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn--green" style="width: 100%; height: 44px; font-size: 16px; padding: 0;">
                                    ADD TO CART
                                </button>
                            </form>
                        <?php else: ?>
                            <button disabled style="width: 100%; height: 44px; font-size: 16px; background: var(--gray); color: #fff; border: none; border-radius: var(--radius); cursor: not-allowed; margin-top: 12px;">
                                OUT OF STOCK
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 40px;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>" 
                           style="display: inline-block; padding: 8px 16px; border-radius: var(--radius); background: <?= $i == $page ? 'var(--green)' : 'var(--gray)' ?>; color: <?= $i == $page ? 'var(--dark)' : '#fff' ?>; text-decoration: none; font-weight: 700;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>