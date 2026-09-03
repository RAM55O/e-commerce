<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Get order items
$items_stmt = $conn->prepare("SELECT oi.*, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

$status_config = [
    'pending' => ['color' => '#ffd700', 'icon' => 'fas fa-clock', 'text' => 'Pending'],
    'confirmed' => ['color' => '#00d4aa', 'icon' => 'fas fa-check', 'text' => 'Confirmed'],
    'processing' => ['color' => '#7c5ce0', 'icon' => 'fas fa-cog', 'text' => 'Processing'],
    'shipped' => ['color' => '#00bcd4', 'icon' => 'fas fa-truck', 'text' => 'Shipped'],
    'out_for_delivery' => ['color' => '#ff9800', 'icon' => 'fas fa-shipping-fast', 'text' => 'Out for Delivery'],
    'delivered' => ['color' => '#4caf50', 'icon' => 'fas fa-check-circle', 'text' => 'Delivered'],
    'cancelled' => ['color' => '#ff6b6b', 'icon' => 'fas fa-times-circle', 'text' => 'Cancelled']
];
$status = $status_config[$order['status']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <a href="orders.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></h1>
    </div>

    <div class="order-detail-container">
        <!-- Status Card -->
        <div class="status-card" style="border-color: <?php echo $status['color']; ?>">
            <div class="status-icon" style="background: <?php echo $status['color']; ?>">
                <i class="<?php echo $status['icon']; ?>"></i>
            </div>
            <div class="status-info">
                <h3><?php echo $status['text']; ?></h3>
                <p>Order placed on <?php echo date('M d, Y \a\t h:i A', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <!-- Progress Tracker -->
        <?php if($order['status'] !== 'cancelled'): ?>
        <div class="tracking-card">
            <h3>Order Tracking</h3>
            <div class="order-progress-vertical">
                <?php 
                $stages = ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
                $stage_labels = ['Order Placed', 'Order Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered'];
                $current_index = array_search($order['status'], $stages);
                ?>
                <?php foreach($stages as $i => $stage): ?>
                <div class="tracking-step <?php echo $i <= $current_index ? 'completed' : ''; ?> <?php echo $i == $current_index ? 'current' : ''; ?>">
                    <div class="tracking-dot"></div>
                    <div class="tracking-content">
                        <strong><?php echo $stage_labels[$i]; ?></strong>
                        <?php if($i <= $current_index): ?>
                        <small><?php echo $i == $current_index ? 'Current Status' : 'Completed'; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if($order['tracking_number']): ?>
            <div class="tracking-number">
                <span>Tracking Number:</span>
                <strong><?php echo htmlspecialchars($order['tracking_number']); ?></strong>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Order Items -->
        <div class="detail-card">
            <h3>Order Items</h3>
            <div class="order-items-list">
                <?php foreach($items as $item): ?>
                <div class="order-item-detail">
                    <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://via.placeholder.com/80'); ?>" alt="">
                    <div class="item-info">
                        <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                        <p>Quantity: <?php echo $item['quantity']; ?></p>
                        <p class="item-price">₹<?php echo number_format($item['price'], 2); ?> each</p>
                    </div>
                    <div class="item-total">
                        ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Shipping Address -->
        <div class="detail-card">
            <h3><i class="fas fa-map-marker-alt"></i> Shipping Address</h3>
            <p class="address-text">
                <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                <?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_zip']); ?><br>
                <?php echo htmlspecialchars($order['shipping_country']); ?>
            </p>
        </div>

        <!-- Payment & Summary -->
        <div class="detail-card">
            <h3><i class="fas fa-receipt"></i> Payment Summary</h3>
            <div class="payment-summary">
                <div class="summary-row">
                    <span>Payment Method</span>
                    <span><?php echo $order['payment_method']; ?></span>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($order['subtotal'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>₹<?php echo number_format($order['shipping_cost'], 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>

        <?php if($order['estimated_delivery'] && $order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
        <div class="delivery-banner">
            <i class="fas fa-truck"></i>
            <div>
                <strong>Estimated Delivery</strong>
                <p><?php echo date('l, M d, Y', strtotime($order['estimated_delivery'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="home.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="offers.php" class="nav-item">
            <i class="fas fa-percent"></i>
            <span>Offers</span>
        </a>
        <a href="cart.php" class="nav-item center-btn">
            <div class="nav-icon-wrapper">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <span>Cart</span>
        </a>
        <a href="wishlist.php" class="nav-item">
            <i class="fas fa-heart"></i>
            <span>Wishlist</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>
    <script src="script.js?v=2.1"></script>
</body>
</html>
