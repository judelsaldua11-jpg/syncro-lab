<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | SYNCRO LAB</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .cart-page {
            min-height: calc(100vh - 108px);
            padding: 80px 24px;
            background-color: var(--light-bg);
            color: var(--dark-bg);
        }

        .cart-page-container {
            width: min(1100px, 100%);
            margin: 0 auto;
        }

        .cart-page-title {
            margin-bottom: 36px;
            font-family: var(--font-heading);
            font-size: clamp(36px, 6vw, 64px);
            text-transform: uppercase;
        }

        .cart-page-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
        }

        .cart-page-panel {
            padding: 32px;
            border: 2px solid var(--gray-muted);
            border-radius: 4px;
            background-color: #fff;
        }

        .cart-page-panel h2 {
            margin-bottom: 24px;
            font-family: var(--font-heading);
            font-size: 24px;
        }

        .cart-page-empty {
            padding: 56px 24px;
            border-top: 1px solid var(--gray-muted);
            color: #68747b;
            text-align: center;
            line-height: 1.5;
        }

        .cart-page-empty strong {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-bg);
            font-family: var(--font-heading);
            font-size: 22px;
        }

        .cart-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
            color: #68747b;
        }

        .cart-summary-total {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-muted);
            color: var(--dark-bg);
            font-weight: 700;
        }

        .cart-page-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .cart-page-link {
            color: var(--dark-bg);
            font-weight: 700;
            text-decoration: none;
        }

        .cart-page-link:hover {
            color: #759616;
        }

        @media (max-width: 760px) {
            .cart-page {
                padding: 48px 16px;
            }

            .cart-page-layout {
                grid-template-columns: 1fr;
            }

            .cart-page-panel {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <main class="cart-page">
        <div class="cart-page-container">
            <h1 class="cart-page-title">Your Cart</h1>
            <div class="cart-page-layout">
                <section class="cart-page-panel" aria-labelledby="cart-items-title">
                    <h2 id="cart-items-title">Cart Items</h2>
                    <div class="cart-page-empty">
                        <strong>Your cart is empty</strong>
                        Browse the catalog to add precision components, rider gear, or services.
                    </div>
                    <div class="cart-page-actions">
                        <a href="index.php#catalog" class="btn-register">CONTINUE SHOPPING</a>
                    </div>
                </section>
                <aside class="cart-page-panel" aria-labelledby="cart-summary-title">
                    <h2 id="cart-summary-title">Order Summary</h2>
                    <div class="cart-summary-row"><span>Items</span><span>0</span></div>
                    <div class="cart-summary-row"><span>Shipping</span><span>Calculated at checkout</span></div>
                    <div class="cart-summary-row cart-summary-total"><span>Subtotal</span><span>PHP 0.00</span></div>
                </aside>
            </div>
            <p style="margin-top: 28px;"><a class="cart-page-link" href="index.php">&#8592; Back to SYNCRO LAB</a></p>
        </div>
    </main>
</body>
</html>