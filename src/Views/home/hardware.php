<?php
// src/Views/home/hardware.php - Featured Hardware (Dynamic)

// Get featured products from database
$stmt = $pdo->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM inventory WHERE product_id = p.id AND status = 'in_stock') AS total_stock,
           (SELECT file_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image
    FROM products p
    WHERE p.is_active = 1 
      AND p.is_phase_out = 0
      AND p.is_featured = 1
    ORDER BY p.id ASC       -- This matches the mockup order
    LIMIT 4
");
$featuredProducts = $stmt->fetchAll();

// Map product IDs to their original bracket tags
$badgeMap = [
    1 => '[FEATURED BUILD]',
    2 => '[NEW ARRIVAL COMPONENTS]',
    3 => '[RIDER GEAR]',
    4 => '[TELEMETRY TECH]'
];
?>

<!-- SECTION 5: FEATURED HARDWARE & BUILDS -->
<section id="catalog" class="hardware-section" aria-label="Featured Hardware and Builds">
    <div class="hardware-container">
        <h2 class="section-title" id="featured-hardware-heading">FEATURED HARDWARE & BUILDS</h2>
        <div class="hardware-grid" role="region" aria-labelledby="featured-hardware-heading">
            
            <?php if (empty($featuredProducts)): ?>
                <p style="color: var(--dark); grid-column: 1 / -1; text-align: center; padding: 40px 0;">
                    No featured products available. Check the shop for our full selection!
                </p>
            <?php else: ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <a href="pages/product-detail.php?id=<?= $product['id'] ?>" 
                       style="text-decoration: none; color: inherit; display: block;">
                        <article class="hardware-card" aria-label="<?= htmlspecialchars($product['name']) ?> Product Card">
                            <div class="card-header">
                                <span class="category-tag" aria-label="Category">
                                    <?= $badgeMap[$product['id']] ?? '[FEATURED]' ?>
                                </span>
                                <span class="stock-tag" aria-label="Stock Status">
                                    IN STOCK: <?= (int) $product['total_stock'] ?>
                                </span>
                            </div>
                            <div class="hardware-image">
                                <img src="<?= !empty($product['image']) ? htmlspecialchars($product['image']) : 'assets/images/placeholder.png' ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>">
                            </div>
                            <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                        </article>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
    </div>
</section>