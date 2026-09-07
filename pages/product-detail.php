<?php
// pages/product-detail.php - SYNCRO LAB Product Detail

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: shop.php');
    exit;
}

$pdo = getConnection();

// Get product details
$sql = "SELECT p.*, 
               (SELECT COUNT(*) FROM inventory WHERE product_id = p.id AND status = 'in_stock') AS total_stock,
               (SELECT COUNT(*) FROM inventory WHERE product_id = p.id AND status = 'sold') AS total_sold,
               (SELECT file_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image
        FROM products p
        WHERE p.id = :id AND p.is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Get product categories
$stmt = $pdo->prepare("
    SELECT c.id, c.name 
    FROM categories c 
    JOIN product_categories pc ON c.id = pc.category_id 
    WHERE pc.product_id = :product_id
");
$stmt->execute([':product_id' => $productId]);
$categories = $stmt->fetchAll();

// Get product images gallery
$stmt = $pdo->prepare("
    SELECT file_path, is_primary 
    FROM product_images 
    WHERE product_id = :product_id 
    ORDER BY is_primary DESC, sort_order ASC
");
$stmt->execute([':product_id' => $productId]);
$images = $stmt->fetchAll();

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 40px;">

        <!-- Breadcrumb -->
        <p style="color: var(--gray-dark); font-size: 14px; margin-bottom: 24px;">
            <a href="shop.php" style="color: var(--green);">Shop</a> / <?= htmlspecialchars($product['name']) ?>
        </p>

        <!-- Product Detail Row -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px;">
            
            <!-- Left: Images -->
            <div>
                <!-- Main Image -->
                <div style="background: #fff; border: 1px solid var(--gray); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow);">
                    <img src="<?= !empty($product['image']) ? '../' . htmlspecialchars($product['image']) : '../assets/images/placeholder.png' ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         style="width: 100%; height: auto; max-height: 500px; object-fit: contain;">
                </div>
                
                <!-- Thumbnails (if more images exist) -->
                <?php if (count($images) > 1): ?>
                    <div style="display: flex; gap: 12px; margin-top: 16px;">
                        <?php foreach ($images as $img): ?>
                            <img src="../<?= htmlspecialchars($img['file_path']) ?>" 
                                 style="width: 80px; height: 80px; object-fit: cover; border: <?= $img['is_primary'] ? '2px solid var(--green)' : '2px solid var(--gray)' ?>; border-radius: var(--radius); cursor: pointer;">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Product Info -->
            <div>
                <h1 style="font-family: var(--font-heading); font-size: 36px; text-transform: uppercase; margin-bottom: 8px;">
                    <?= htmlspecialchars($product['name']) ?>
                </h1>
                <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 8px;">
                    <?= htmlspecialchars($product['brand'] ?? '') ?>
                </p>
                <p style="font-family: var(--font-heading); font-size: 32px; color: var(--green);">
                    ₱ <?= number_format($product['price'], 2) ?>
                </p>
                
                <!-- Stock Status -->
                <div style="margin: 16px 0; padding: 12px; background: <?= $product['total_stock'] > 0 ? '#e8f5e9' : '#ffebee' ?>; border-radius: var(--radius);">
                    <p style="color: <?= $product['total_stock'] > 0 ? '#2e7d32' : '#c62828' ?>; font-weight: 700; font-size: 16px;">
                        <?php if ($product['total_stock'] > 0): ?>
                            ✅ IN STOCK — <?= $product['total_stock'] ?> units available
                        <?php else: ?>
                            ❌ OUT OF STOCK
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Categories -->
                <?php if (!empty($categories)): ?>
                    <p style="color: var(--gray-dark); font-size: 14px; margin-bottom: 16px;">
                        <strong>Categories:</strong> 
                        <?php foreach ($categories as $i => $cat): ?>
                            <?php if ($i > 0) echo ', '; ?>
                            <a href="shop.php?category=<?= $cat['id'] ?>" style="color: var(--green);"><?= htmlspecialchars($cat['name']) ?></a>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>

                <!-- SKU -->
                <p style="color: var(--gray-dark); font-size: 14px; margin-bottom: 16px;">
                    <strong>SKU:</strong> <?= htmlspecialchars($product['sku']) ?>
                </p>

                <!-- Description -->
                <?php if ($product['description']): ?>
                    <div style="margin: 24px 0; padding-top: 24px; border-top: 1px solid var(--gray);">
                        <h3 style="font-family: var(--font-heading); font-size: 18px; text-transform: uppercase; margin-bottom: 8px;">Description</h3>
                        <p style="color: var(--gray-dark); font-size: 16px; line-height: 1.6;"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Add to Cart -->
                <?php if ($product['total_stock'] > 0): ?>
                    <form method="POST" action="cart.php?action=add" style="display: flex; gap: 12px; align-items: center; margin-top: 24px;">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <label for="qty" style="font-weight: 700;">Qty:</label>
                        <input type="number" id="qty" name="quantity" value="1" min="1" max="<?= $product['total_stock'] ?>" 
                            style="width: 70px; height: 48px; padding: 0 8px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; text-align: center;">
                        <button type="submit" class="btn btn--green" style="height: 48px; font-size: 18px; padding: 0 32px;">
                            ADD TO CART
                        </button>
                    </form>
                <?php else: ?>
                    <button disabled style="width: 100%; height: 48px; background: var(--gray); color: #fff; border: none; border-radius: var(--radius); font-size: 18px; font-weight: 700; cursor: not-allowed; margin-top: 24px;">
                        OUT OF STOCK
                    </button>
                <?php endif; ?>

                <!-- Back to Shop -->
                <p style="margin-top: 32px;">
                    <a href="shop.php" style="color: var(--green); font-weight: 700;">&larr; Back to Shop</a>
                </p>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>