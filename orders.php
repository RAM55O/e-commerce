<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get current orders (pending, confirmed, processing, shipped, out_for_delivery)
$current_stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND status NOT IN ('delivered', 'cancelled') ORDER BY created_at DESC");
$current_stmt->execute([$user_id]);
$current_orders = $current_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get previous orders (delivered, cancelled)
$previous_stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND status IN ('delivered', 'cancelled') ORDER BY created_at DESC");
$previous_stmt->execute([$user_id]);
$previous_orders = $previous_stmt->fetchAll(PDO::FETCH_ASSOC);

// Status colors and icons
$status_config = [
    'pending' => ['color' => '#ffd700', 'icon' => 'fas fa-clock', 'text' => 'Pending'],
    'confirmed' => ['color' => '#00d4aa', 'icon' => 'fas fa-check', 'text' => 'Confirmed'],
    'processing' => ['color' => '#7c5ce0', 'icon' => 'fas fa-cog', 'text' => 'Processing'],
    'shipped' => ['color' => '#00bcd4', 'icon' => 'fas fa-truck', 'text' => 'Shipped'],
    'out_for_delivery' => ['color' => '#ff9800', 'icon' => 'fas fa-shipping-fast', 'text' => 'Out for Delivery'],
    'delivered' => ['color' => '#4caf50', 'icon' => 'fas fa-check-circle', 'text' => 'Delivered'],
    'cancelled' => ['color' => '#ff6b6b', 'icon' => 'fas fa-times-circle', 'text' => 'Cancelled']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .order-id span {
            font-size: 1rem;
            font-weight: 600;
            color: #00d4aa;
        }
        
        .order-id small {
            display: block;
            color: #888;
            font-size: 0.8rem;
            margin-top: 3px;
        }
        
        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }
        
        .order-info-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        
        .order-info-item label {
            font-size: 0.75rem;
            color: #888;
            text-transform: uppercase;
        }
        
        .order-info-item span {
            font-size: 0.95rem;
            color: #fff;
            font-weight: 500;
        }
        
        .order-info-item span.highlight {
            color: #00d4aa;
            font-weight: 700;
        }
        
        .order-items-list {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 15px;
            margin-bottom: 15px;
        }
        
        .order-items-title {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 10px;
        }
        
        .order-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.05);
        }
        
        .order-item-row:last-child {
            border-bottom: none;
        }
        
        .order-item-name {
            color: #fff;
            font-size: 0.9rem;
        }
        
        .order-item-qty {
            color: #888;
            font-size: 0.85rem;
        }
        
        .order-item-price {
            color: #00d4aa;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-view-details {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-view-details:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .order-card.previous {
            opacity: 0.8;
        }
        
        .empty-orders {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }
        
        .empty-orders i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .orders-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #00d4aa;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">My Orders</h1>
    </div>

    <div class="orders-container" style="padding: 20px; padding-bottom: 100px;">
        <!-- Current Orders -->
        <section class="orders-section">
            <h3 class="section-title"><i class="fas fa-clock"></i> Current Orders</h3>
            <?php if(count($current_orders) > 0): ?>
                <?php foreach($current_orders as $order): 
                    $status = $status_config[$order['status']];
                    // Get order items
                    $items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    $items_stmt->execute([$order['id']]);
                    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                    $item_count = count($items);
                ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">
                            <span>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            <small><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                        </div>
                        <div class="order-status" style="background: <?php echo $status['color']; ?>20; color: <?php echo $status['color']; ?>">
                            <i class="<?php echo $status['icon']; ?>"></i>
                            <?php echo $status['text']; ?>
                        </div>
                    </div>
                    
                    <!-- Order Info Grid -->
                    <div class="order-info-grid">
                        <div class="order-info-item">
                            <label>Total Amount</label>
                            <span class="highlight">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="order-info-item">
                            <label>Items</label>
                            <span><?php echo $item_count; ?> item<?php echo $item_count > 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="order-info-item">
                            <label>Payment</label>
                            <span><?php echo $order['payment_method'] === 'COD' ? 'Cash on Delivery' : $order['payment_method']; ?></span>
                        </div>
                        <div class="order-info-item">
                            <label>Est. Delivery</label>
                            <span><?php echo $order['estimated_delivery'] ? date('M d, Y', strtotime($order['estimated_delivery'])) : 'Calculating...'; ?></span>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="order-items-list">
                        <div class="order-items-title">Order Items:</div>
                        <?php foreach($items as $item): ?>
                        <div class="order-item-row">
                            <div>
                                <span class="order-item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                <span class="order-item-qty"> × <?php echo $item['quantity']; ?></span>
                            </div>
                            <span class="order-item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-footer">
                        <div>
                            <?php if($order['shipping_city']): ?>
                            <small style="color: #888;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['shipping_city']); ?></small>
                            <?php endif; ?>
                        </div>
                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn-view-details">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <i class="fas fa-box-open"></i>
                    <p>No current orders</p>
                    <a href="home.php" style="color: #00d4aa;">Start Shopping</a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Previous Orders -->
        <section class="orders-section">
            <h3 class="section-title"><i class="fas fa-history"></i> Previous Orders</h3>
            <?php if(count($previous_orders) > 0): ?>
                <?php foreach($previous_orders as $order): 
                    $status = $status_config[$order['status']];
                    $items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    $items_stmt->execute([$order['id']]);
                    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                    $item_count = count($items);
                ?>
                <div class="order-card previous">
                    <div class="order-header">
                        <div class="order-id">
                            <span>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            <small><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                        </div>
                        <div class="order-status" style="background: <?php echo $status['color']; ?>20; color: <?php echo $status['color']; ?>">
                            <i class="<?php echo $status['icon']; ?>"></i>
                            <?php echo $status['text']; ?>
                        </div>
                    </div>

                    <!-- Order Info Grid -->
                    <div class="order-info-grid">
                        <div class="order-info-item">
                            <label>Total Amount</label>
                            <span class="highlight">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="order-info-item">
                            <label>Items</label>
                            <span><?php echo $item_count; ?> item<?php echo $item_count > 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="order-info-item">
                            <label>Delivered On</label>
                            <span><?php echo $order['status'] === 'delivered' && $order['updated_at'] ? date('M d, Y', strtotime($order['updated_at'])) : '-'; ?></span>
                        </div>
                        <div class="order-info-item">
                            <label>Payment</label>
                            <span><?php echo $order['payment_method'] === 'COD' ? 'Cash on Delivery' : $order['payment_method']; ?></span>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="order-items-list">
                        <div class="order-items-title">Order Items:</div>
                        <?php foreach($items as $item): ?>
                        <div class="order-item-row">
                            <div>
                                <span class="order-item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                <span class="order-item-qty"> × <?php echo $item['quantity']; ?></span>
                            </div>
                            <span class="order-item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-footer">
                        <div></div>
                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn-view-details">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <i class="fas fa-history"></i>
                    <p>No previous orders</p>
                </div>
            <?php endif; ?>
        </section>
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
