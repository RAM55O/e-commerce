<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="success-container">
        <div class="success-box">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p class="order-number">Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
            <p class="success-message">Thank you for your purchase! Your order has been placed successfully.</p>
            
            <div class="order-info-card">
                <div class="info-row">
                    <span>Estimated Delivery</span>
                    <strong><?php echo date('M d, Y', strtotime($order['estimated_delivery'])); ?></strong>
                </div>
                <div class="info-row">
                    <span>Total Amount</span>
                    <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                </div>
                <div class="info-row">
                    <span>Payment Method</span>
                    <strong><?php echo $order['payment_method']; ?></strong>
                </div>
            </div>

            <div class="success-actions">
                <a href="orders.php" class="btn-view-orders">View My Orders</a>
                <a href="home.php" class="btn-continue">Continue Shopping</a>
            </div>
        </div>
    </div>
    <script src="script.js?v=2.1"></script>
</body>
</html>
