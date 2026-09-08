<?php
// pages/admin/locations-edit.php - Edit Location

session_start();
require_once __DIR__ . '/../../database/config.php';
require_once __DIR__ . '/../../inc/functions.php';

// Check if user is logged in and is HQ Admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../auth/login.php?error=Please log in to access the admin panel.');
    exit;
}

$locationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($locationId <= 0) {
    header('Location: locations.php?error=Invalid location ID.');
    exit;
}

$pdo = getConnection();

// Get location data
$stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
$stmt->execute([$locationId]);
$location = $stmt->fetch();

if (!$location) {
    header('Location: locations.php?error=Location not found.');
    exit;
}

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

    if (empty($name) || empty($address)) {
        $error = 'Name and address are required.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE branches 
                SET name = ?, address = ?, maps_url = ?, 
                    contact_phone = ?, contact_email = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $address, $mapsUrl ?: null, $contactPhone ?: null, $contactEmail ?: null, $isActive, $locationId]);
            
            $success = "Location updated successfully! <a href='locations.php'>View all locations</a>";
            
            // Refresh location data
            $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
            $stmt->execute([$locationId]);
            $location = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Failed to update location: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 36px; text-transform: uppercase; margin-bottom: 8px;">
            Edit Location
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Update <?= htmlspecialchars($location['name']) ?>
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
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($location['name']) ?>"
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                </div>

                <div>
                    <label for="address" style="font-weight: 700; display: block; margin-bottom: 4px;">Address *</label>
                    <textarea id="address" name="address" rows="3" required
                              style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; font-family: inherit;"><?= htmlspecialchars($location['address']) ?></textarea>
                </div>

                <div>
                    <label for="maps_url" style="font-weight: 700; display: block; margin-bottom: 4px;">Google Maps URL</label>
                    <input type="url" id="maps_url" name="maps_url" value="<?= htmlspecialchars($location['maps_url'] ?? '') ?>"
                           placeholder="https://www.google.com/maps/search/?api=1&query=9.3167+123.3167"
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    <p style="color: var(--gray-dark); font-size: 12px; margin-top: 4px;">
                        Paste the Google Maps link for this location. Used for "SHOW DIRECTIONS" button.
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label for="contact_phone" style="font-weight: 700; display: block; margin-bottom: 4px;">Contact Phone</label>
                        <input type="text" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($location['contact_phone'] ?? '') ?>"
                               style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    </div>
                    <div>
                        <label for="contact_email" style="font-weight: 700; display: block; margin-bottom: 4px;">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($location['contact_email'] ?? '') ?>"
                               style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                    </div>
                </div>

                <div>
                    <label style="font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" <?= $location['is_active'] ? 'checked' : '' ?>>
                        Active (visible to customers)
                    </label>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        UPDATE LOCATION
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