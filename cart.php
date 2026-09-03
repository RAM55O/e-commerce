<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $code = trim($_POST['coupon_code'] ?? '');
    $coupon = $code ? find_coupon($conn, $code) : null;
    if (!$coupon) {
        header("Location: cart.php?error=" . urlencode('Invalid coupon code'));
        exit();
    }
    $_SESSION['coupon_code'] = $coupon['code'];
    header("Location: cart.php?success=" . urlencode('Coupon applied'));
    exit();
}

if (isset($_GET['remove_coupon'])) {
    unset($_SESSION['coupon_code']);
    header("Location: cart.php?success=" . urlencode('Coupon removed'));
    exit();
}

$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image, p.discount FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($cart_items as &$item) {
    $item['unit_price'] = sale_price($item);
    $item['line_total'] = $item['unit_price'] * (int)$item['quantity'];
    $subtotal += $item['line_total'];
}
unset($item);

$coupon_discount = 0;
$coupon_message = '';
$coupon_row = null;
if (!empty($_SESSION['coupon_code'])) {
    $coupon_row = find_coupon($conn, $_SESSION['coupon_code']);
    if ($coupon_row) {
        $applied = apply_coupon($coupon_row, $subtotal);
        if ($applied['ok']) {
            $coupon_discount = $applied['discount'];
            $coupon_message = $applied['message'];
        } else {
            $coupon_message = $applied['message'];
        }
    } else {
        unset($_SESSION['coupon_code']);
    }
}

$after_coupon = max(0, $subtotal - $coupon_discount);
$shipping = $after_coupon > 100 ? 0 : 25;
$total = $after_coupon + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1 class="logo">Moonchild</h1>
            <form class="search-bar" action="search.php" method="GET">
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Search products...">
            </form>
        </div>
    </header>

    <div class="cart-page-container">
        <h2 class="cart-page-title">Your Shopping Cart</h2>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if (count($cart_items) > 0): ?>
            <div class="cart-items-list">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item-card">
                    <div class="cart-item-img">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                    </div>
                    <div class="cart-item-info">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <p class="cart-item-price">
                            <?php if ((int)$item['discount'] > 0): ?>
                            <span class="original-price">₹<?php echo number_format($item['price'], 2); ?></span>
                            ₹<?php echo number_format($item['unit_price'], 2); ?>
                            <?php else: ?>
                            ₹<?php echo number_format($item['unit_price'], 2); ?>
                            <?php endif; ?>
                        </p>
                        <form action="cart_action.php" method="POST" class="cart-qty-form">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="action" value="update">
                            <div class="cart-qty-controls">
                                <button type="button" class="cart-qty-btn" onclick="updateCartQty(this, -1)">−</button>
                                <input type="number" name="quantity" class="cart-qty-input" value="<?php echo $item['quantity']; ?>" min="1">
                                <button type="button" class="cart-qty-btn" onclick="updateCartQty(this, 1)">+</button>
                            </div>
                        </form>
                    </div>
                    <div class="cart-item-actions-col">
                        <form action="cart_action.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="cart-action-btn delete"><i class="fas fa-trash"></i></button>
                        </form>
                        <form action="wishlist_action.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="cart-action-btn wishlist"><i class="fas fa-heart"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="order-summary-card">
                <h3>Order Summary</h3>
                <form method="POST" class="coupon-form">
                    <input type="text" name="coupon_code" placeholder="Coupon code" value="<?php echo htmlspecialchars($_SESSION['coupon_code'] ?? ''); ?>">
                    <button type="submit" name="apply_coupon" value="1">Apply</button>
                    <?php if (!empty($_SESSION['coupon_code'])): ?>
                    <a href="cart.php?remove_coupon=1">Remove</a>
                    <?php endif; ?>
                </form>
                <?php if ($coupon_message): ?>
                <p class="coupon-note"><?php echo htmlspecialchars($coupon_message); ?></p>
                <?php endif; ?>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <?php if ($coupon_discount > 0): ?>
                <div class="summary-row">
                    <span>Coupon</span>
                    <span>-₹<?php echo number_format($coupon_discount, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span><?php echo $shipping == 0 ? 'Free' : '₹' . number_format($shipping, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($total, 2); ?></span>
                </div>
                <a href="checkout.php" class="checkout-btn" style="display:block;text-align:center;">Proceed to Checkout</a>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Browse our products and add items to your cart!</p>
                <a href="home.php" class="btn-primary" style="display: inline-block; margin-top: 20px;">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <?php $nav_active = 'cart'; include 'includes/user_nav.php'; ?>
    <script>
        function updateCartQty(btn, delta) {
            const form = btn.closest('form');
            const input = form.querySelector('input[name="quantity"]');
            let val = parseInt(input.value) + delta;
            if (val < 1) val = 1;
            input.value = val;
            form.submit();
        }
    </script>
    <script src="script.js?v=2.1"></script>
</body>
</html>
