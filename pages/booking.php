<?php
// pages/booking.php - Service Booking Form

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: auth/login.php?error=Please log in to book a service.');
    exit;
}

$pdo = getConnection();
$userId = $_SESSION['user_id'];

// Get all branches for dropdown
$branches = getBranches();

// Get any error/success messages
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 60px 0; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 40px;">
        
        <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 8px;">
            Book a Service
        </h1>
        <p style="color: var(--gray-dark); font-size: 18px; margin-bottom: 32px;">
            Schedule your custom build, repair, or bike fit at your preferred branch.
        </p>

        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid #d32f2f;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 16px; border-radius: var(--radius); margin-bottom: 24px; border-left: 4px solid var(--green);">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="booking-handler.php" style="background: #fff; padding: 32px; border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow);">
            
            <div style="display: grid; gap: 20px;">
                
                <div>
                    <label for="branch_id" style="font-weight: 700; display: block; margin-bottom: 4px;">Branch *</label>
                    <select id="branch_id" name="branch_id" required
                            style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                        <option value="">-- Select Branch --</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch['id'] ?>">
                                <?= htmlspecialchars($branch['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="service_type" style="font-weight: 700; display: block; margin-bottom: 4px;">Service Type *</label>
                    <select id="service_type" name="service_type" required
                            style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; background: #fff;">
                        <option value="">-- Select Service --</option>
                        <option value="custom_build">Custom Build</option>
                        <option value="repair_maintenance">Repair & Maintenance</option>
                        <option value="bike_fit">Bike Fit</option>
                    </select>
                </div>

                <div>
                    <label for="scheduled_date" style="font-weight: 700; display: block; margin-bottom: 4px;">Scheduled Date & Time *</label>
                    <input type="datetime-local" id="scheduled_date" name="scheduled_date" required
                           style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px;">
                </div>

                <div>
                    <label for="notes" style="font-weight: 700; display: block; margin-bottom: 4px;">Additional Notes</label>
                    <textarea id="notes" name="notes" rows="4"
                              style="width: 100%; padding: 12px; border: 2px solid var(--gray); border-radius: var(--radius); font-size: 16px; font-family: inherit;"
                              placeholder="Any special requests or details about your bike..."></textarea>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <button type="submit" class="btn btn--green" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        SUBMIT BOOKING
                    </button>
                    <a href="../index.php" class="btn btn--outline" style="height: 48px; font-size: 16px; padding: 0 32px;">
                        CANCEL
                    </a>
                </div>
            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>