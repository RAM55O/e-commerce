<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?error=Access denied");
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order
$stmt = $conn->prepare("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: admin_orders.php");
    exit();
}

// Get order items
$items_stmt = $conn->prepare("SELECT oi.*, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'];
    $tracking = $_POST['tracking_number'] ?? '';
    
    $stmt = $conn->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE id = ?");
    $stmt->execute([$new_status, $tracking, $order_id]);
    
    header("Location: admin_order_detail.php?id=$order_id&success=Order updated successfully");
    exit();
}

$status_config = [
    'pending' => ['color' => '#ffd700', 'text' => 'Pending'],
    'confirmed' => ['color' => '#00d4aa', 'text' => 'Confirmed'],
    'processing' => ['color' => '#7c5ce0', 'text' => 'Processing'],
    'shipped' => ['color' => '#00bcd4', 'text' => 'Shipped'],
    'out_for_delivery' => ['color' => '#ff9800', 'text' => 'Out for Delivery'],
    'delivered' => ['color' => '#4caf50', 'text' => 'Delivered'],
    'cancelled' => ['color' => '#ff6b6b', 'text' => 'Cancelled']
];
$status = $status_config[$order['status']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?> - Admin</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <a href="admin_orders.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></h1>
    </div>

    <main class="main-content">
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <!-- Order Status Card -->
        <div class="admin-order-status" style="border-left: 4px solid <?php echo $status['color']; ?>">
            <div class="status-info">
                <h3>Current Status: <span style="color: <?php echo $status['color']; ?>"><?php echo $status['text']; ?></span></h3>
                <p>Order placed on <?php echo date('M d, Y \a\t h:i A', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <!-- Update Status Form -->
        <div class="detail-card">
            <h3><i class="fas fa-edit"></i> Update Order Status</h3>
            <form action="admin_order_detail.php?id=<?php echo $order_id; ?>" method="POST" class="status-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="status-select">
                            <?php foreach($status_config as $key => $val): ?>
                            <option value="<?php echo $key; ?>" <?php echo $order['status'] === $key ? 'selected' : ''; ?>>
                                <?php echo $val['text']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tracking Number</label>
                        <input type="text" name="tracking_number" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>" placeholder="Enter tracking number">
                    </div>
                </div>
                <button type="submit" class="btn-submit">Update Order</button>
            </form>
        </div>

        <!-- Customer Info -->
        <div class="detail-card">
            <h3><i class="fas fa-user"></i> Customer Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Name</label>
                    <p><?php echo htmlspecialchars($order['username']); ?></p>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <p><?php echo htmlspecialchars($order['email']); ?></p>
                </div>
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

        <!-- Order Items -->
        <div class="detail-card">
            <h3><i class="fas fa-box"></i> Order Items</h3>
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

        <!-- Payment Summary -->
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
                <div class="summary-row highlight">
                    <span>Shipping Charges</span>
                    <span>₹<?php echo number_format($order['shipping_cost'], 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total Amount</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <?php if($order['status'] === 'pending'): ?>
            <form action="admin_order_detail.php?id=<?php echo $order_id; ?>" method="POST" style="display: inline;">
                <input type="hidden" name="status" value="confirmed">
                <input type="hidden" name="tracking_number" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>">
                <button type="submit" class="btn-action confirm"><i class="fas fa-check"></i> Confirm Order</button>
            </form>
            <?php endif; ?>
            
            <?php if($order['status'] === 'confirmed'): ?>
            <form action="admin_order_detail.php?id=<?php echo $order_id; ?>" method="POST" style="display: inline;">
                <input type="hidden" name="status" value="processing">
                <input type="hidden" name="tracking_number" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>">
                <button type="submit" class="btn-action process"><i class="fas fa-cog"></i> Start Processing</button>
            </form>
            <?php endif; ?>
            
            <?php if($order['status'] === 'processing'): ?>
            <form action="admin_order_detail.php?id=<?php echo $order_id; ?>" method="POST" style="display: inline;">
                <input type="hidden" name="status" value="shipped">
                <input type="hidden" name="tracking_number" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>">
                <button type="submit" class="btn-action ship"><i class="fas fa-truck"></i> Mark as Shipped</button>
            </form>
            <?php endif; ?>

            <?php if($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
            <form action="admin_order_detail.php?id=<?php echo $order_id; ?>" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                <input type="hidden" name="status" value="cancelled">
                <input type="hidden" name="tracking_number" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>">
                <button type="submit" class="btn-action cancel"><i class="fas fa-times"></i> Cancel Order</button>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- Admin Bottom Navigation -->
    <nav class="bottom-nav admin-nav">
        <a href="admin_orders.php" class="nav-item active">
            <i class="fas fa-shopping-bag"></i>
            <span>Orders</span>
        </a>
        <a href="product_form.php" class="nav-item">
            <i class="fas fa-plus-circle"></i>
            <span>Add Product</span>
        </a>
        <a href="admin_dashboard.php" class="nav-item center-btn">
            <div class="nav-icon-wrapper">
                <i class="fas fa-th-large"></i>
            </div>
            <span>Dashboard</span>
        </a>
        <a href="admin_settings.php" class="nav-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>
    <script src="script.js?v=2.1"></script>
</body>
</html>
