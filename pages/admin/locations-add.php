<?php
// pages/admin/locations-add.php - Add New Location

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $mapsUrl = trim($_POST['maps_url'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validate
    if (empty($name) || empty($address)) {
        $error = 'Name and address are required.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO branches (name, address, maps_url, contact_phone, contact_email, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $address, $mapsUrl ?: null, $contactPhone ?: null, $contactEmail ?: null, $isActive]);
            
            $success = "Location added successfully! <a href='locations.php'>View all locations</a>";
        } catch (PDOException $e) {
            $error = "Failed to add location: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 36px; text-transform: uppercase; margin-bottom: 8px;">
            Add New Location
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Create a new SYNCRO LAB branch location.
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
                    <label for="name" style="font-weight: 700; display: block; margin-bottom: 4px;">Location Name *</label>
                    <input type="text" id="name" name="name" required
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                </div>

                <div>
                    <label for="address" style="font-weight: 700; display: block; margin-bottom: 4px;">Address *</label>
                    <textarea id="address" name="address" rows="3" required
                              style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; font-family: inherit;"></textarea>
                </div>

                <div>
                    <label for="maps_url" style="font-weight: 700; display: block; margin-bottom: 4px;">Google Maps URL</label>
                    <input type="url" id="maps_url" name="maps_url" placeholder="https://www.google.com/maps/search/?api=1&query=9.3167+123.3167"
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                        Paste the Google Maps link for this location. Used for "SHOW DIRECTIONS" button.
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label for="contact_phone" style="font-weight: 700; display: block; margin-bottom: 4px;">Contact Phone</label>
                        <input type="text" id="contact_phone" name="contact_phone"
                               style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    </div>
                    <div>
                        <label for="contact_email" style="font-weight: 700; display: block; margin-bottom: 4px;">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email"
                               style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    </div>
                </div>

                <div>
                    <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active (visible to customers)
                    </label>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        ADD LOCATION
                    </button>
                    <a href="locations.php" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        CANCEL
                    </a>
                </div>
            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/../../src/Views/layouts/footer.php'; ?>