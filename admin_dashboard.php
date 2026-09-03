<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?error=Access denied. Admin only.");
    exit();
}

$user_stmt = $conn->prepare("SELECT username, email, first_name, surname, mobile, address, city, zip_code, country, user_type FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit();
}

$username = $current_user['username'];
$display_name = trim(($current_user['first_name'] ?? '') . ' ' . ($current_user['surname'] ?? ''));
$email = $current_user['email'] ?? '';
$mobile = $current_user['mobile'] ?? '';
$address = $current_user['address'] ?? '';
$city = $current_user['city'] ?? '';
$zip_code = $current_user['zip_code'] ?? '';
$country = $current_user['country'] ?? '';

// Get analytics data
// Total Revenue
$revenue_stmt = $conn->query("SELECT SUM(total_amount) as total, SUM(shipping_cost) as shipping, SUM(subtotal) as subtotal FROM orders WHERE status != 'cancelled'");
$revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC);
$total_revenue = $revenue['total'] ?? 0;
$total_shipping = $revenue['shipping'] ?? 0;
$total_subtotal = $revenue['subtotal'] ?? 0;

// Today's Revenue
$today_stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'");
$today_revenue = $today_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Total Orders
$orders_stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $orders_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending Orders
$pending_stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status IN ('pending', 'confirmed', 'processing')");
$pending_orders = $pending_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Products
$products_stmt = $conn->query("SELECT COUNT(*) as total FROM products");
$total_products = $products_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Users
$users_stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'user'");
$total_users = $users_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent Orders
$recent_orders_stmt = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10");
$recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all products for management
$stmt = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$cat_stmt = $conn->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Status config
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
    <title>Admin Dashboard - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <h1 class="logo">Moonchild</h1>
            <div class="header-icons" style="margin-left: auto;">
                <span style="color: #ffd700; margin-right: 15px;">
                    <i class="fas fa-user-shield"></i> Admin: <?php echo htmlspecialchars($username); ?>
                </span>
                <a href="profile.php" class="icon-btn" title="Profile">
                    <i class="fas fa-user-cog"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Welcome Message -->
        <div class="admin-welcome">
            <h2>Hiii Admin!</h2>
            <p>Here's what's happening with your store today.</p>
        </div>

        <div class="admin-section">
            <div class="admin-header">
                <h3 class="section-title"><i class="fas fa-id-card"></i> My Profile</h3>
            </div>
            <div class="profile-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
                <div class="analytics-card users">
                    <div class="analytics-info">
                        <h3>Name</h3>
                        <p class="analytics-value" style="font-size: 1.05rem;"><?php echo htmlspecialchars($display_name ?: $username); ?></p>
                        <span class="analytics-sub"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                </div>
                <div class="analytics-card revenue">
                    <div class="analytics-info">
                        <h3>Email</h3>
                        <p class="analytics-value" style="font-size: 1.05rem;">&nbsp;<?php echo htmlspecialchars($email); ?></p>
                        <span class="analytics-sub">Contact account email</span>
                    </div>
                </div>
                <div class="analytics-card shipping">
                    <div class="analytics-info">
                        <h3>Mobile</h3>
                        <p class="analytics-value" style="font-size: 1.05rem;"><?php echo htmlspecialchars($mobile ?: '-'); ?></p>
                        <span class="analytics-sub"><?php echo htmlspecialchars(trim($city . ' ' . $country) ?: 'No address saved'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <!-- Analytics Cards -->
        <div class="analytics-grid">
            <div class="analytics-card revenue">
                <div class="analytics-icon"><i class="fas fa-rupee-sign"></i></div>
                <div class="analytics-info">
                    <h3>Total Revenue</h3>
                    <p class="analytics-value">₹<?php echo number_format($total_revenue, 2); ?></p>
                    <span class="analytics-sub">Today: ₹<?php echo number_format($today_revenue, 2); ?></span>
                </div>
            </div>
            <div class="analytics-card shipping">
                <div class="analytics-icon"><i class="fas fa-truck"></i></div>
                <div class="analytics-info">
                    <h3>Shipping Collected</h3>
                    <p class="analytics-value">₹<?php echo number_format($total_shipping, 2); ?></p>
                    <span class="analytics-sub">From all orders</span>
                </div>
            </div>
            <div class="analytics-card orders">
                <div class="analytics-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="analytics-info">
                    <h3>Total Orders</h3>
                    <p class="analytics-value"><?php echo $total_orders; ?></p>
                    <span class="analytics-sub"><?php echo $pending_orders; ?> pending</span>
                </div>
            </div>
            <div class="analytics-card profit">
                <div class="analytics-icon"><i class="fas fa-chart-line"></i></div>
                <div class="analytics-info">
                    <h3>Product Sales</h3>
                    <p class="analytics-value">₹<?php echo number_format($total_subtotal, 2); ?></p>
                    <span class="analytics-sub">Excluding shipping</span>
                </div>
            </div>
            <div class="analytics-card products">
                <div class="analytics-icon"><i class="fas fa-box"></i></div>
                <div class="analytics-info">
                    <h3>Total Products</h3>
                    <p class="analytics-value"><?php echo $total_products; ?></p>
                    <span class="analytics-sub">In catalog</span>
                </div>
            </div>
            <div class="analytics-card users">
                <div class="analytics-icon"><i class="fas fa-users"></i></div>
                <div class="analytics-info">
                    <h3>Total Customers</h3>
                    <p class="analytics-value"><?php echo $total_users; ?></p>
                    <span class="analytics-sub">Registered users</span>
                </div>
            </div>
        </div>

        <!-- Recent Orders Section -->
        <div class="admin-section">
            <div class="admin-header">
                <h3 class="section-title"><i class="fas fa-clock"></i> Recent Orders</h3>
                <a href="admin_orders.php" class="btn-view-all">View All Orders</a>
            </div>
            
            <div class="orders-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Shipping</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $order): 
                            $status = $status_config[$order['status']];
                        ?>
                        <tr>
                            <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($order['shipping_cost'], 2); ?></td>
                            <td>
                                <span class="status-badge" style="background: <?php echo $status['color']; ?>20; color: <?php echo $status['color']; ?>">
                                    <?php echo $status['text']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="admin_order_detail.php?id=<?php echo $order['id']; ?>" class="btn-edit">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($recent_orders) == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #888; padding: 30px;">No orders yet</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Product Management -->
        <div class="admin-section">
            <div class="admin-header">
                <h3 class="section-title"><i class="fas fa-box"></i> Product Management</h3>
                <a href="product_form.php" class="btn-add-product">
                    <i class="fas fa-plus"></i> Add Product
                </a>
            </div>

            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Stock</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $product): ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($product['image']); ?>" alt=""></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                            <td>₹<?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <?php if(isset($product['discount']) && $product['discount'] > 0): ?>
                                <span style="background: linear-gradient(135deg, #ff6b6b, #ff4757); color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 600;">-<?php echo $product['discount']; ?>%</span>
                                <?php else: ?>
                                <span style="color: #666;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($product['stock'] < 10): ?>
                                <span style="color: #ff6b6b;"><?php echo $product['stock']; ?> (Low)</span>
                                <?php else: ?>
                                <?php echo $product['stock']; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($product['featured']): ?>
                                    <span style="color: #00d4aa;"><i class="fas fa-star"></i></span>
                                <?php else: ?>
                                    <span style="color: #666;"><i class="far fa-star"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="product_form.php?id=<?php echo $product['id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="product_action.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete this product?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="admin-section">
            <div class="admin-header">
                <h3 class="section-title"><i class="fas fa-th-large"></i> Categories</h3>
                <a href="category_form.php" class="btn-add-product">
                    <i class="fas fa-plus"></i> Add Category
                </a>
            </div>

            <div class="categories-grid">
                <?php foreach($categories as $cat): ?>
                <div class="category-card" style="position: relative;">
                    <div class="category-icon">
                        <i class="<?php echo $cat['icon']; ?>"></i>
                    </div>
                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <a href="category_form.php?id=<?php echo $cat['id']; ?>" style="color: #7c5ce0; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="category_action.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete this category?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                            <button type="submit" style="background: none; border: none; color: #ff6b6b; font-size: 0.8rem; cursor: pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Admin Bottom Navigation -->
    <nav class="bottom-nav admin-nav">
        <a href="admin_orders.php" class="nav-item">
            <i class="fas fa-shopping-bag"></i>
            <span>Orders</span>
        </a>
        <a href="product_form.php" class="nav-item">
            <i class="fas fa-plus-circle"></i>
            <span>Add Product</span>
        </a>
        <a href="admin_dashboard.php" class="nav-item center-btn active">
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
