<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image, p.stock, p.discount FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($cart_items) == 0) {
    header("Location: cart.php?error=Your cart is empty");
    exit();
}

$subtotal = 0;
foreach ($cart_items as &$item) {
    $item['unit_price'] = sale_price($item);
    $item['line_total'] = $item['unit_price'] * (int)$item['quantity'];
    $subtotal += $item['line_total'];
}
unset($item);

$coupon_discount = 0;
$coupon_code = null;
$coupon_message = '';
if (!empty($_SESSION['coupon_code'])) {
    $coupon_row = find_coupon($conn, $_SESSION['coupon_code']);
    if ($coupon_row) {
        $applied = apply_coupon($coupon_row, $subtotal);
        if ($applied['ok']) {
            $coupon_discount = $applied['discount'];
            $coupon_code = $coupon_row['code'];
            $coupon_message = $applied['message'];
        } else {
            $coupon_message = $applied['message'];
        }
    }
}

$after_coupon = max(0, $subtotal - $coupon_discount);
$shipping = $after_coupon > 100 ? 0 : 25;
$total = $after_coupon + $shipping;

$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $payment = $_POST['payment_method'] ?? 'COD';

    if ($address === '' || $city === '' || $zip === '' || $country === '') {
        $error = 'Please fill in all shipping details.';
    } elseif ($payment === 'Card') {
        $card = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $expiry = trim($_POST['card_expiry'] ?? '');
        $cvv = trim($_POST['card_cvv'] ?? '');
        if (strlen($card) < 12 || $expiry === '' || strlen($cvv) < 3) {
            $error = 'Enter valid card details to complete payment.';
        }
    } elseif ($payment === 'UPI') {
        $upi = trim($_POST['upi_id'] ?? '');
        if ($upi === '' || strpos($upi, '@') === false) {
            $error = 'Enter a valid UPI ID (example: name@upi).';
        }
    }

    if ($error === '') {
        foreach ($cart_items as $item) {
            if ((int)$item['quantity'] > (int)$item['stock']) {
                $error = htmlspecialchars($item['name']) . ' does not have enough stock.';
                break;
            }
        }
    }

    if ($error === '') {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, subtotal, shipping_cost, total_amount, shipping_address, shipping_city, shipping_zip, shipping_country, payment_method, estimated_delivery, coupon_code, coupon_discount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 5 DAY), ?, ?)");
        $stmt->execute([$user_id, $subtotal, $shipping, $total, $address, $city, $zip, $country, $payment, $coupon_code, $coupon_discount]);
        $order_id = $conn->lastInsertId();

        foreach ($cart_items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['name'], $item['quantity'], $item['unit_price']]);

            $new_stock = max(0, (int)$item['stock'] - (int)$item['quantity']);
            $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmt->execute([$new_stock, $item['product_id']]);
        }

        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        unset($_SESSION['coupon_code']);

        header("Location: order_success.php?order_id=" . $order_id);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="page-header">
        <a href="cart.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">Checkout</h1>
    </div>

    <div class="checkout-container">
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="checkout.php" method="POST" class="checkout-form">
            <div class="checkout-section">
                <h3><i class="fas fa-map-marker-alt"></i> Shipping Address</h3>
                <div class="form-group">
                    <label>Street Address *</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ($user['address'] ?? '')); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ($user['city'] ?? '')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>ZIP Code *</label>
                        <input type="text" name="zip" value="<?php echo htmlspecialchars($_POST['zip'] ?? ($user['zip_code'] ?? '')); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Country *</label>
                    <select name="country" required>
                        <?php
                        $selected_country = $_POST['country'] ?? ($user['country'] ?? 'India');
                        foreach (['India', 'USA', 'UK', 'Canada', 'Australia'] as $c):
                        ?>
                        <option value="<?php echo $c; ?>" <?php echo $selected_country === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="checkout-section">
                <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                <div class="payment-options">
                    <?php $pay = $_POST['payment_method'] ?? 'COD'; ?>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="COD" <?php echo $pay === 'COD' ? 'checked' : ''; ?> onchange="togglePayFields()">
                        <span class="payment-card">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Cash on Delivery</span>
                        </span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Card" <?php echo $pay === 'Card' ? 'checked' : ''; ?> onchange="togglePayFields()">
                        <span class="payment-card">
                            <i class="fas fa-credit-card"></i>
                            <span>Credit/Debit Card</span>
                        </span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="UPI" <?php echo $pay === 'UPI' ? 'checked' : ''; ?> onchange="togglePayFields()">
                        <span class="payment-card">
                            <i class="fas fa-mobile-alt"></i>
                            <span>UPI / Mobile Pay</span>
                        </span>
                    </label>
                </div>

                <div id="card-fields" class="pay-extra" style="display:none;">
                    <p class="coupon-note">Demo checkout — card details are not stored.</p>
                    <div class="form-group">
                        <label>Card number</label>
                        <input type="text" name="card_number" maxlength="19" placeholder="ACCT-000015">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry</label>
                            <input type="text" name="card_expiry" placeholder="MM/YY">
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="password" name="card_cvv" maxlength="4" placeholder="123">
                        </div>
                    </div>
                </div>
                <div id="upi-fields" class="pay-extra" style="display:none;">
                    <p class="coupon-note">Demo checkout — UPI ID is used only to confirm payment.</p>
                    <div class="form-group">
                        <label>UPI ID</label>
                        <input type="text" name="upi_id" placeholder="yourname@upi">
                    </div>
                </div>
            </div>

            <div class="checkout-section">
                <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                <div class="checkout-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="checkout-item">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                        <div class="checkout-item-info">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>Qty: <?php echo (int)$item['quantity']; ?></p>
                        </div>
                        <span class="checkout-item-price">₹<?php echo number_format($item['line_total'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="checkout-totals">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <?php if ($coupon_discount > 0): ?>
                    <div class="summary-row">
                        <span>Coupon (<?php echo htmlspecialchars($coupon_code); ?>)</span>
                        <span>-₹<?php echo number_format($coupon_discount, 2); ?></span>
                    </div>
                    <?php elseif ($coupon_message): ?>
                    <p class="coupon-note"><?php echo htmlspecialchars($coupon_message); ?></p>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?php echo $shipping == 0 ? 'Free' : '₹' . number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>

            <button type="submit" name="place_order" value="1" class="place-order-btn">
                <i class="fas fa-lock"></i> Pay & Place Order - ₹<?php echo number_format($total, 2); ?>
            </button>
        </form>
    </div>
    <script>
        function togglePayFields() {
            const method = document.querySelector('input[name="payment_method"]:checked').value;
            document.getElementById('card-fields').style.display = method === 'Card' ? 'block' : 'none';
            document.getElementById('upi-fields').style.display = method === 'UPI' ? 'block' : 'none';
        }
        togglePayFields();
    </script>
    <script src="script.js?v=2.1"></script>
</body>
</html>
