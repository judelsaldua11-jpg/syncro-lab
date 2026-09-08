<?php
// pages/service-detail.php - Service Detail Page

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Get the service type from URL
$service = isset($_GET['service']) ? $_GET['service'] : '';

// Define all services data
$services = [
    'custom_build' => [
        'id' => 'custom_build',
        'title' => 'CUSTOM BUILD',
        'tagline' => 'Frame-up bespoke assembly, electronic groupsets, and custom telemetry tuning.',
        'description' => 'Our custom build service transforms your vision into reality. We start with a frame of your choice and meticulously hand-pick every component to match your riding style, body geometry, and performance goals.',
        'image' => '/syncro lab/assets/images/custom-build-detail.jpg',
        'fallback_image' => '/syncro lab/assets/images/custom-build.png',
        'time' => '24 - 48 hours',
        'price' => 'Starting at ₱ 15,000',
        'cta' => 'BOOK CUSTOM BUILD',
        'features' => [
            'Frame consultation and selection',
            'Groupset installation (Shimano, SRAM, Campagnolo)',
            'Custom telemetry tuning',
            'Full build documentation',
            'Post-build fit adjustment',
            '1-year warranty on labor'
        ],
        'booking_service' => 'custom_build'
    ],
    'repair_maintenance' => [
        'id' => 'repair_maintenance',
        'title' => 'REPAIR & MAINTENANCE',
        'tagline' => 'Complete ultrasonic cleaning, brake bleeds, bearing check and torque calibration.',
        'description' => 'Our comprehensive repair and maintenance service keeps your bike performing at its peak. We use state-of-the-art equipment and certified technicians to ensure every component is in perfect working order.',
        'image' => '/syncro lab/assets/images/repair-maintenance-detail.jpg',
        'fallback_image' => '/syncro lab/assets/images/repair-maintenance.png',
        'time' => '2 - 4 hours',
        'price' => 'Starting at ₱ 2,500',
        'cta' => 'BOOK REPAIR',
        'features' => [
            'Full ultrasonic drivetrain cleaning',
            'Complete brake system bleed and adjustment',
            'Bearing inspection and replacement',
            'Torque calibration on all bolts',
            'Gear indexing and tuning',
            'Safety inspection report'
        ],
        'booking_service' => 'repair_maintenance'
    ],
    'bike_fit' => [
        'id' => 'bike_fit',
        'title' => 'BIKE FIT',
        'tagline' => 'Motion analysis, cockpit alignment, and power-transfer telemetry adjustments.',
        'description' => 'Our professional bike fit service uses advanced motion analysis and telemetry to optimize your position on the bike. We analyze your biomechanics to maximize power transfer, comfort, and efficiency while reducing the risk of injury.',
        'image' => '/syncro lab/assets/images/bike-fit-detail.jpg',
        'fallback_image' => '/syncro lab/assets/images/bike-fit.png',
        'time' => '1 - 2 hours',
        'price' => 'Starting at ₱ 3,500',
        'cta' => 'BOOK BIKE FIT',
        'features' => [
            'Comprehensive motion analysis',
            'Cockpit alignment (saddle, handlebars, cleats)',
            'Power-transfer telemetry adjustments',
            'Body geometry assessment',
            'Custom fitting report',
            'Follow-up adjustment session'
        ],
        'booking_service' => 'bike_fit'
    ]
];

// Check if service exists
if (!isset($services[$service])) {
    header('Location: /syncro lab/index.php#services');
    exit;
}

$serviceData = $services[$service];

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 40px 0 60px; color: var(--dark); min-height: 60vh; background: var(--light);">
    <div style="max-width: 1100px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Breadcrumb -->
        <p style="color: var(--gray-dark); font-size: 14px; margin-bottom: 24px;">
            <a href="/syncro lab/index.php#services" style="color: var(--green);">Services</a> / <?= $serviceData['title'] ?>
        </p>

        <!-- Hero Section -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px; margin-bottom: 48px;">
            
            <!-- Left: Image -->
            <div style="background: var(--light); border-radius: var(--radius); border: 1px solid var(--gray); box-shadow: var(--shadow); overflow: hidden; height: 300px; display: flex; align-items: center; justify-content: center;">
                <img src="<?= $serviceData['image'] ?>" 
                    alt="<?= $serviceData['title'] ?>"
                    onerror="this.src='<?= $serviceData['fallback_image'] ?>'"
                    style="width: 100%; height: 100%; object-fit: cover; display: block; flex-shrink: 0;">
            </div>

            <!-- Right: Info -->
            <div style="display: flex; flex-direction: column; justify-content: center;">
                <p style="color: var(--gray-dark); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                    SYNCRO LAB SERVICE
                </p>
                <h1 style="font-family: var(--font-heading); font-size: 48px; text-transform: uppercase; margin-bottom: 12px;">
                    <?= $serviceData['title'] ?>
                </h1>
                <p style="font-size: 20px; color: var(--gray-dark); margin-bottom: 16px;">
                    <?= $serviceData['tagline'] ?>
                </p>
                
                <div style="display: flex; gap: 32px; margin-bottom: 24px;">
                    <div>
                        <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Estimated Time</p>
                        <p style="font-weight: 700; font-size: 18px;"><?= $serviceData['time'] ?></p>
                    </div>
                    <div>
                        <p style="color: var(--gray-dark); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Price</p>
                        <p style="font-weight: 700; font-size: 18px; color: var(--green);"><?= $serviceData['price'] ?></p>
                    </div>
                </div>

                <a href="/syncro lab/pages/booking.php?service=<?= $serviceData['booking_service'] ?>" 
                   class="btn btn--green" style="align-self: flex-start;">
                    <?= $serviceData['cta'] ?> →
                </a>
            </div>
        </div>

        <!-- Description & Features -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
            
            <!-- Description -->
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 32px; box-shadow: var(--shadow);">
                <h2 style="font-family: var(--font-heading); font-size: 24px; text-transform: uppercase; margin-bottom: 16px;">
                    About This Service
                </h2>
                <p style="color: var(--gray-dark); font-size: 18px; line-height: 1.8;">
                    <?= $serviceData['description'] ?>
                </p>
            </div>

            <!-- Features -->
            <div style="background: var(--dark); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow); align-self: start;">
                <h2 style="font-family: var(--font-heading); font-size: 20px; text-transform: uppercase; margin-bottom: 16px; color: var(--light);">
                    What's Included
                </h2>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($serviceData['features'] as $feature): ?>
                        <li style="padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--gray); display: flex; align-items: center; gap: 12px;">
                            <span style="color: var(--green); font-size: 18px;">✓</span>
                            <?= htmlspecialchars($feature) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Back to Services -->
        <p style="margin-top: 40px;">
            <a href="/syncro lab/index.php#services" style="color: var(--green); font-weight: 700;">&larr; Back to Services</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>