<?php
// pages/warranty.php - SYNCRO LAB Warranty & Performance Guarantee Policy

session_start();
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../inc/functions.php';

include __DIR__ . '/../src/Views/layouts/header.php';
?>

<div style="padding: 60px 0 80px; color: var(--dark); min-height: 70vh; background: var(--light);">
    <div style="max-width: 1000px; margin: 0 auto; padding: 0 40px;">
        
        <!-- Header Section -->
        <div style="margin-bottom: 48px; border-bottom: 2px solid var(--gray); padding-bottom: 24px;">
            <p style="color: var(--green); font-family: var(--font-heading); font-size: 14px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;">
                Customer Care & Quality Standards
            </p>
            <h1 style="font-family: var(--font-heading); font-size: 46px; text-transform: uppercase; margin: 0 0 12px; line-height: 1.1;">
                Warranty & Guarantee Policy
            </h1>
            <p style="color: var(--gray-dark); font-size: 18px; margin: 0; line-height: 1.6;">
                Every component curated and bicycle calibrated at SYNCRO LAB is backed by strict engineering tolerances and manufacturer-authorized coverage.
            </p>
        </div>

        <!-- Coverage Highlights Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 48px;">
            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                <span style="font-size: 32px; display: block; margin-bottom: 12px;">🛡️</span>
                <h3 style="font-family: var(--font-heading); font-size: 20px; text-transform: uppercase; margin: 0 0 8px;">
                    Manufacturer Warranty
                </h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6; margin: 0;">
                    100% genuine components sourced via official global distribution channels with full factory warranty protection.
                </p>
            </div>

            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                <span style="font-size: 32px; display: block; margin-bottom: 12px;">⚙️</span>
                <h3 style="font-family: var(--font-heading); font-size: 20px; text-transform: uppercase; margin: 0 0 8px;">
                    1-Year Workshop Labor
                </h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6; margin: 0;">
                    All bespoke frame-up builds and custom builds completed in our labs are warranted against assembly defects for 12 months.
                </p>
            </div>

            <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 24px; box-shadow: var(--shadow);">
                <span style="font-size: 32px; display: block; margin-bottom: 12px;">📍</span>
                <h3 style="font-family: var(--font-heading); font-size: 20px; text-transform: uppercase; margin: 0 0 8px;">
                    Multi-Hub Support
                </h3>
                <p style="color: var(--gray-dark); font-size: 14px; line-height: 1.6; margin: 0;">
                    Need a warranty inspection or retorque? Present your invoice or serial number at any of our active lab hubs nationwide or internationally.
                </p>
            </div>
        </div>

        <!-- Detailed Policy Sections -->
        <div style="background: #fff; border-radius: var(--radius); border: 1px solid var(--gray); padding: 36px; box-shadow: var(--shadow); margin-bottom: 40px;">
            
            <!-- Section 1 -->
            <div style="margin-bottom: 32px;">
                <h2 style="font-family: var(--font-heading); font-size: 22px; text-transform: uppercase; margin-bottom: 12px; color: var(--dark);">
                    1. Scope of Coverage
                </h2>
                <p style="color: var(--gray-dark); line-height: 1.7; font-size: 15px; margin-bottom: 12px;">
                    SYNCRO LAB guarantees that all new hardware, electronic groupsets (Shimano, SRAM), ceramic bearings, wheelsets, and frame units purchased through our website or branch hubs are free from manufacturing defects in material and workmanship.
                </p>
                <ul style="list-style: disc; padding-left: 20px; color: var(--gray-dark); line-height: 1.7; font-size: 15px;">
                    <li><strong>Framesets:</strong> Covered according to the primary manufacturer’s terms (typically 2 to 5 years, or lifetime where registered).</li>
                    <li><strong>Electronic Components & Groupsets:</strong> 2-year warranty against electrical and sensor failure under normal operating parameters.</li>
                    <li><strong>Bearings & Bottom Brackets:</strong> Subject to standard manufacturer ceramic warranty policies.</li>
                    <li><strong>Apparel & Gear:</strong> 90-day coverage against stitching or seam separation.</li>
                </ul>
            </div>

            <!-- Section 2 -->
            <div style="margin-bottom: 32px; border-top: 1px solid #eee; padding-top: 24px;">
                <h2 style="font-family: var(--font-heading); font-size: 22px; text-transform: uppercase; margin-bottom: 12px; color: var(--dark);">
                    2. Exclusions & Limitations
                </h2>
                <p style="color: var(--gray-dark); line-height: 1.7; font-size: 15px; margin-bottom: 12px;">
                    Our warranty ensures technical excellence but does not cover damage resulting from:
                </p>
                <ul style="list-style: disc; padding-left: 20px; color: var(--gray-dark); line-height: 1.7; font-size: 15px;">
                    <li>Normal wear and tear (including chains, brake pads, cassettes, bar tape, and tires).</li>
                    <li>Crashes, impacts, reckless riding, or exceeding recommended torque and weight specifications.</li>
                    <li>Improper home installation or modifications performed outside of authorized SYNCRO LAB technicians.</li>
                    <li>Corrosion or water intrusion caused by high-pressure jet washing or neglected maintenance.</li>
                </ul>
            </div>

            <!-- Section 3 -->
            <div style="border-top: 1px solid #eee; padding-top: 24px;">
                <h2 style="font-family: var(--font-heading); font-size: 22px; text-transform: uppercase; margin-bottom: 12px; color: var(--dark);">
                    3. How to Request Service or Inspection
                </h2>
                <p style="color: var(--gray-dark); line-height: 1.7; font-size: 15px; margin-bottom: 16px;">
                    If you suspect an issue with your equipment, our master mechanics will inspect and diagnose your bicycle using calibrated diagnostic instruments.
                </p>
                <div style="background: var(--light); border-left: 4px solid var(--green); padding: 18px 20px; border-radius: var(--radius);">
                    <p style="font-size: 15px; margin: 0 0 8px; font-weight: 700;">
                        Recommended Procedure:
                    </p>
                    <ol style="padding-left: 20px; margin: 0 0 16px; color: var(--gray-dark); font-size: 14px; line-height: 1.6;">
                        <li>Book a diagnostic appointment via our <a href="booking.php?service=repair_maintenance" style="color: var(--green); font-weight: 700; text-decoration: underline;">Service Booking Form</a>.</li>
                        <li>Bring your bicycle or component to your selected SYNCRO LAB branch alongside your Order ID or serial number receipt.</li>
                        <li>Our certified staff will inspect the unit and coordinate directly with the manufacturer for replacement or repair.</li>
                    </ol>
                    <p style="font-size: 14px; margin: 0; color: var(--gray-dark); line-height: 1.6;">
                        For questions or remote warranty inquiries, you can contact our support team directly via email at <a href="mailto:contact@syncrolab.com" style="color: var(--green); font-weight: 700; text-decoration: underline;">contact@syncrolab.com</a>.
                    </p>
                </div>
            </div>

        </div>

        <!-- Footer Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <a href="../index.php" style="color: var(--green); font-weight: 700; text-decoration: none;">
                &larr; Back to Home
            </a>
            <div style="display: flex; gap: 12px;">
                <a href="booking.php" class="btn btn--green btn--small" style="height: 38px; padding: 0 20px;">
                    Book Workshop Inspection
                </a>
                <a href="about.php" class="btn btn--outline btn--small" style="height: 38px; padding: 0 20px;">
                    About Our Standards
                </a>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../src/Views/layouts/footer.php'; ?>