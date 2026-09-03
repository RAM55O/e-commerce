<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?error=Access denied");
    exit();
}

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get orders
if ($status_filter !== 'all') {
    $stmt = $conn->prepare("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.status = ? ORDER BY o.created_at DESC");
    $stmt->execute([$status_filter]);
} else {
    $stmt = $conn->query("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order counts by status
$count_stmt = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$status_counts = [];
while ($row = $count_stmt->fetch(PDO::FETCH_ASSOC)) {
    $status_counts[$row['status']] = $row['count'];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Moonchild Admin</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <a href="admin_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">Manage Orders</h1>
    </div>

    <main class="main-content">
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="admin_orders.php?status=all" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                All <span class="count"><?php echo array_sum($status_counts); ?></span>
            </a>
            <a href="admin_orders.php?status=pending" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                Pending <span class="count"><?php echo $status_counts['pending'] ?? 0; ?></span>
            </a>
            <a href="admin_orders.php?status=processing" class="filter-tab <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">
                Processing <span class="count"><?php echo $status_counts['processing'] ?? 0; ?></span>
            </a>
            <a href="admin_orders.php?status=shipped" class="filter-tab <?php echo $status_filter === 'shipped' ? 'active' : ''; ?>">
                Shipped <span class="count"><?php echo $status_counts['shipped'] ?? 0; ?></span>
            </a>
            <a href="admin_orders.php?status=delivered" class="filter-tab <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>">
                Delivered <span class="count"><?php echo $status_counts['delivered'] ?? 0; ?></span>
            </a>
        </div>

        <!-- Orders Table -->
        <div class="orders-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Items</th>
                        <th>Subtotal</th>
                        <th>Shipping</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $order): 
                        $status = $status_config[$order['status']];
                        // Get item count
                        $items_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM order_items WHERE order_id = ?");
                        $items_stmt->execute([$order['id']]);
                        $item_count = $items_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                    ?>
                    <tr>
                        <td><strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                        <td><?php echo $item_count; ?></td>
                        <td>₹<?php echo number_format($order['subtotal'], 2); ?></td>
                        <td>₹<?php echo number_format($order['shipping_cost'], 2); ?></td>
                        <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        <td>
                            <span class="status-badge" style="background: <?php echo $status['color']; ?>20; color: <?php echo $status['color']; ?>">
                                <?php echo $status['text']; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        <td>
                            <a href="admin_order_detail.php?id=<?php echo $order['id']; ?>" class="btn-edit">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($orders) == 0): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; color: #888; padding: 40px;">No orders found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
